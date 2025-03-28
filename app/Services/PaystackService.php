<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    public function intializePayment(string $email, string $amount)
    {
        $response = Http::withHeaders([
            'Authorization' => $this->secretKey,
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl.'transaction/initialize',[
            'email'=>$email,
            'amount' => $amount * 100,
            'callback_url' => $this->callbackUrl,
            //'authorization_code' => $authorizationCode

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
            //save data to db
                //db fields ---service type(paystack),event(charge.success),customerid,payload-json
            return response([
                'status'=>true,
            ],200)->json();
        }
    }

    public function chargeAuthorization(string $email, string $amount)
    {
        //get from db authorization code.
        $authorizationCode = '';
        $response = Http::withHeaders([
            'Authorization' => $this->secretKey,
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl.'transaction/charge_authorization',[
            'email'=>$email,
            'amount' => $amount * 100,
            'authorization_code' => $authorizationCode
        ]);
        return $response->json();
    }
}
