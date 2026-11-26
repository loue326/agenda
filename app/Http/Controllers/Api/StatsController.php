<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    /**
     * GET /api/stats — statistiques du tableau de bord.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'total_events' => Event::count(),
                'total_categories' => Category::count(),
                'upcoming_events' => Event::whereDate('date', '>=', now()->toDateString())->count(),
            ],
        ]);
    }
}
