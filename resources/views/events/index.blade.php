@extends('layouts.app')

@section('title', 'Événements')

@section('content')
<h1>Liste des événements</h1>

<form class="filters" id="events-filter-form" action="{{ route('events.index') }}" method="GET">
    <input type="text" name="search" placeholder="Rechercher un événement..." value="{{ $search ?? '' }}">
    <select name="category_id">
        <option value="">Toutes les catégories</option>
        @foreach($categories as $category)
            <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->nom }}</option>
        @endforeach
    </select>
    <button type="submit">Rechercher / filtrer</button>
    <a href="{{ route('events.index') }}">Réinitialiser</a>
</form>

<p id="api-status" class="api-status" role="status" aria-live="polite"></p>

<section class="stats">
    <p>Total des événements : {{ $totalEvents }}</p>
    <p>Événements à venir : {{ $upcomingEvents }}</p>
</section>

<p class="page-actions">
    <a class="button-link" href="{{ route('events.create') }}">Ajouter un événement</a>
    <a class="button-link secondary-link" href="{{ route('categories.index') }}">Gérer les catégories</a>
</p>

<table>
    <thead><tr><th>Titre</th><th>Date</th><th>Heure</th><th>Lieu</th><th>Catégorie</th><th>Statut</th><th>Actions</th></tr></thead>
    <tbody id="events-body">
        @forelse($events as $event)
            <tr>
                <td>{{ $event->titre }}</td>
                <td>{{ $event->date }}</td>
                <td>{{ $event->heure }}</td>
                <td>{{ $event->lieu }}</td>
                <td>{{ $event->category?->nom ?? 'Sans catégorie' }}</td>
                <td>
                    @if($event->date >= date('Y-m-d'))<span class="upcoming">À venir</span>
                    @else<span class="finished">Terminé</span>@endif
                </td>
                <td class="actions">
                    <a href="{{ route('events.show', $event) }}">Voir</a>
                    <a href="{{ route('events.edit', $event) }}">Modifier</a>
                    <form class="inline-form" action="{{ route('events.destroy', $event) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('Confirmer la suppression ?')">Supprimer</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7">Aucun événement trouvé.</td></tr>
        @endforelse
    </tbody>
</table>

<div id="events-pagination">{{ $events->links() }}</div>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('events-filter-form');
    const tbody = document.getElementById('events-body');
    const pagination = document.getElementById('events-pagination');
    const status = document.getElementById('api-status');

    let currentPage = Number(new URLSearchParams(window.location.search).get('page')) || 1;

    // Date du jour au format Y-m-d (comme le stocke la base de données).
    const now = new Date();
    const today = [
        now.getFullYear(),
        String(now.getMonth() + 1).padStart(2, '0'),
        String(now.getDate()).padStart(2, '0'),
    ].join('-');

    function escapeHtml(value) {
        // Échappement DOM : insensible aux entités HTML mal transmises.
        const div = document.createElement('div');
        div.textContent = String(value ?? '');

        return div.innerHTML;
    }

    function setStatus(message, isError = false) {
        status.textContent = message;
        status.classList.toggle('is-error', isError);
    }

    function renderRow(event) {
        const category = event.category ? escapeHtml(event.category.nom) : '';
        const statusLabel = event.date >= today
            ? '<span class="upcoming">À venir</span>'
            : '<span class="finished">Terminé</span>';

        return `<tr>
            <td>${escapeHtml(event.titre)}</td>
            <td>${escapeHtml(event.date)}</td>
            <td>${escapeHtml(event.heure)}</td>
            <td>${escapeHtml(event.lieu)}</td>
            <td>${category || 'Sans catégorie'}</td>
            <td>${statusLabel}</td>
            <td class="actions">
                <a href="/events/${event.id}">Voir</a>
                <a href="/events/${event.id}/edit">Modifier</a>
                <button type="button" data-delete-id="${event.id}">Supprimer</button>
            </td>
        </tr>`;
    }

    function renderPagination(page) {
        if (!page.last_page || page.last_page <= 1) {
            pagination.innerHTML = '';
            return;
        }

        const previous = page.current_page > 1
            ? `<button type="button" data-goto="${page.current_page - 1}">« Précédent</button>`
            : '';
        const next = page.current_page < page.last_page
            ? `<button type="button" data-goto="${page.current_page + 1}">Suivante »</button>`
            : '';

        pagination.innerHTML = `<div class="api-pagination">
            ${previous}
            <span>Page ${page.current_page} / ${page.last_page} — ${page.total} événement(s)</span>
            ${next}
        </div>`;
    }

    // Charge les événements via l'API GET /api/events.
    async function loadEvents(page = 1) {
        currentPage = page;

        const params = new URLSearchParams();
        const search = form.elements.search.value.trim();
        const categoryId = form.elements.category_id.value;

        if (search) params.set('search', search);
        if (categoryId) params.set('category_id', categoryId);
        params.set('page', String(page));

        setStatus('Chargement via l\'API GET /api/events…');

        try {
            const result = await apiClient.get(`/api/events?${params.toString()}`);
            if (!result) return; // 401 : déjà redirigé vers /login par apiClient.

            tbody.innerHTML = result.data.length
                ? result.data.map(renderRow).join('')
                : '<tr><td colspan="7">Aucun événement trouvé.</td></tr>';

            renderPagination(result);
            window.history.replaceState(null, '', `${window.location.pathname}?${params.toString()}`);
            setStatus(`✓ Données chargées via l'API REST — page ${result.current_page}/${result.last_page} (${result.total} résultat(s))`);
        } catch (error) {
            setStatus(`Erreur API : ${error.message}`, true);
        }
    }

    // Recherche / filtre : la page ne se recharge pas, l'API répond en JSON.
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        loadEvents(1);
    });

    form.elements.category_id.addEventListener('change', () => loadEvents(1));

    // Pagination dynamique.
    pagination.addEventListener('click', (event) => {
        const page = event.target.dataset.goto;
        if (page) loadEvents(Number(page));
    });

    // Suppression via l'API DELETE /api/events/{id}.
    tbody.addEventListener('click', async (event) => {
        const id = event.target.dataset.deleteId;
        if (!id || !window.confirm('Confirmer la suppression ?')) return;

        try {
            await apiClient.delete(`/api/events/${id}`);
            await loadEvents(currentPage);
        } catch (error) {
            setStatus(`Erreur API : ${error.message}`, true);
        }
    });
})();
</script>
@endpush
