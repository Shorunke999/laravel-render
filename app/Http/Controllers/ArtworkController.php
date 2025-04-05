<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use App\Http\Requests\StoreArtworkRequest;
use App\Http\Requests\UpdateArtworkRequest;
use App\Http\Resources\ArtworkResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Http\JsonResponse;

class ArtworkController extends Controller
{
    /**
     * List all artworks with filtering and sorting
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Artwork::query();

            // Filtering
            if ($request->has('category')) {
                $query->whereHas('category', function ($q) use ($request) {
                    $q->where('slug', $request->input('category'));
                });
            }

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
            $query->with(['category','images','colorVariants','sizeVariants']);

            // Pagination
            $artworks = $query->paginate($request->input('per_page', 12));

            return response()->json([
                'artworks'=>ArtworkResource::collection($artworks)
            ],200);
        } catch (Exception $e) {
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

            // Calculate total variant stocks
            $totalVariantStock = 0;

            // Sum color variant stocks if present
            if ($request->has('color_variants')) {
                foreach ($request->color_variants as $variant) {
                    $totalVariantStock += $variant['stock'];
                }
            }

            // Sum size variant stocks if present
            if ($request->has('size_variants')) {
                foreach ($request->size_variants as $variant) {
                    $totalVariantStock += $variant['stock'];
                }
            }

            // If variants exist, validate stock matches
            if (($request->has('color_variants') || $request->has('size_variants')) &&
                $validatedData['stock'] != $totalVariantStock) {
                throw ValidationException::withMessages([
                    'stock' => 'The total artwork stock must equal the sum of all variant stocks.'
                ]);
            }

            // Create artwork
            $artwork = Artwork::create($validatedData);

            // Handle image upload if present
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('artworks', 'b2');
                    $artwork->images()->create(['image_url' => Storage::url($path)]);
                }
            }

            // Handle color variants
            if ($request->has('color_variants')) {
                foreach ($request->color_variants as $variant) {
                    $artwork->colorVariants()->create($variant);
                }
            }

            // Handle size variants
            if ($request->has('size_variants')) {
                foreach ($request->size_variants as $variant) {
                    $artwork->sizeVariants()->create($variant);
                }
            }

            return response()->json([
                'message' => 'Artwork created successfully',
                'artwork' => new ArtworkResource($artwork->load(['category','images','colorVariants','sizeVariants'])),
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
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
            return response()->json(
                new ArtworkResource(
                    $artwork->load([
                        'category',
                        'reviews' => function ($query) {
                            $query->latest()->limit(5);
                        },
                        'colorVariants',
                        'sizeVariants',
                        'images'
                    ])
                )
            );
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Artwork not found',
                'error' => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
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


            // Remove old images before adding new ones
            if ($request->hasFile('images')) {
                // Delete old images from storage
                foreach ($artwork->images as $oldImage) {
                     // Extract the relative path from the full URL
                    $imagePath = str_replace(Storage::url(''), '', $oldImage->image_url);

                    // Delete from storage if exists
                    Storage::disk('b2')->delete($imagePath);

                    // Delete the image record
                    $oldImage->delete();
                }
                // Store new images
                foreach ($request->file('images') as $image) {
                    $path = $image->store('artworks', 'b2');
                    $artwork->images()->create(['image_url' => Storage::url($path)]);
                }
            }
            // Handle color variants
            if ($request->has('color_variants')) {
                $artwork->colorVariants()->delete();
                foreach ($request->color_variants as $variant) {
                    $artwork->colorVariants()->create($variant);
                }
            }

            // Handle size variants
            if ($request->has('size_variants')) {
                $artwork->sizeVariants()->delete();
                foreach ($request->size_variants as $variant) {
                    $artwork->sizeVariants()->create($variant);
                }
            }

            return response()->json([
                'message' => 'Artwork Updated Successfully',
                'artwork' =>  new ArtworkResource($artwork->load(['colorVariants', 'sizeVariants','images']))]

            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Artwork not found',
                'error' => $e->getMessage()
            ], 404);
        } catch (Exception $e) {
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
                ->with('category')
                ->paginate(12);

            return response()->json([
                'artwork' => ArtworkResource::collection($artworks)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error searching artworks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Artwork $artwork)
    {
        $artworkName = $artwork->name;
        $artwork->delete();
        return response()->json([
            'message' => $artworkName . " Artwork has been deleted successfully"
        ]);
    }
}