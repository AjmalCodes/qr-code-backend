<?php

namespace App\Http\Controllers\Api\Waiter;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoyaltyController extends Controller
{
    use ApiResponses;

    public function __construct(private LoyaltyService $loyaltyService) {}

    /**
     * Staff confirms a customer's one-time redemption code at the table.
     */
    public function verifyRedemption(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        try {
            $claim = $this->loyaltyService->verify(
                $this->tenant($request),
                $validated['code'],
                $request->user()->id,
            );
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), $e->errors(), 422);
        }

        return $this->success([
            'id' => $claim->id,
            'status' => $claim->status->value,
            'offer_title' => $claim->offer->title,
            'customer_name' => $claim->customer->name,
            'verified_at' => $claim->verified_at,
        ], 'Redemption verified successfully');
    }

    private function tenant(Request $request): Restaurant
    {
        return $request->attributes->get('tenant_restaurant');
    }
}
