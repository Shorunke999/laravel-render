<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Artwork;
use App\Models\ArtworkColorVariant;
use App\Models\ArtworkSizeVariant;
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

    /**
     * Add item to cart
     */
    public function addItem(array $data): Cart
    {
        return DB::transaction(function () use ($data) {
            $artwork = Artwork::with(['colorVariants', 'sizeVariants'])
                ->findOrFail($data['artwork_id']);

            $this->checkStockAvailability($artwork, $data);

            $cartItem = Cart::firstOrNew([
                'user_id' => Auth::id(),
                'artwork_id' => $data['artwork_id'],
                'color_variant_id' => $data['color_variant_id'] ?? null,
                'size_variant_id' => $data['size_variant_id'] ?? null,
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
            return $cartItem->load(['artwork', 'colorVariants', 'sizeVariants']);
        });
    }

    /**
     * Get cart items
     */
    public function getCartItems()
    {
        return Cache::remember($this->cacheKey, now()->addMinutes(10), function () {
            return Cart::where('user_id', Auth::id())
                ->with([
                    'artwork',
                    'colorVariants',
                    'sizeVariants'
                ])
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
                ->where('id', $data['cart_id'])
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $this->checkStockAvailability($cartItem->artwork, $data);

            $cartItem->update([
                'quantity' => $data['quantity'],
                'color_variant_id' => $data['color_variant_id'] ?? null,
                'size_variant_id' => $data['size_variant_id'] ?? null,
            ]);

            $this->clearCache();

            return $cartItem->load(['artwork', 'colorVariants', 'sizeVariants']);
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

        if (isset($data['color_variant_id'])) {
            $colorVariant = $artwork->colorVariants
                ->firstWhere('id', $data['color_variant_id']);

            if (!$colorVariant || $requestedQuantity > $colorVariant->stock) {
                throw new Exception("Insufficient stock for selected color variant. Available: " .
                    ($colorVariant ? $colorVariant->stock : 0));
            }
        }

        if (isset($data['size_variant_id'])) {
            $sizeVariant = $artwork->sizeVariants
                ->firstWhere('id', $data['size_variant_id']);

            if (!$sizeVariant || $requestedQuantity > $sizeVariant->stock) {
                throw new Exception("Insufficient stock for selected size variant. Available: " .
                    ($sizeVariant ? $sizeVariant->stock : 0));
            }
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