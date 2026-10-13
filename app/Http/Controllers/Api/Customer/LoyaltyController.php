<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ClaimOfferRequest;
use App\Http\Resources\LoyaltyOfferResource;
use App\Models\Customer;
use App\Models\LoyaltyOffer;
use App\Repositories\LoyaltyOfferRepository;
use App\Services\LoyaltyService;
use App\Services\RestaurantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoyaltyController extends Controller
{
    use ApiResponses;

    public function __construct(
        private RestaurantService $restaurantService,
        private LoyaltyOfferRepository $loyaltyOfferRepository,
        private LoyaltyService $loyaltyService,
    ) {}

    /**
     * Active loyalty offers for a restaurant, shown directly in the QR menu.
     * Identify the restaurant with ?restaurant_slug=.
     */
    public function activeOffers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'restaurant_slug' => ['required', 'string'],
        ]);

        $restaurant = $this->restaurantService->findActiveBySlug($validated['restaurant_slug']);

        if (! $restaurant) {
            return $this->error('Restaurant not found.', null, 404);
        }

        $offers = $this->loyaltyOfferRepository->getActiveForRestaurant($restaurant->id);

        return $this->success(LoyaltyOfferResource::collection($offers));
    }

    /**
     * A verified customer claims an offer, generating a one-time redemption
     * code that staff confirm in person on the Waiter Panel.
     */
    public function claim(ClaimOfferRequest $request): JsonResponse
    {
        $offer = LoyaltyOffer::findOrFail($request->validated('loyalty_offer_id'));

        /** @var Customer $customer */
        $customer = $request->user('customer');

        try {
            $claim = $this->loyaltyService->claim($customer, $offer);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), $e->errors(), 422);
        }

        return $this->success([
            'id' => $claim->id,
            'redemption_code' => $claim->redemption_code,
            'status' => $claim->status->value,
            'offer_title' => $offer->title,
        ], 'Offer claimed. Show this code to staff to redeem it.', 201);
    }
}
