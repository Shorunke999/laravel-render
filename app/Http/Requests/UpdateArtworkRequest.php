<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateArtworkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|exists:categories,id',
            'artist' => 'sometimes|string|max:255',
            'base_price' => 'sometimes|numeric|min:0',
            'description' => 'sometimes|string',
            'stock' => 'sometimes|integer|min:0',
             // Multiple image uploads
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            //color variant
            'color_variants' => 'nullable|array',
            'color_variants.*.id' => 'nullable|exists:artwork_color_variants,id', // Needed for updating
            'color_variants.*.color' => 'required_with:color_variants|string|max:50',
            'color_variants.*.price_increment' => 'required_with:color_variants|numeric|min:0',
            'color_variants.*.stock' => 'required_with:color_variants|integer|min:0',

            'size_variants' => 'nullable|array',
            'size_variants.*.id' => 'nullable|exists:artwork_size_variants,id',
            'size_variants.*.size' => 'required_with:size_variants|string|max:50',
            'size_variants.*.price_increment' => 'required_with:size_variants|numeric|min:0',
            'size_variants.*.stock' => 'required_with:size_variants|integer|min:0',
        ];
    }
}
