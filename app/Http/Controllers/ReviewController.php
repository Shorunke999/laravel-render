<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReviewResource;
use App\Models\Artwork;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    /**
     * List reviews for a specific artwork
     */
    public function artworkReviews(Artwork $artwork)
    {
        try {
            $reviews = $artwork->reviews()
                ->with('user')
                ->latest()
                ->paginate(10);

            return response()->json([
                'status' => true,
                'message' => 'Fetch Reviews',
                'reviews' =>ReviewResource::collection($reviews),
                'average_rating' =>$artwork->averageRating()
                ], 200);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status'=> false,
                    'message' => $e->getMessage()
                ]
                );
        }
    }

    /**
     * Get user's reviews
     */
    public function userReviews()
    {
        $reviews = Review::where('user_id', Auth::id())
            ->with(['artwork', 'order'])
            ->latest()
            ->paginate(10);

            return response()->json([
                'reviews' => ReviewResource::collection($reviews)
            ],200);
    }

    /**
     * Create a new review
     */
    public function store(Request $request)
    {
        try {

            $validatedData = $request->validate([
                'order_id' => 'required|exists:orders,id',
                'artwork_id' => 'required|exists:artworks,id',
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:500'
            ]);

            // Verify order belongs to user and is delivered
            $order = Order::findOrFail($validatedData['order_id']);

            if ($order->user_id !== Auth::id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if ($order->status !== 'delivered') {
                return response()->json(['message' => 'Reviews can only be submitted for delivered orders'], 400);
            }

            // Check if review already exists
            $existingReview = Review::where('user_id', Auth::id())
                ->where('order_id', $validatedData['order_id'])
                ->where('artwork_id', $validatedData['artwork_id'])
                ->first();

            if ($existingReview) {
                return response()->json(['message' => 'You have already reviewed this artwork for this order'], 400);
            }

            $review = Review::create([
                'user_id' => Auth::id(),
                'order_id' => $validatedData['order_id'],
                'artwork_id' => $validatedData['artwork_id'],
                'rating' => $validatedData['rating'],
                'comment' => $validatedData['comment'],
                'is_verified' => true
            ]);
            return response()->json([
                'status' => true,
                'message' => 'Review Created Successfully',
                'reviews' =>new ReviewResource($review)
                ], 200);
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'status'=> false,
                    'message' => $e->getMessage()
                ],422
                );
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status'=> false,
                    'message' => $e->getMessage()
                ],500
                );
        }
    }

    /**
     * Update an existing review
     */
    public function update(Request $request, Review $review)
    {
        try {
            // Ensure the review belongs to the current user
            if ($review->user_id !== Auth::id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validatedData = $request->validate([
                'rating' => 'integer|min:1|max:5',
                'comment' => 'nullable|string|max:500'
            ]);

            $review->update($validatedData);
            return response()->json([
                'status' => true,
                'message' => 'Review Updated Successfully',
                'reviews' =>new ReviewResource($review)
                ], 200);
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'status'=> false,
                    'message' => $e->getMessage()
                ],422
                );
        } catch (\Exception $e) {
            return response()->json(
                [
                    'status'=> false,
                    'message' => $e->getMessage()
                ],500
                );
        }
    }

    /**
     * Delete a review
     */
    public function destroy(Review $review)
    {
        // Ensure the review belongs to the current user
        if ($review->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $review->delete();

        return response()->json(['message' => 'Review deleted successfully']);
    }
}
