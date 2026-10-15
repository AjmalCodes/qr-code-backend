<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ClaimOfferRequest;
use App\Models\Customer;
use App\Models\LoyaltyOffer;
use App\Repositories\LoyaltyOfferRepository;
use App\Repositories\LoyaltyProgressRepository;
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
        private LoyaltyProgressRepository $loyaltyProgressRepository,
        private LoyaltyService $loyaltyService,
    ) {}

    /**
     * Active loyalty offers for a restaurant, shown directly in the QR menu,
     * each with the authenticated customer's own progress ("2/4 coffees")
     * and whether it's ready to redeem right now. Identify the restaurant
     * with ?restaurant_slug=.
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

        /** @var Customer $customer */
        $customer = $request->user('customer');
        $progressByOffer = $this->loyaltyProgressRepository->getForCustomer($customer->id);

        return $this->success($offers->map(function (LoyaltyOffer $offer) use ($progressByOffer) {
            $progress = min($progressByOffer->get($offer->id)?->progress_count ?? 0, $offer->buy_quantity);

            return [
                'id' => $offer->id,
                'title' => $offer->title,
                'description' => $offer->description,
                'product_id' => $offer->product_id,
                'buy_quantity' => $offer->buy_quantity,
                'get_quantity' => $offer->get_quantity,
                'progress' => $progress,
                'is_redeemable' => $progress >= $offer->buy_quantity,
            ];
        }));
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
            'get_quantity' => $offer->get_quantity,
        ], 'Offer claimed. Show this code to staff to redeem it.', 201);
    }
}
