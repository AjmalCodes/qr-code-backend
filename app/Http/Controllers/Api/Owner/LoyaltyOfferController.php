<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreLoyaltyOfferRequest;
use App\Http\Requests\Owner\UpdateLoyaltyOfferRequest;
use App\Http\Resources\LoyaltyOfferResource;
use App\Models\Restaurant;
use App\Services\Owner\LoyaltyOfferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyOfferController extends Controller
{
    use ApiResponses;

    public function __construct(private LoyaltyOfferService $loyaltyOfferService) {}

    /**
     * All loyalty offers for the authenticated owner's restaurant, paginated
     * via ?per_page= (defaults to 15, max 100).
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $offers = $this->loyaltyOfferService->list($this->tenant($request)->id, $filters['per_page'] ?? 15);

        return $this->successPaginated(LoyaltyOfferResource::collection($offers), $offers);
    }

    public function store(StoreLoyaltyOfferRequest $request): JsonResponse
    {
        $offer = $this->loyaltyOfferService->create($this->tenant($request)->id, $request->validated());

        return $this->success(new LoyaltyOfferResource($offer), 'Loyalty offer created successfully', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $offer = $this->loyaltyOfferService->find($id, $this->tenant($request)->id);

        if (! $offer) {
            return $this->error('Loyalty offer not found.', null, 404);
        }

        return $this->success(new LoyaltyOfferResource($offer));
    }

    public function update(UpdateLoyaltyOfferRequest $request, int $id): JsonResponse
    {
        $offer = $this->loyaltyOfferService->find($id, $this->tenant($request)->id);

        if (! $offer) {
            return $this->error('Loyalty offer not found.', null, 404);
        }

        $offer = $this->loyaltyOfferService->update($offer, $request->validated());

        return $this->success(new LoyaltyOfferResource($offer), 'Loyalty offer updated successfully');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $offer = $this->loyaltyOfferService->find($id, $this->tenant($request)->id);

        if (! $offer) {
            return $this->error('Loyalty offer not found.', null, 404);
        }

        $this->loyaltyOfferService->delete($offer);

        return $this->success(null, 'Loyalty offer deleted successfully');
    }

    public function toggleActive(Request $request, int $id): JsonResponse
    {
        $offer = $this->loyaltyOfferService->find($id, $this->tenant($request)->id);

        if (! $offer) {
            return $this->error('Loyalty offer not found.', null, 404);
        }

        $offer = $this->loyaltyOfferService->toggleActive($offer);

        return $this->success(new LoyaltyOfferResource($offer), 'Loyalty offer status updated successfully');
    }

    private function tenant(Request $request): Restaurant
    {
        return $request->attributes->get('tenant_restaurant');
    }
}
