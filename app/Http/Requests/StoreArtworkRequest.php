<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreArtworkRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'artist' => 'required|string|max:255',
            'base_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'stock' => 'required|integer|min:0',

            // Multiple image uploads
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',

            'color_variants' => 'array',
            'color_variants.*.color' => 'required|string|max:50',
            'color_variants.*.price_increment' => 'required|numeric|min:0',
            'color_variants.*.stock' => 'required|integer|min:0',
            'size_variants' => 'array',
            'size_variants.*.size' => 'required|string|max:50',
            'size_variants.*.price_increment' => 'required|numeric|min:0',
            'size_variants.*.stock' => 'required|integer|min:0',
        ];
    }
}
