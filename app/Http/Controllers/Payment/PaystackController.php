<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\PaystackService;
use Illuminate\Http\Request;

class PaystackController extends Controller
{

    public function processWebhook(Request $request)
    {
        $paystack = new PaystackService();
        $paystackPaymentVerification = $paystack->verifyPayment($request);
        return $paystackPaymentVerification;
    }
}
