<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\OrderProcessingException;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function index(Request $request)
    {
        $orders = $this->orderService->getUserOrders($request->all());
        return OrderResource::collection($orders);
    }

    public function show(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return new OrderResource(
            $order->load(['orderItems.artwork', 'reviews'])
        );
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'shipping_address' => 'required|array',
            'billing_address' => 'required|array',
            'payment_method' => 'required|string'
        ]);

        try {
            $order = $this->orderService->createOrder($validatedData);
            return response()->json([
                'message' => 'Order created successfully',
                'order_id' => $order->id,
                'payment_redirect' => route('payment.process', $order->id)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Order creation failed',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function cancel(Order $order)
    {
        try {
            $order = $this->orderService->cancelOrder($order);
            return new OrderResource($order);
        }catch (\Exception $e) {
            return response()->json([
                'message' => 'Order cancellation failed',
                'error' => $e->getMessage()
            ]);
        }
    }
}