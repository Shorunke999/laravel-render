<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Str;
use App\Services\CloudinaryService;
class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Category::with('artworks');

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return response()->json(CategoryResource::collection($query->paginate(10)));
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
        $validated = $request->validate([
            'name'        => 'required|string|unique:categories,name',
            'description' => 'nullable|string',
            'image'       => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);


        $cloudinaryService = new CloudinaryService();
        // Store image
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time().'_'. Str::random();

            // Use the upload API directly
            $result = $cloudinaryService->upload(
                $image->getRealPath(),
                'categories',
                $filename
            );
            //$path = Storage::disk('cloudinary')->put('categories',$request->file('image'));
            $validated['image_url'] = $result['secure_url'];
        }

        $category = Category::create($validated);

        return response()->json([
            'message'=>"Category created successfully",
            'category'=>new CategoryResource($category)
        ], 201);
        }catch (ValidationException $e) {
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
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return response()->json([
            'category' =>new CategoryResource($category->load(['artworks','artworks.images']))
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        try {

        $validated = $request->validate([
            'name'        => 'required|string|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Handle image update
        if ($request->hasFile('image')) {
            // Delete old image
            if ($category->image_url) {

                $cloudinaryService = new CloudinaryService();
                $publicId = $this->extractCloudinaryPublicId($category->image_url);
                // Delete the image from Cloudinary using your service
                $cloudinaryService->delete($publicId);
                //$oldImagePath = str_replace(Storage::url(''), '', $category->image_url);
                //if (Storage::disk('b2')->exists($oldImagePath)) {
                  //  Storage::disk('b2')->delete($oldImagePath);
                //}
            }

            // Store new image
            //$path = $request->file('image')->store('categories', 'b2');
            $image = $request->file('image');
            $filename = time().'_'. Str::random();

            // Use the upload API directly
            $result = $cloudinaryService->upload(
                $image->getRealPath(),
                'categories',
                $filename
            );
            $validated['image_url'] = $result['secure_path'];
        }

        $category->update($validated);

        return response()->json([
            'message' => "Category updated successfully",
            'category' => new CategoryResource($category)
        ],200);
        }catch (ValidationException $e) {
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
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {

        $cloudinaryService = new CloudinaryService();
        // Delete associated image
            $publicId = $this->extractCloudinaryPublicId($category->image_url);
            // Delete the image from Cloudinary using your service
            $cloudinaryService->delete($publicId);
        /*if ($category->image_url) {
            $imagePath = str_replace(Storage::url(''), '', $category->image_url);
            if (Storage::disk('b2')->exists($imagePath)) {
                Storage::disk('b2')->delete($imagePath);
            }
        }*/

        $category->delete();
        return response()->json(['message' => 'Category deleted successfully.']);
    }

    public function extractCloudinaryPublicId($url)
    {
        $path = parse_url($url, PHP_URL_PATH); // Get path from URL
        $filename = pathinfo($path, PATHINFO_FILENAME); // Get the name without extension
        return $filename;
    }
}
