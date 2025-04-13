<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Artwork;
use App\Models\Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

use function PHPUnit\Framework\isEmpty;

class OrderService
{
    public function getUserOrders(array $filters = [])
    {
        $user = Auth::user();
        if($user->type == 'customer')
        {
            $query = Order::where('user_id', $user->id);

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
        }else{
            $query = Order::query();

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }else
            {
                $query->where('status','processing');
            }
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

    public function createOrder(array $orderData)
    {
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
                'contact' => $orderData['shipping_address'] ?? $orderData['email'],
                'shipping_address' => $orderData['shipping_address'],
                'email' => Auth::user()->email,
            ]);

           $totalAmount = 0;

            foreach ($cartItems as $item) {
                $this->processOrderItem($order, $item, $totalAmount);
            }

            $order->update(['total_amount' => $totalAmount]);
            return $order;
        });
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
        $artwork->decrement('stock', $item->quantity);
        $item->delete();
    }

    public function UpdateOrder(Order $order, $orderStatus)
    {
        if (!in_array($order->status, ['pending', 'cancelled'])) {
            throw new Exception('This order cannot be updated',400);
        }
        $order->update([
            'status' => $orderStatus
        ]);
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
}
