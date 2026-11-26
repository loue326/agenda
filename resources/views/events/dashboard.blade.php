@extends('layouts.app')

@section('title', 'Tableau de bord')

@section('content')
<h1>Tableau de bord</h1>
<section class="stats dashboard-stats">
    <article><h2>Total événements</h2><p data-stat="total_events">{{ $totalEvents }}</p></article>
    <article><h2>Total catégories</h2><p data-stat="total_categories">{{ $totalCategories }}</p></article>
    <article><h2>Événements à venir</h2><p data-stat="upcoming_events">{{ $upcomingEvents }}</p></article>
</section>
<p class="page-actions">
    <button type="button" id="refresh-stats">Recharger via l'API</button>
    <span id="stats-status" class="api-status" role="status" aria-live="polite"></span>
</p>
<a class="button-link" href="{{ route('events.index') }}">Voir les événements</a>
@endsection

@push('scripts')
<script>
(() => {
    const status = document.getElementById('stats-status');

    // Charge les statistiques via l'API GET /api/stats et met à jour les cartes.
    async function loadStats() {
        status.classList.remove('is-error');
        status.textContent = 'Chargement via l\'API GET /api/stats…';

        try {
            const result = await apiClient.get('/api/stats');
            if (!result) return; // 401 : déjà redirigé vers /login par apiClient.

            Object.entries(result.data).forEach(([key, value]) => {
                const cell = document.querySelector(`[data-stat="${key}"]`);
                if (cell) cell.textContent = value;
            });

            status.textContent = `✓ Actualisé via l'API à ${new Date().toLocaleTimeString('fr-FR')}`;
        } catch (error) {
            status.textContent = `Erreur API : ${error.message}`;
            status.classList.add('is-error');
        }
    }

    document.getElementById('refresh-stats').addEventListener('click', loadStats);

    loadStats();
})();
</script>
@endpush
