<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

    public function intializePayment(string $email, Order $order)
    {
        $uniqueRef = 'Tiimbooktu_' . Str::random(12);

        $order->update([
            'reference_code'=>$uniqueRef
        ]);
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '. $this->secretKey,
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl.'transaction/initialize',[
            'email'=>$email,
            'amount' => $order->total_amount * 100,
            'reference' =>$uniqueRef,
            'callback_url' => $this->callbackUrl,
        ]);

        return $response->json();
    }

    public function verifyPayment(Request $request)
    {
        //validate webhook signature.
        $signature =  hash_hmac('sha512',$request->body(),$this->secretKey);
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
            return response([
                'status' => false,
                'message' => 'invalid signature'
            ],401)->json();
        }

        if ($request->event == 'charge.success')
        {
            $order = Order::where('reference_code',$request->data->reference)
            ->first();
            $order->update([
                'payment_status' => 'success',
                'status' => 'processing'
            ]);
           return response([
                'status'=>true,
            ],200)->json();
        }
    }
}
