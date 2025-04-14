<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable //implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'authorization_code',
        'authorization',
        'recurring_transaction'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

      /**
     * Generate email verification token
     */
    public function generateEmailVerificationToken()
    {
        $this->verification_token = hash_hmac('sha256', $this->email, config('app.key'));
        $this->save();
    }

    /**
     * Verify email
     */
    public function verifyEmail($token)
    {
        if ($this->verification_token === $token) {
            $this->email_verified_at = now();
            $this->verification_token = null;
            $this->save();
            return true;
        }
        return false;
    }

    /**
     * Check if email is verified
     */
    public function hasVerifiedEmail()
    {
        return !is_null($this->email_verified_at);
    }

    public function cart()
    {
        return $this->hasMany(Cart::class);
    }

    // Optional: Add a mutator to normalize the type
    public function setTypeAttribute($value)
    {
        $this->attributes['type'] = strtolower($value);
    }


   /* public function cartCount()
    {
        return $this->cart->count();
    }*/
}
