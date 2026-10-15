<?php

namespace App\Repositories;

use App\Models\QrScan;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class QrScanRepository
{
    public function create(array $data): QrScan
    {
        return QrScan::create($data);
    }

    // See OrderRepository for why these accept CarbonInterface rather than
    // the concrete Illuminate\Support\Carbon (this app defaults to CarbonImmutable).
    public function countForRestaurant(int $restaurantId, ?CarbonInterface $from = null, ?CarbonInterface $to = null): int
    {
        return QrScan::where('restaurant_id', $restaurantId)
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->count();
    }

    /**
     * Scan counts grouped by table, for the per-table QR analytics view.
     *
     * @return Collection<int, object{restaurant_table_id: int, scans: int}>
     */
    public function countsByTableForRestaurant(int $restaurantId, ?CarbonInterface $from = null, ?CarbonInterface $to = null): Collection
    {
        return QrScan::query()
            ->selectRaw('restaurant_table_id, count(*) as scans')
            ->where('restaurant_id', $restaurantId)
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->groupBy('restaurant_table_id')
            ->get();
    }
}
