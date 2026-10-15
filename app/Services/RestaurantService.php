<?php

namespace App\Services;

use App\Enums\RestaurantStatus;
use App\Enums\UserRole;
use App\Mail\RestaurantCreated;
use App\Models\Restaurant;
use App\Repositories\RestaurantRepository;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RestaurantService
{
    public function __construct(
        private RestaurantRepository $restaurantRepository,
        private UserRepository $userRepository,
    ) {}

    public function createRestaurant(array $data): Restaurant
    {
        // Validate that name and email are provided
        if (empty($data['name']) || empty($data['email'])) {
            throw new \InvalidArgumentException('Name and email are required');
        }

        // Generate temporary password
        $temporaryPassword = Str::random(12);

        // Create restaurant user
        $user = $this->userRepository->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($temporaryPassword),
            'role' => UserRole::Restaurant->value,
        ]);

        // Create restaurant record
        $restaurant = $this->restaurantRepository->create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'slug' => $this->uniqueSlugFor($data['name']),
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'status' => RestaurantStatus::Active->value,
            'temporary_password' => $temporaryPassword,
        ]);

        // Send welcome email with credentials
        Mail::to($restaurant->email)->send(new RestaurantCreated($restaurant, $temporaryPassword));

        return $restaurant;
    }

    public function updateRestaurant(Restaurant $restaurant, array $data): Restaurant
    {
        $this->restaurantRepository->update($restaurant, $data);

        return $restaurant->fresh();
    }

    public function deleteRestaurant(Restaurant $restaurant): bool
    {
        // Delete associated user
        $restaurant->user()->delete();

        // Delete restaurant
        return $this->restaurantRepository->delete($restaurant);
    }

    /**
     * Instantly enable or disable a restaurant. Every tenant-scoped middleware
     * (owner, waiter) and public menu lookup re-checks this status on the next
     * request, so access is blocked immediately — no cache or token revocation needed.
     */
    public function toggleStatus(Restaurant $restaurant): Restaurant
    {
        $newStatus = $restaurant->status === RestaurantStatus::Active
            ? RestaurantStatus::Inactive
            : RestaurantStatus::Active;

        $this->restaurantRepository->update($restaurant, ['status' => $newStatus->value]);

        return $restaurant->fresh('user');
    }

    public function getRestaurantById(int $id): ?Restaurant
    {
        return $this->restaurantRepository->findById($id);
    }

    /**
     * Resolve a restaurant for a public-facing request. Returns null for a
     * disabled restaurant too, so a blocked tenant disappears from the
     * public menu/ordering surface the instant Super Admin disables it.
     */
    public function findActiveBySlug(string $slug): ?Restaurant
    {
        $restaurant = $this->restaurantRepository->findBySlug($slug);

        return $restaurant?->isActive() ? $restaurant : null;
    }

    public function getAllRestaurants(): Collection
    {
        return $this->restaurantRepository->getAll();
    }

    /**
     * @param  array{search?: string, status?: string}  $filters
     */
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->restaurantRepository->search($filters, $perPage);
    }

    private function uniqueSlugFor(string $name): string
    {
        $base = Str::slug($name) ?: 'restaurant';
        $slug = $base;
        $suffix = 1;

        while ($this->restaurantRepository->slugExists($slug)) {
            $slug = "{$base}-".(++$suffix);
        }

        return $slug;
    }
}
