<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArtworkResource;
use App\Models\Artwork;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function index()
    {
        try {
            $wishlist = Auth::user()->wishlist()->with('images')->get();

            if($wishlist->isEmpty())
            {
                return response()->json([
                    'status' => true,
                    'message' => 'Wishlist Is Empty.',
                ]);
            }
            return response()->json([
                'status' => true,
                'message' => 'Wishlist retrieved successfully.',
                'artworks' => ArtworkResource::collection($wishlist),
            ]);
        } catch (Exception $e) {
            Log::error('Wishlist index error', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.'
            ], 500);
        }
    }

    public function store($artworkId)
    {
        try {
            $user = Auth::user();

            $artwork = Artwork::find($artworkId);
            if (!$artwork) {
                return response()->json([
                    'status' => false,
                    'message' => 'Artwork not found.'
                ], 404);
            }

            if ($user->wishlist()->where('artwork_id', $artworkId)->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Artwork already in wishlist.'
                ], 409);
            }

            $user->wishlist()->attach($artworkId);

            return response()->json([
                'status' => true,
                'message' => 'Artwork added to wishlist.'
            ], 201);
        } catch (Exception $e) {
            Log::error('Wishlist store error', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.'
            ], 500);
        }
    }

    public function destroy($artworkId)
    {
        try {
            $user = Auth::user();

            $artwork = Artwork::find($artworkId);
            if (!$artwork) {
                return response()->json([
                    'status' => false,
                    'message' => 'Artwork not found.'
                ], 404);
            }

            if (!$user->wishlist()->where('artwork_id', $artworkId)->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Artwork not in wishlist.'
                ], 404);
            }

            $user->wishlist()->detach($artworkId);

            return response()->json([
                'status' => true,
                'message' => 'Artwork removed from wishlist.'
            ]);
        } catch (Exception $e) {
            Log::error('Wishlist destroy error', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.'
            ], 500);
        }
    }
}
