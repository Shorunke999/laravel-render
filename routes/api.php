<?php

use App\Http\Controllers\ArtworkController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Payment\PaystackController;
use App\Http\Controllers\ReviewController;
use App\Http\Middleware\CheckAdmin;
use Illuminate\Support\Facades\Route;

    Route::get('/login', [AuthController::class, 'Unauthorized'])->name('login');
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // Optional: Email Verification Routes
    // Route::get('verify-email/{token}', [AuthController::class, 'verifyEmail']);


Route::middleware('auth:sanctum')->group(function () {
     // Get authenticated user
    Route::get('me', [AuthController::class, 'me']);
    Route::post('log-out', [AuthController::class, 'logout']);
    //Category Routes

    Route::middleware([CheckAdmin::class])->group(function () {

        //Categories actions for Admin only
        Route::post('/categories', [CategoryController::class, 'store']); // Create a new category
        Route::get('/categories/{category}', [CategoryController::class, 'show']); // Get a single category
        Route::put('/categories/{category}', [CategoryController::class, 'update']); // Update a category
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']); // Delete a category

        //Artwork actions for Admin only
        Route::post('/artwork', [ArtworkController::class, 'store']);
        Route::put('/artworks/{artwork}', [ArtworkController::class, 'update']);
        Route::delete('/artworks/{artwork}', [ArtworkController::class, 'destroy']);

        //Order Update
        //Route::post('/orders/{order}/update', [OrderController::class, 'update']);
    });
  //Order Update
  Route::post('/orders/{order}/update', [OrderController::class, 'update']);
    Route::get('/categories', [CategoryController::class, 'index']);  // List all categories

    // Artwork Routes
    Route::get('/artworks', [ArtworkController::class, 'index']);
    Route::get('/artworks/search', [ArtworkController::class, 'search']);
    Route::get('/artworks/{artwork}', [ArtworkController::class, 'show']);

    // Get cart contents
    Route::get('/cart', [CartController::class, 'viewCart']);
    // Add item to cart
    Route::post('/cart', [CartController::class, 'addToCart']);
    // Update cart item
    Route::put('/cart', [CartController::class, 'updateCart']);
    // Remove item from cart
    Route::delete('/cart/{cartItem}', [CartController::class, 'removeFromCart']);
    // Clear entire cart
    Route::delete('/cart', [CartController::class, 'clearCart']);


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

    //payment
    Route::post('/paystack/payment',[PaystackController::class, 'initiateTransaction'])->name('payment.process');

});
Route::post('/webhook/verify',[PaystackController::class, 'processWebhook']);