<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * GET /api/categories — liste des catégories avec leur nombre d'événements.
     */
    public function index(): JsonResponse
    {
        $categories = Category::withCount('events')->orderBy('nom')->get();

        return response()->json(['data' => $categories]);
    }

    /**
     * GET /api/categories/{category} — détails d'une catégorie et ses événements.
     */
    public function show(Category $category): JsonResponse
    {
        return response()->json(['data' => $category->load('events')]);
    }
}
