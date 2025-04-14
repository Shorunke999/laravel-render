<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Concurrency;
class PaystackService
{
    protected $secretKey;
    protected $baseUrl;
    protected $callbackUrl;
    public function __construct()
    {
        $this->secretKey = env('PAYSTACK_SECRET_KEY');
        $this->baseUrl = env('PAYSTACK_BASEURL');
        $this->callbackUrl= env('PAYSTACK_CALLBACKURL');

    }

    public function intializePayment($user, Order $order, array $metadata = [])
    {
        try {
            $uniqueRef = 'Tiimbooktu_' . Str::random(12);

            $order->update([
                'reference_code'=>$uniqueRef
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '. $this->secretKey,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl.'transaction/initialize',[
                'email'=>$user->email,
                'amount' => intval($order->total_amount) * 100,
                'reference' =>$uniqueRef,
                'metadata' => $metadata,
                'callback_url' => $this->callbackUrl,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::info('error during initializePayment',
            [
                'message' => 'error '.$e->getMessage()
            ]);
        }
    }

    public function chargeUserRecurring($user, Order $order, array $metadata = [])
    {
        try {

        $uniqueRef = 'Tiimbooktu_' . Str::random(12);

        $order->update([
            'reference_code'=>$uniqueRef
        ]);
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '. $this->secretKey,
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl.'transaction/charge_authorization',[
            'email'=>$user->email,
            'amount' => intval($order->total_amount) * 100,
            'authorization_code' => $user->authorization_code,
            'reference' =>$uniqueRef,
            'metadata' => $metadata,
            'callback_url' => $this->callbackUrl,
        ]);
        return $response->json();
        } catch (\Exception $e) {
            Log::info('error during chargeUserReccuring',
            [
                'message' => 'error '.$e->getMessage()
            ]);
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ],500);

        }
    }

    public function verifyPayment(Request $request)
    {
        //validate webhook signature.
        $payload = $request->getContent();

        $signature =  hash_hmac('sha512',$payload,$this->secretKey);
        if ($signature != $request->header('x-paystack-signature'))
        {

            Log::info('invalid signature',
            [
                'status' => false,
                'error' =>
                [
                    'message' => 'invalid signature',
                    'customer_info' => [
                        'customer_id' => $request->data->customer_id,
                        'customer_email' => $request->data->email,
                    ]
                ]

            ]);
            return response()->json(
                [
                    'status' => false,
                    'message' => 'invalid signature'
                ],401
            );
        }

        if ($request->event == 'charge.success')
        {
            defer(
                function() use ($request)
                {
                    $order = Order::where('reference_code',$request->data['reference'])
                    ->first();
                    $order->update([
                        'payment_status' => 'success',
                        'status' => 'processing'
                    ]);
                    $orderService = new OrderService();

                       $orderService->decreaseArtworksStock($order);

                    $authorization = json_encode($request->data['authorization'] ?? []);
                    $authorizationCode = $request->data['authorization']['authorization_code'];

                    $user = User::find($order->user_id);
                    if ($user && $user->recurring_transaction) {
                        $user->update([
                            'authorization_code' => $authorizationCode,
                            'authorization' => $authorization
                        ]);
                    }
                    PaymentTransaction::create([
                        'order_id' => $order->id,
                        'amount' => $request->data['amount'] / 100,
                        'reference' => $request->data['reference'],
                        'status' => 'verified',
                        'metadata' => json_encode($request->data['metadata'] ?? [])
                    ]);
                }
            );

           return response()->json([
            'status'=>true,
        ],200);
        }
    }
}
