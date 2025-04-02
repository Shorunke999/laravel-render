<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\OrderProcessingException;
use App\Models\Order;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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
            'shipping_address' => 'required|string',
            'email' => 'required|string',
        ]);
        try {
            $order = $this->orderService->createOrder($validatedData);
            $paystackPayment = new PaystackService();
            $initializePayment = $paystackPayment->intializePayment($validatedData['email'],$order);
            return response()->json([
                'message' => 'Order created successfully',
                'order_id' => $order->id,
                'checkout_url' =>  $initializePayment['data']['authorization_url'],
                'authorization_url' => $initializePayment['data']['authorization_url']
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Order creation failed',
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function update(Request $request, Order $order)
    {
        try {
            $validatedData = Validator::make($request->all(),
            [
                'status' => 'required|in:shipped,delivered'
            ]);
            $order = $this->orderService->updateOrder($order,$validatedData['status']);
            return response()
            ->json([
                'status' => true,
                'message' => 'Order status has been updated to '. $validatedData['status'] . "successfull"
            ]);
        }catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Order updating failed',
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Order updating failed',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function cancel(Order $order)
    {
        try {
            $order = $this->orderService->cancelOrder($order);
            return response()
            ->json([
                'status' => true,
                'message' => 'Order canceled successfully',
                'canceled_order' =>new OrderResource($order)
            ]);

        }catch (\Exception $e) {
            return response()->json([
                'message' => 'Order cancellation failed',
                'error' => $e->getMessage()
            ]);
        }
    }
}