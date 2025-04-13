<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\PaystackService;
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

    public function diableRecurringCharge()
    {
        $user = Auth::user();
        if(!$user->recurring_transaction)
        {
            return response()->json([
                'status' => true,
                'message' => 'Recurring charge is already disabled'
            ],200);
        }
        $user->update([
            'recurring_transaction' => false,
            'authorization' => null,
            'authorization_code' => null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Recurring charge has been disabled'
        ],200);
    }
}
