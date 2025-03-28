<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Artwork;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * List user's orders
     */
    public function index(Request $request)
    {
        $query = Order::where('user_id', Auth::id());

        // Status filtering
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Date range filtering
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        // Sorting
        $query->orderBy('created_at', $request->input('sort', 'desc'));

        // Pagination
        $orders = $query->with('orderItems.artwork')->paginate($request->input('per_page', 10));

        return new OrderCollection($orders);
    }

    /**
     * Get a single order details
     */
    public function show(Order $order)
    {
        // Ensure the order belongs to the current user
        if ($order->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return new OrderResource(
            $order->load(['orderItems.artwork', 'reviews'])
        );
    }

    /**
     * Create a new order
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'shipping_address' => 'required|array',
            'billing_address' => 'required|array',
            'payment_method' => 'required|string',
            'order_items' => 'required|array',
            'order_items.*.artwork_id' => 'required|exists:artworks,id',
            'order_items.*.quantity' => 'required|integer|min:1'
        ]);

        // Start database transaction
        DB::beginTransaction();

        try {
            // Create order
            $order = Order::create([
                'user_id' => Auth::id(),
                'status' => 'pending',
                'total_amount' => 0,
                'shipping_address' => $validatedData['shipping_address'],
                'billing_address' => $validatedData['billing_address'],
                'payment_method' => $validatedData['payment_method']
            ]);

            $totalAmount = 0;

            // Process order items
            foreach ($validatedData['order_items'] as $item) {
                $artwork = Artwork::findOrFail($item['artwork_id']);

                // Check stock availability
                if ($artwork->stock_quantity < $item['quantity']) {
                    throw new \Exception("Insufficient stock for artwork: {$artwork->title}");
                }

                // Create order item
                $orderItem = $order->orderItems()->create([
                    'artwork_id' => $artwork->id,
                    'quantity' => $item['quantity'],
                    'price' => $artwork->price
                ]);

                // Update total amount
                $totalAmount += $artwork->price * $item['quantity'];

                // Reduce artwork stock
                $artwork->decrement('stock_quantity', $item['quantity']);
            }

            // Update order total amount
            $order->update(['total_amount' => $totalAmount]);

            DB::commit();

            return new OrderResource(
                $order->load('orderItems.artwork')
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Order creation failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancel an order
     */
    public function cancel(Order $order)
    {
        // Ensure the order belongs to the current user
        if ($order->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Only pending or processing orders can be cancelled
        if (!in_array($order->status, ['pending', 'processing'])) {
            return response()->json(['message' => 'This order cannot be cancelled'], 400);
        }

        // Start transaction to handle stock restoration
        DB::beginTransaction();

        try {
            // Restore stock for each order item
            foreach ($order->orderItems as $orderItem) {
                $artwork = $orderItem->artwork;
                $artwork->increment('stock_quantity', $orderItem->quantity);
            }

            // Update order status
            $order->update(['status' => 'cancelled']);

            DB::commit();

            return new OrderResource($order);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Order cancellation failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
