<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Exception;

class UtilityService
{
    public function subscribeNewsletter($email): void
    {
        $exists = DB::table('subscribe_newsletter')->where('email', $email)->exists();

        if ($exists) {
            throw new Exception('Email Already Subscribed to Newsletter', 422);
        }

        DB::table('subscribe_newsletter')->insert([
            'email' => $email,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
