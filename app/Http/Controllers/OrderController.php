<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\OrderProcessingException;
use App\Models\Order;
use App\Services\PaystackService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
        try {
            $validatedData = $request->validate([
                'shipping_details' => 'required|array',
                'contact' => 'required|email',
                'shipping_method' => 'required|in:standard,express',
                'recurring' => 'required|boolean',
                'metadata' => "nullable|array"
            ]);
            $user = Auth::user();
            if(isset($validatedData['recurring']))
            {
                $user->recurring_transaction = $validatedData['recurring'];
                $user->save();
            }
            $order = $this->orderService->createOrder($validatedData,$user);
            $paystackPayment = new PaystackService();
            if($user->recurring_transaction && $user->authorization_code)
            {
                $Payment = $paystackPayment->chargeUserRecurring($user,$order,$validatedData['metadata'] ?? []);
                $message = "Order created and Charging using recurring card details successfull";
            }
            else
            {
                $Payment = $paystackPayment->intializePayment($user,$order,$validatedData['metadata'] ?? []);
                $message = "Order created successfully and will be redirected now";
            }
            if(!$Payment['status'])
            {
                throw new Exception($Payment['message']);
            }
            return response()->json([
                'status' => true,
                'message' => $message,
                'order_id' => $order->id ?? null,
                'checkout_url' =>  $Payment['data']['authorization_url'] ?? [],
            ], 201);
        } catch (\Throwable $th) {
            return response()
            ->json([
                'status' =>false,
                'message' => $th->getMessage()
            ],422);
        }catch(\Exception $e)
        {
            Log::info('Error during Order Creation and Paystack',
            [
                'status' =>false,
                'message' => $e->getMessage()
            ]);
            return response()
            ->json([
                'status' =>false,
                'message' => $e->getMessage()
            ],500);
        }
    }

    public function update(Request $request, Order $order)
    {
        try {
            $validatedData = $request->validate([
                'status' => 'required|in:shipped,delivered'
            ]);

            $deliveredAt = ($validatedData['status'] == "delivered") ? now() : null;
            $order = $this->orderService->updateOrder($order, $validatedData['status'],$deliveredAt);

            return response()
            ->json([
                'status' => true,
                'message' => 'Order status has been updated to '. $validatedData['status'] . " successfully"
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Order updating failed',
                'error' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Order updating failed',
                'error' => $e->getMessage()
            ], 500);
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
            ]);

        }catch (\Exception $e) {
            return response()->json([
                'message' => 'Order cancellation failed',
                'error' => $e->getMessage()
            ]);
        }
    }
}
