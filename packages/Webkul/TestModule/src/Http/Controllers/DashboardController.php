<?php

namespace TestModule\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use TestModule\Repositories\TestModuleRepository;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected TestModuleRepository $testModuleRepository
    ) {}

    /**
     * Display the dashboard.
     */
    public function index(): View
    {
        $totalHotels = $this->testModuleRepository->getTotalHotels();
        $totalCities = $this->testModuleRepository->getTotalCities();
        $hotelsByCity = $this->testModuleRepository->getHotelsByCity();
        $recentHotels = $this->testModuleRepository->getRecentHotels(6);

        return view('testmodule::dashboard.index', compact(
            'totalHotels',
            'totalCities',
            'hotelsByCity',
            'recentHotels'
        ));
    }

    /**
     * Get dashboard statistics JSON.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'total_hotels' => $this->testModuleRepository->getTotalHotels(),
            'total_cities' => $this->testModuleRepository->getTotalCities(),
            'hotels_by_city' => $this->testModuleRepository->getHotelsByCity(),
            'recent_hotels' => $this->testModuleRepository->getRecentHotels(6),
        ]);
    }
}
