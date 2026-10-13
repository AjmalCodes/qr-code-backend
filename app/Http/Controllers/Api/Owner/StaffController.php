<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreStaffRequest;
use App\Http\Requests\Owner\UpdateStaffRequest;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\Owner\StaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    use ApiResponses;

    public function __construct(private StaffService $staffService) {}

    /**
     * List waiter/kitchen staff accounts for the authenticated owner's restaurant.
     */
    public function index(Request $request): JsonResponse
    {
        $staff = $this->staffService->list($this->tenant($request)->id);

        return $this->success($staff->map(fn (User $s) => $this->present($s)));
    }

    /**
     * Create a new staff (waiter) login, emailing them their credentials.
     */
    public function store(StoreStaffRequest $request): JsonResponse
    {
        $staff = $this->staffService->create($this->tenant($request), $request->validated());

        return $this->success($this->present($staff), 'Staff account created successfully', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $staff = $this->staffService->find($id, $this->tenant($request)->id);

        if (! $staff) {
            return $this->error('Staff account not found.', null, 404);
        }

        return $this->success($this->present($staff));
    }

    public function update(UpdateStaffRequest $request, int $id): JsonResponse
    {
        $staff = $this->staffService->find($id, $this->tenant($request)->id);

        if (! $staff) {
            return $this->error('Staff account not found.', null, 404);
        }

        $staff = $this->staffService->update($staff, $request->validated());

        return $this->success($this->present($staff), 'Staff account updated successfully');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $staff = $this->staffService->find($id, $this->tenant($request)->id);

        if (! $staff) {
            return $this->error('Staff account not found.', null, 404);
        }

        $this->staffService->delete($staff);

        return $this->success(null, 'Staff account deleted successfully');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(User $staff): array
    {
        return [
            'id' => $staff->id,
            'name' => $staff->name,
            'email' => $staff->email,
            'role' => $staff->role->value,
            'created_at' => $staff->created_at,
        ];
    }

    private function tenant(Request $request): Restaurant
    {
        return $request->attributes->get('tenant_restaurant');
    }
}
