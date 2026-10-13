<?php

namespace App\Http\Requests\Owner;

use App\Enums\ProductStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isRestaurant() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $restaurantId = $this->user()?->restaurant?->id;

        return [
            'category_id' => [
                'sometimes',
                'nullable',
                Rule::exists('categories', 'id')->where('restaurant_id', $restaurantId),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:999999.99'],
            'image' => ['sometimes', 'nullable', 'image', 'max:2048'],
            'status' => ['sometimes', Rule::enum(ProductStatus::class)],
        ];
    }
}
