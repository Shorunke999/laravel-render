<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;

class PasswordResetController extends Controller
{
    /*public function forgotPassword(Request $request)
    {
        try{
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);

            $token = Str::random(64);

            // Store token in the password_reset_tokens table
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                ['token' => $token, 'created_at' => Carbon::now()]
            );

            // Use defer for sending mail
            defer(function() use ($request,$token){
                Mail::to($request->email)->send(new \App\Mail\ResetPasswordMail($token, $request->email));
            });

            return response()->json([
                'message' => 'Password reset link has been sent to your email.'
            ], 200);
        }catch(\Exception $e)
        {
            Log::info('Error when forgot Passord ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ],422);
        }

    }*/
    public function forgotPassword(Request $request)
{
    try {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $token = Str::random(64);

        // Store token in the password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $token, 'created_at' => Carbon::now()]
        );

        // Create the email service instance
        $emailService = new EmailService();

        // Use defer for sending mail
        defer(function() use ($request, $token, $emailService) {
            $mailable = new \App\Mail\ResetPasswordMail($token, $request->email);
            $emailService->send($mailable, $request->email);
        });

        return response()->json([
            "status" => true,
            'message' => 'Password reset link has been sent to your email.'
        ], 200);
    } catch(\Exception $e) {
        Log::info('Error when forgot Password ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], 422);
    }
}

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Verify token
        $tokenData = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$tokenData) {
            return response()->json([
                'status' => 'false',
                'message' => 'Invalid token!'
            ], 422);
        }

        // Check if token is expired (default 60 minutes)
        $createdAt = Carbon::parse($tokenData->created_at);
        if (Carbon::now()->diffInMinutes($createdAt) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json([
                'status' => false,
                'message' => 'Token has expired!'
            ], 422);
        }

        // Update password
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete the token after password reset
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'status'=>true,
            'message' => 'Password reset successfully!'
        ], 200);
    }
}
