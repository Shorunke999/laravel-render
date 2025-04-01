<?php

namespace App\Http\Controllers;

use App\Http\Resources\CartResource;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Add item to cart
     */
    public function addToCart(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'artwork_id' => 'required|exists:artworks,id',
                'quantity' => 'integer|min:1|max:100',
                'color_variant_id' => 'nullable|exists:artwork_color_variants,id',
                'size_variant_id' => 'nullable|exists:artwork_size_variants,id',
            ]);

            $cartItem = $this->cartService->addItem($validatedData);
            $cartItems = $this->cartService->getCartItems();

            return response()->json([
                'status' => true,
                'message' => "Item added to cart successfully",
                'cart' =>  CartResource::collection($cartItems),
                'cart_count' => $cartItems->count()
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * View cart
     */
    public function viewCart(): JsonResponse
    {
        try {
            $cartItems = $this->cartService->getCartItems();

            return response()->json([
                'cart' => CartResource::collection($cartItems),
                'total_cart_price' => $this->cartService->calculateTotalPrice(),
                'cart_count' => $cartItems->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error retrieving cart'
            ], 500);
        }
    }

    /**
     * Update cart item
     */
    public function updateCart(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'cart_item_id' => 'required|exists:carts,id',
                'quantity' => 'required|integer|min:1|max:100',
                'color_variant_id' => 'nullable|exists:artwork_color_variants,id',
                'size_variant_id' => 'nullable|exists:artwork_size_variants,id',
            ]);

            $cartItem = $this->cartService->updateItem($validatedData);
            $cartItems = $this->cartService->getCartItems();
            return response()->json([
                'status' => true,
                'message' => 'Cart updated successfully',
                'cart' => CartResource::collection($cartItems),
            ],200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error updating cart ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart($cartId): JsonResponse
    {
        try {
            $this->cartService->removeItem($cartId);
            $cartItems = $this->cartService->getCartItems();
            return response()->json([
                'status' => true,
                'message' => 'Item removed from cart',
                'cart' => CartResource::collection($cartItems)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error removing item from cart'
            ], 500);
        }
    }

    /**
     * Clear entire cart
     */
    public function clearCart(): JsonResponse
    {
        try {
            $itemsRemoved = $this->cartService->clearCart();

            return response()->json([
                'status' => true,
                'message' => 'Cart cleared successfully',
                'cart_items_removed' => $itemsRemoved,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error clearing cart'
            ], 500);
        }
    }
}