<?php

use App\Http\Controllers\ArtworkController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Payment\PaystackController;
use App\Http\Controllers\ReviewController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    //Category Routes

    Route::get('/categories', [CategoryController::class, 'index']);  // List all categories
    Route::post('/categories', [CategoryController::class, 'store']); // Create a new category
    Route::get('/categories/{category}', [CategoryController::class, 'show']); // Get a single category
    Route::put('/categories/{category}', [CategoryController::class, 'update']); // Update a category
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']); // Delete a category

    // Artwork Routes
    Route::get('/artworks', [ArtworkController::class, 'index']);
    Route::post('/artwork', [ArtworkController::class, 'store']);
    //Route::get('/artworks/featured', [ArtworkController::class, 'featured']);
    Route::get('/artworks/search', [ArtworkController::class, 'search']);
    Route::get('/artworks/{artwork}', [ArtworkController::class, 'show']);

    //cart
    Route::post('/cart/add', [CartController::class, 'addToCart'])->name('cart.add');
    Route::get('/cart', [CartController::class, 'viewCart'])->name('cart.view');
    Route::put('/cart/update', [CartController::class, 'updateCart'])->name('cart.update');
    Route::delete('/cart/remove/{cartId}', [CartController::class, 'removeFromCart'])->name('cart.remove');
    Route::delete('/cart/clear', [CartController::class, 'clearCart'])->name('cart.clear');

    // Review Routes
    Route::get('/artworks/{artwork}/reviews', [ReviewController::class, 'artworkReviews']);
    Route::get('/reviews', [ReviewController::class, 'userReviews']);
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

    // Order Routes
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);


});

Route::post('/paystack/payment',[PaystackController::class, 'initiateTransaction']);
Route::post('/webhook/verify',[PaystackController::class, 'processWebhook']);