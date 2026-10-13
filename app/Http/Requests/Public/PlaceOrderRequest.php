<?php

namespace App\Http\Requests\Public;

use App\Services\RestaurantService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    public function __construct(private RestaurantService $restaurantService)
    {
        parent::__construct();
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $restaurant = $this->restaurantService->findActiveBySlug((string) $this->route('slug'));
        $restaurantId = $restaurant?->id ?? 0;

        return [
            'table_token' => [
                'required',
                'string',
                Rule::exists('restaurant_tables', 'qr_token')->where('restaurant_id', $restaurantId),
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('restaurant_id', $restaurantId),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'items.*.special_instructions' => ['nullable', 'string', 'max:500'],
        ];
    }
}
