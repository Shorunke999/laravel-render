<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateArtworkRequest;
use App\Http\Resources\ArtworkResource;
use App\Models\Artwork;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

class UtilityController extends Controller
{
     /**
     * Update an existing artwork
     */
    public function update(UpdateArtworkRequest $request, Artwork $artwork): JsonResponse
    {
        try {
            Log::info('in the update artwork controller');
            $artwork->update($request->validated());
            Log::info('Validation completed');

            $cloudinaryService = new CloudinaryService();
            // Remove old images before adding new ones
            if ($request->hasFile('images')) {
                Log::info('images processing');
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
        }catch (\Exception $e) {
            Log::info('error',[
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'message' => 'Error updating artwork',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
