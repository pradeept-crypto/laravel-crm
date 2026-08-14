<?php

namespace TestModule\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use TestModule\Contracts\TestModule;
use Webkul\Core\Eloquent\Repository;

class TestModuleRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return TestModule::class;
    }

    /**
     * Get total hotels count.
     */
    public function getTotalHotels(): int
    {
        return $this->model->count();
    }

    /**
     * Get total distinct cities count.
     */
    public function getTotalCities(): int
    {
        return $this->model->distinct('city')->count('city');
    }

    /**
     * Get hotels count grouped by city.
     */
    public function getHotelsByCity(): Collection
    {
        return $this->model
            ->select('city', DB::raw('count(*) as count'))
            ->groupBy('city')
            ->orderByDesc('count')
            ->get();
    }

    /**
     * Get recent hotels.
     */
    public function getRecentHotels(int $limit = 5): Collection
    {
        return $this->model
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();
    }
}
