<?php

namespace App\Services\Owner;

use App\Models\Restaurant;
use App\Repositories\RestaurantRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class RestaurantSettingsService
{
    private const LOGO_DIRECTORY = 'restaurants/logos';

    public function __construct(private RestaurantRepository $restaurantRepository) {}

    public function updateSettings(Restaurant $restaurant, array $data, ?UploadedFile $logo = null): Restaurant
    {
        if ($logo) {
            $data['logo'] = $this->replaceLogo($restaurant, $logo);
        }

        $this->restaurantRepository->update($restaurant, $data);

        return $restaurant->fresh();
    }

    private function replaceLogo(Restaurant $restaurant, UploadedFile $logo): string
    {
        if ($restaurant->logo) {
            Storage::disk('public')->delete($restaurant->logo);
        }

        return $logo->store(self::LOGO_DIRECTORY, 'public');
    }
}
