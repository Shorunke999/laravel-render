<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

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
        Log::info('in the store artwork request class');
        return [
            'name' => 'sometimes|string|max:255',
            //'category_id' => 'sometimes|exists:categories,id',
            'artist' => 'sometimes|string|max:255',
            'base_price' => 'sometimes|numeric|min:0',
            'description' => 'sometimes|string',
            'stock' => 'sometimes|integer|min:0',
             // Multiple image uploads
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',

        ];
    }
}
