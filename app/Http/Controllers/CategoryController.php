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

        // Store image
        if ($request->hasFile('image')) {
            $path = Storage::disk('s3')->putFile('categories',$request->file('image'));
            dd($path);
            $validated['image_url'] = Storage::url($path);
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
                $oldImagePath = str_replace(Storage::url(''), '', $category->image_url);
                if (Storage::disk('b2')->exists($oldImagePath)) {
                    Storage::disk('b2')->delete($oldImagePath);
                }
            }

            // Store new image
            $path = $request->file('image')->store('categories', 'b2');
            $validated['image_url'] = Storage::url($path);
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
        // Delete associated image
        if ($category->image_url) {
            $imagePath = str_replace(Storage::url(''), '', $category->image_url);
            if (Storage::disk('b2')->exists($imagePath)) {
                Storage::disk('b2')->delete($imagePath);
            }
        }

        $category->delete();
        return response()->json(['message' => 'Category deleted successfully.']);
    }
}
