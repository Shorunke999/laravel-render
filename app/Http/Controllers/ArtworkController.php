<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use App\Http\Requests\StoreArtworkRequest;
use App\Http\Requests\UpdateArtworkRequest;
use App\Http\Resources\ArtworkResource;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ArtworkController extends Controller
{
    /**
     * List all artworks with filtering and sorting
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Artwork::query();


            // Price range filter
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->input('min_price'));
            }

            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->input('max_price'));
            }

            // Artist filter
            if ($request->has('artist')) {
                $query->where('artist_name', 'like', '%' . $request->input('artist') . '%');
            }

            // Sorting
            if ($request->has('sort')) {
                switch ($request->input('sort')) {
                    case 'price_asc':
                        $query->orderBy('price', 'asc');
                        break;
                    case 'price_desc':
                        $query->orderBy('price', 'desc');
                        break;
                    case 'newest':
                        $query->orderBy('created_at', 'desc');
                        break;
                    case 'rating':
                        $query->withAvg('reviews', 'rating')
                              ->orderByDesc('reviews_avg_rating');
                        break;
                }
            }

            // Include relationships
            $query->with(['images']);

            // Pagination
            $artworks = $query->paginate($request->input('per_page', 12));

            return response()->json([
                'status' => true,
                'message' =>  "Artwork fetched successfully",
                'artworks'=>ArtworkResource::collection($artworks)
            ],200);
        } catch (Exception $e)
        {
            Log::info('error',[
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'message' => 'Error retrieving artworks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created artwork in storage.
     */
    public function store(StoreArtworkRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            // Create artwork
            $artwork = Artwork::create($validatedData);

            $cloudinaryService = new CloudinaryService();
            // Handle image upload if present
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {;
                    $filename = time().'_'. Str::random();

                    // Use the upload API directly
                    $result = $cloudinaryService->upload(
                        $image->getRealPath(),
                        'artwork',
                        $filename
                    );
                    //$path = $image->store('artworks', 'b2');
                    $artwork->images()->create(['image_url' =>$result['secure_url'] ]);
                }
            }

            return response()->json([
                'message' => 'Artwork created successfully',
                'artwork' => new ArtworkResource($artwork->load(['images'])),
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::info('error',[
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'message' => 'Error creating artwork',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a single artwork with detailed information
     */
    public function show(Artwork $artwork): JsonResponse
    {
        try {
            $relatedArtworks = Artwork::inRandomOrder()
            ->where('id', '!=', $artwork->id)
            ->with('images')
            ->latest()
            ->take(5)
            ->get();

        $artwork->load([
            'images'
        ]);

        return response()->json([
            'artwork' => new ArtworkResource($artwork),
            'related_artworks' => ArtworkResource::collection($relatedArtworks),
        ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Artwork not found',
                'error' => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            Log::info('error',[
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'message' => 'Error retrieving artwork',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing artwork
     */
    public function update(UpdateArtworkRequest $request, Artwork $artwork): JsonResponse
    {
        try {
            $artwork->update($request->validated());


            $cloudinaryService = new CloudinaryService();
            // Remove old images before adding new ones
            if ($request->hasFile('images')) {
                // Delete old images from storage
                foreach ($artwork->images as $oldImage) {

                    $publicId = $cloudinaryService->extractCloudinaryPublicId($oldImage->image_url);
                    // Delete the image from Cloudinary using your service
                    $cloudinaryService->delete($publicId);
                     // Extract the relative path from the full URL
                    //$imagePath = str_replace(Storage::url(''), '', $oldImage->image_url);

                    // Delete from storage if exists
                    //Storage::disk('b2')->delete($imagePath);

                    // Delete the image record
                    $oldImage->delete();
                }
                // Store new images
                foreach ($request->file('images') as $image) {
                    $filename = time().'_'. Str::random();

                    // Use the upload API directly
                    $result = $cloudinaryService->upload(
                        $image->getRealPath(),
                        'artwork',
                        $filename,
                    );
                     $artwork->images()->create(['image_url' => $result['secure_url']]);
                    //$path = $image->store('artworks', 'b2');
                    //$artwork->images()->create(['image_url' => Storage::url($path)]);
                }
            }
            return response()->json([
                'message' => 'Artwork Updated Successfully',
                'artwork' =>  new ArtworkResource($artwork->load(['images']))]

            );
        } catch (ValidationException $e) {
            Log::info('error',[
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            Log::info('error',[
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'message' => 'Artwork not found',
                'error' => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            Log::info('error',[
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'message' => 'Error updating artwork',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search artworks
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->input('query');

            // Validate query parameter
            if (empty($query)) {
                return response()->json([
                    'message' => 'Search query is required'
                ], 400);
            }

            $artworks = Artwork::where('name', 'like', "%{$query}%")
                ->orWhere('artist', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->with(['images'])
                ->paginate(12);

            return response()->json([
                'artwork' => ArtworkResource::collection($artworks)
            ]);
        } catch (Exception $e) {
            Log::info('error',[
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'message' => 'Error searching artworks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Artwork $artwork)
    {

        $cloudinaryService = new CloudinaryService();
        $artworkName = $artwork->name;
        foreach ($artwork->images as $Image) {

            $publicId = $cloudinaryService->extractCloudinaryPublicId($Image->image_url);
            // Delete the image from Cloudinary using your service
            $cloudinaryService->delete($publicId);
        }
        $artwork->delete();
        return response()->json([
            'message' => $artworkName . " Artwork has been deleted successfully"
        ]);
    }
}
