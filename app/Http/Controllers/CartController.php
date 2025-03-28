<?php

namespace App\Http\Controllers;

use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\Artwork;
use App\Models\ArtworkColorVariant;
use App\Models\ArtworkSizeVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{

    /**
     * Add item to cart
     */
    public function addToCart(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $validatedData = $request->validate([
                'artwork_id' => 'required|exists:artworks,id',
                'quantity' => 'integer|min:1|max:100',
                'color_variant_id' => 'nullable|exists:artwork_color_variants,id',
                'size_variant_id' => 'nullable|exists:artwork_size_variants,id',
            ]);

            return DB::transaction(function () use ($validatedData) {
                $user = Auth::user();
                $artwork = Artwork::findOrFail($validatedData['artwork_id']);

                // Check stock availability
                $this->checkStockAvailability($artwork, $validatedData);

                // Find existing cart item
                $cartItem = Cart::where('user_id', $user->id)
                    ->where('artwork_id', $validatedData['artwork_id'])
                    ->when($validatedData['color_variant_id'], function ($query) use ($validatedData) {
                        return $query->where('color_variant_id', $validatedData['color_variant_id']);
                    })
                    ->when($validatedData['size_variant_id'], function ($query) use ($validatedData) {
                        return $query->where('size_variant_id', $validatedData['size_variant_id']);
                    })
                    ->first();

                if ($cartItem) {
                    // Update quantity
                    $newQuantity = $cartItem->quantity + $validatedData['quantity'];
                    $this->checkStockAvailability($artwork, array_merge($validatedData, ['quantity' => $newQuantity]));

                    $cartItem->update([
                        'quantity' => $newQuantity
                    ]);
                } else {
                    // Create new cart item
                    $cartItem = Cart::create([
                        'user_id' => $user->id,
                        'artwork_id' => $validatedData['artwork_id'],
                        'quantity' => $validatedData['quantity'],
                    ]);
                }

                // Manage color variant pivot
                if ($validatedData['color_variant_id']) {
                    $colorVariant = ArtworkColorVariant::findOrFail($validatedData['color_variant_id']);
                    $cartItem->colorVariants()->syncWithoutDetaching([$colorVariant->id]);
                }

                // Manage size variant pivot
                if ($validatedData['size_variant_id']) {
                    $sizeVariant = ArtworkSizeVariant::findOrFail($validatedData['size_variant_id']);
                    $cartItem->sizeVariants()->syncWithoutDetaching([$sizeVariant->id]);
                }

                return response()->json([
                    'status' => true,
                    'message' => "Item added to cart successfully",
                    'cart_item' => new CartResource($cartItem),

                ], 201);
            });

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'error' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * View cart
     */
    public function viewCart()
    {
        try {
            $cacheKey = 'user_cart_' . Auth::id();

            $cartItems = Cache::remember($cacheKey, now()->addMinutes(10), function () {
                return Cart::where('user_id', Auth::id())
                    ->with(['artwork', 'colorVariants', 'sizeVariants'])
                    ->get();
            });

            $total = $cartItems->sum(function ($item) {
                return $item->calculateTotalPrice();
            });

            return response()->json([
                'cart_items' => CartResource::collection($cartItems),
                'total_cart_price' => $total,
                'cart_count' => $cartItems->count()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error retrieving cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateCart(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'cart_id' => 'required|exists:carts,id',
                'quantity' => 'required|integer|min:1'
            ]);

            $cartItem = Cart::findOrFail($validatedData['cart_id']);

            // Ensure the cart item belongs to the current user
            if ($cartItem->user_id !== Auth::id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized action'
                ], 403);
            }

            // Check stock availability
            $artwork = $cartItem->artwork;
            $this->checkStockAvailability($artwork, [
                'artwork_id' => $artwork->id,
                'quantity' => $validatedData['quantity']
            ]);

            // Update cart item
            $cartItem->update([
                'quantity' => $validatedData['quantity']
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Cart updated successfully',
                'cart_item' => new CartResource($cartItem)
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error updating cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart($cartId): JsonResponse
    {
        try {
            $cartItem = Cart::findOrFail($cartId);

            // Ensure the cart item belongs to the current user
            if ($cartItem->user_id !== Auth::id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized action'
                ], 403);
            }

            $cartItem->delete();

            return response()->json([
                'status' => true,
                'message' => 'Item removed from cart'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error removing item from cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear entire cart
     */
    public function clearCart(): JsonResponse
    {
        try {
            $cartItemsCount = Cart::where('user_id', Auth::id())->count();
            Cart::where('user_id', Auth::id())->delete();

            return response()->json([
                'status' => true,
                'message' => 'Cart cleared successfully',
                'items_removed' => $cartItemsCount
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error clearing cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check stock availability
     */
    private function checkStockAvailability(Artwork $artwork, array $data): void
    {
        $requestedQuantity = $data['quantity'];

        // Check base artwork stock
        if ($requestedQuantity > $artwork->stock) {
            throw new \Exception("Insufficient stock for {$artwork->name}. Available: {$artwork->stock}");
        }

        // Check color variant stock if specified
        if (isset($data['color_variant_id'])) {
            $colorVariant = $artwork->colorVariants
                ->firstWhere('id', $data['color_variant_id']);

            if (!$colorVariant || $requestedQuantity > $colorVariant->stock) {
                throw new \Exception("Insufficient stock for selected color variant. Available: " .
                    ($colorVariant ? $colorVariant->stock : 0));
            }
        }

        // Check size variant stock if specified
        if (isset($data['size_variant_id'])) {
            $sizeVariant = $artwork->sizeVariants
                ->firstWhere('id', $data['size_variant_id']);

            if (!$sizeVariant || $requestedQuantity > $sizeVariant->stock) {
                throw new \Exception("Insufficient stock for selected size variant. Available: " .
                    ($sizeVariant ? $sizeVariant->stock : 0));
            }
        }
    }
}