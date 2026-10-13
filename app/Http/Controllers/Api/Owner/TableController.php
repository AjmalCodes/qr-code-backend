<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreTableRequest;
use App\Http\Requests\Owner\UpdateTableRequest;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Services\Owner\TableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TableController extends Controller
{
    use ApiResponses;

    public function __construct(private TableService $tableService) {}

    /**
     * List tables for the authenticated owner's restaurant, each with its QR payload.
     */
    public function index(Request $request): JsonResponse
    {
        $restaurant = $this->tenant($request);
        $tables = $this->tableService->list($restaurant->id);

        return $this->success($tables->map(fn (RestaurantTable $t) => $this->present($t, $restaurant->slug)));
    }

    /**
     * Create a table and generate its printable QR payload.
     *
     * Note: this returns the QR *target URL and token* rather than a rendered
     * PNG/SVG. Generating an actual image server-side needs a QR-code library
     * (e.g. endroid/qr-code) which is not yet a project dependency â€” the
     * returned URL can be rendered into an image with any client-side QR
     * library, or the library can be added on request.
     */
    public function store(StoreTableRequest $request): JsonResponse
    {
        $restaurant = $this->tenant($request);
        $table = $this->tableService->create($restaurant->id, $request->validated());

        return $this->success($this->present($table, $restaurant->slug), 'Table created successfully', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $restaurant = $this->tenant($request);
        $table = $this->tableService->find($id, $restaurant->id);

        if (! $table) {
            return $this->error('Table not found.', null, 404);
        }

        return $this->success($this->present($table, $restaurant->slug));
    }

    public function update(UpdateTableRequest $request, int $id): JsonResponse
    {
        $restaurant = $this->tenant($request);
        $table = $this->tableService->find($id, $restaurant->id);

        if (! $table) {
            return $this->error('Table not found.', null, 404);
        }

        $table = $this->tableService->update($table, $request->validated());

        return $this->success($this->present($table, $restaurant->slug), 'Table updated successfully');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $table = $this->tableService->find($id, $this->tenant($request)->id);

        if (! $table) {
            return $this->error('Table not found.', null, 404);
        }

        $this->tableService->delete($table);

        return $this->success(null, 'Table deleted successfully');
    }

    /**
     * Invalidate a lost/compromised printed QR code by issuing a fresh token.
     */
    public function regenerateQrCode(Request $request, int $id): JsonResponse
    {
        $restaurant = $this->tenant($request);
        $table = $this->tableService->find($id, $restaurant->id);

        if (! $table) {
            return $this->error('Table not found.', null, 404);
        }

        $table = $this->tableService->regenerateToken($table);

        return $this->success($this->present($table, $restaurant->slug), 'QR code regenerated successfully');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(RestaurantTable $table, string $restaurantSlug): array
    {
        return [
            'id' => $table->id,
            'label' => $table->label,
            'qr_token' => $table->qr_token,
            // Convention: the customer QR menu is served at /r/{slug}?table={qr_token}.
            // Any QR-code library can render this URL into a printable code.
            'qr_target_url' => rtrim((string) config('app.url'), '/')."/r/{$restaurantSlug}?table={$table->qr_token}",
            'created_at' => $table->created_at,
        ];
    }

    private function tenant(Request $request): Restaurant
    {
        return $request->attributes->get('tenant_restaurant');
    }
}
