<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateArtworkRequest;
use App\Http\Resources\ArtworkResource;
use App\Models\Artwork;
use App\Models\Order;
use App\Services\CloudinaryService;
use App\Services\UtilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

class UtilityController extends Controller
{
    public function subscribeNewsletter(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email'
            ]);

            $utilityInstance = new UtilityService();
            $utilityInstance->subscribeNewsletter($request->email);

            return response()->json([
                'message' => 'Successfully subscribed to the newsletter!',
                'status' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => false
            ], $e->getCode() > 0 ? $e->getCode() : 500);
        }
    }

    public function getAdminStats()
    {
        $totalArtworks = Artwork::count();

        $pendingOrdersCount = Order::whereIn('status', ['pending', 'shipped', 'processing'])->count();
        $deliveredOrdersCount = Order::where('status', 'delivered')->count();

        return response()->json([
            'total_artworks'    => $totalArtworks,
            'pending_orders'    => $pendingOrdersCount,
            'delivered_orders'  => $deliveredOrdersCount,
        ]);
    }
}
