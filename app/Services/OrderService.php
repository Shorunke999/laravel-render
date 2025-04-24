<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Artwork;
use App\Models\Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

use function PHPUnit\Framework\isEmpty;

class OrderService
{
    public function getUserOrders(array $filters = [])
    {
        $user = Auth::user();
        if($user->type == 'customer')
        {
            $query = Order::where('user_id', $user->id);
        }else{
            $query = Order::query();
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        $sort = $filters['sort'] ?? 'desc';
        return $query->with('orderItems.artwork')
                    ->orderBy('created_at', $sort)
                    ->paginate($filters['per_page'] ?? 10);
    }

    public function createOrder(array $orderData,$user)
    {
        try{
                return DB::transaction(function () use ($orderData) {
                $cartItems = Cart::with('artwork')->where('user_id', Auth::id())->get();
                if($cartItems->isEmpty())
                {
                    throw new Exception('Cart is Empty');
                }
                $order = Order::create([
                    'user_id' => Auth::id(),
                    'status' => 'pending',
                    'total_amount' => 0,
                    'contact' => $orderData['contact'],
                    'shipping_details' => json_encode($orderData['shipping_details']),
                    'shipping_method' => $orderData['shipping_method'],
                ]);

            $totalAmount = 0;

                foreach ($cartItems as $item) {
                    $this->processOrderItem($order, $item, $totalAmount);
                }

                $order->update(['total_amount' => $totalAmount]);
                return $order;
            });
        } catch (Exception $e) {
            // Log the exception for debugging
            Log::error('Order creation failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'exception' => $e
            ]);

            // Re-throw the exception to be handled by the controller
            throw $e;
        }

    }

    protected function processOrderItem(Order $order, Cart $item, float &$totalAmount)
    {
        $artwork = $item->artwork;

        if ($artwork->stock < $item->quantity) {
            throw new Exception( "Insufficient stock for artwork: {$artwork->title}, Available stock is {$artwork->stock}",403);
         }

        $order->orderItems()->create([
            'artwork_id' => $artwork->id,
            'quantity' => $item->quantity,
            'price' => $item->calculateTotalPrice(),
        ]);

        $totalAmount += $item->calculateTotalPrice();
        //$artwork->decrement('stock', $item->quantity);
        $item->delete();
    }

    public function UpdateOrder(Order $order, $orderStatus, $deliveredAt)
    {
        if (!in_array($order->status, ['pending', 'cancelled'])) {
            throw new Exception('This order cannot be updated',400);
        }

        $order->status = $orderStatus;
        if($deliveredAt)
        {
            $order->status = $deliveredAt;
        }
        $order->save();
        return $order;
    }

    public function cancelOrder(Order $order)
    {
        if ($order->user_id !== Auth::id()) {

            throw new Exception("Unauthorize",400);
        }

        if (!in_array($order->status, ['pending', 'processing'])) {
            throw new Exception('This order cannot be cancelled',400);
        }

        DB::transaction(function () use ($order) {
            foreach ($order->orderItems as $orderItem) {
                $orderItem->artwork->increment('stock', $orderItem->quantity);
            }
            $order->update(['status' => 'cancelled']);
        });

        return $order;
    }

    public function decreaseArtworksStock(Order $order)
    {
        return DB::transaction(function () use ($order) {
            // Fixed typo: orederItems -> orderItems
            $order->orderItems()->each(function ($item) use ($order) {
                $artwork = Artwork::find($item->artwork_id);

                // Check if we still have enough stock at payment time
                if ($artwork->stock < $item->quantity) {
                    Log::error("Insufficient stock during payment processing", [
                        'artwork_id' => $item->artwork_id,
                        'requested' => $item->quantity,
                        'available' => $artwork->stock,
                        'order_id' => $order->id
                    ]);
                }

                $artwork->decrement('stock', $item->quantity);
            });

            return true;
        });
    }
}
