<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Artwork;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Exception;

class CartService
{
    protected string $cacheKey;

    public function __construct()
    {
        $this->cacheKey = 'user_cart_' . (Auth::id() ?? 0);
    }

    public function addItem(array $data): Cart
    {
        return DB::transaction(function () use ($data) {
            $artwork = Artwork::findOrFail($data['artwork_id']);

            $this->checkStockAvailability($artwork, $data);

            $cartItem = Cart::firstOrNew([
                'user_id' => Auth::id(),
                'artwork_id' => $data['artwork_id'],
            ]);

            if ($cartItem->exists) {
                $newQuantity = $cartItem->quantity + $data['quantity'];
                $this->checkStockAvailability($artwork, array_merge($data, ['quantity' => $newQuantity]));
                $cartItem->quantity = $newQuantity;
            } else {
                $cartItem->quantity = $data['quantity'];
            }

            $cartItem->save();
            $this->clearCache();

            return $cartItem->load('artwork');
        });
    }

    public function getCartItems()
    {
        return Cache::remember($this->cacheKey, now()->addMinutes(10), function () {
            return Cart::where('user_id', Auth::id())
                ->with(
                    'artwork',
                    'artwork.images'
                )
                ->get();
        });
    }

    /**
     * Update cart item
     */
    public function updateItem(array $data): Cart
    {
        return DB::transaction(function () use ($data) {
            $cartItem = Cart::with('artwork')
                ->where('id', $data['cart_item_id'])
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $this->checkStockAvailability($cartItem->artwork, $data);

            $cartItem->update([
                'quantity' => $data['quantity'],
            ]);

            $this->clearCache();

            return $cartItem->load('artwork');
        });
    }

    /**
     * Remove item from cart
     */
    public function removeItem(int $cartId): void
    {
        DB::transaction(function () use ($cartId) {
            $cartItem = Cart::where('id', $cartId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $cartItem->delete();
            $this->clearCache();
        });
    }

    /**
     * Clear entire cart
     */
    public function clearCart(): int
    {
        return DB::transaction(function () {
            $count = Cart::where('user_id', Auth::id())->count();
            Cart::where('user_id', Auth::id())->delete();
            $this->clearCache();
            return $count;
        });
    }

    /**
     * Calculate total cart price
     */
    public function calculateTotalPrice(): float
    {
        return $this->getCartItems()->sum->calculateTotalPrice();
    }

    /**
     * Check stock availability
     */
    private function checkStockAvailability(Artwork $artwork, array $data): void
    {
        $requestedQuantity = $data['quantity'];

        if ($requestedQuantity > $artwork->stock) {
            throw new Exception("Insufficient stock for {$artwork->name}. Available: {$artwork->stock}");
        }

    }

    /**
     * Clear cart cache
     */
    private function clearCache(): void
    {
        Cache::forget($this->cacheKey);
    }
}
