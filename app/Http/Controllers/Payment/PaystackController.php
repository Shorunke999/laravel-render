<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
class PaystackController extends Controller
{

    public function processWebhook(Request $request)
    {
        Log::info('message',
        [
            'in the verify payment controller method'
        ]);
        $paystack = new PaystackService();
        $paystackPaymentVerification = $paystack->verifyPayment($request);
        return $paystackPaymentVerification;
    }
}
