<?php

namespace App\Services\Owner;

use App\Models\Restaurant;
use App\Repositories\RestaurantRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class RestaurantSettingsService
{
    private const LOGO_DIRECTORY = 'restaurants/logos';

    private const BANNER_DIRECTORY = 'restaurants/banners';

    public function __construct(private RestaurantRepository $restaurantRepository) {}

    public function updateSettings(Restaurant $restaurant, array $data, ?UploadedFile $logo = null, ?UploadedFile $banner = null): Restaurant
    {
        if ($logo) {
            $data['logo'] = $this->replaceImage($restaurant->logo, $logo, self::LOGO_DIRECTORY);
        }

        if ($banner) {
            $data['banner'] = $this->replaceImage($restaurant->banner, $banner, self::BANNER_DIRECTORY);
        }

        $this->restaurantRepository->update($restaurant, $data);

        return $restaurant->fresh();
    }

    private function replaceImage(?string $existingPath, UploadedFile $image, string $directory): string
    {
        if ($existingPath) {
            Storage::disk('public')->delete($existingPath);
        }

        return $image->store($directory, 'public');
    }
}
