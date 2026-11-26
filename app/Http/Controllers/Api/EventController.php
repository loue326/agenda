<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EventController extends Controller
{
    /**
     * GET /api/events — liste paginée, avec recherche et filtre catégorie.
     *
     * Paramètres optionnels : ?search=...&category_id=...&per_page=...
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer|exists:categories,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $events = Event::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('titre', 'like', "%{$search}%"))
            ->when($filters['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->with('category')
            ->orderBy('date', 'asc')
            ->paginate($filters['per_page'] ?? 5)
            ->withQueryString();

        return response()->json($events);
    }

    /**
     * GET /api/events/{event} — détails d'un événement.
     */
    public function show(Event $event): JsonResponse
    {
        return response()->json(['data' => $event->load('category')]);
    }

    /**
     * POST /api/events — crée un événement (répond 201 + Location).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'heure' => 'nullable|date_format:H:i',
            'lieu' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $event = Event::create($validated);

        return response()
            ->json(['data' => $event->load('category')], 201)
            ->header('Location', route('api.events.show', $event));
    }

    /**
     * PUT /api/events/{event} — modifie un événement.
     */
    public function update(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'heure' => 'nullable|date_format:H:i',
            'lieu' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $event->update($validated);

        return response()->json(['data' => $event->load('category')]);
    }

    /**
     * DELETE /api/events/{event} — supprime un événement (répond 204).
     */
    public function destroy(Event $event): Response
    {
        $event->delete();

        return response()->noContent();
    }
}
