<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaystackService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
class PaystackController extends Controller
{

    public function processWebhook(Request $request)
    {
        $paystack = new PaystackService();
        $paystackPaymentVerification = $paystack->verifyPayment($request);
        return $paystackPaymentVerification;
    }

    public function initiateTransaction(Request $request)
    {
        $validatedData = $request->validate([
            'metadata' => 'nullable|array',
            'recurring' => 'nullable|boolean',
            'order_id' => 'nullable|exists:orders,id'
        ]);
        try {
            $user = Auth::user();
            if(isset($validatedData['recurring']))
            {
                $user->recurring_transaction = $validatedData['recurring'];
                $user->save();
            }
            $order = Order::find($validatedData['order_id']);
            if($order->payment_status == "success")
            {
                throw new Exception('This order payment has been made already');
            }
            $paystackPayment = new PaystackService();
            if($user->recurring_transaction && $user->authorization_code)
            {
                $Payment = $paystackPayment->chargeUserRecurring($user,$order,$validatedData['metadata']);
                $message = "Order created and Charging using recurring card details successfull";
            }
            else
            {
                $Payment = $paystackPayment->intializePayment($user,$order,$validatedData['metadata']);
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
            Log::info('Error during Paystack Payment',
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
}
