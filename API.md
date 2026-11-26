# API REST — mini-agenda

Ce projet expose ses données en **API JSON**, consommée ensuite par le front-end
(JavaScript `fetch`) et utilisable avec **Postman**, **curl** ou toute autre application.

## Principes

| Sujet | Comportement |
|---|---|
| Base URL | `http://localhost:8000/api` |
| Format | JSON (`Accept: application/json`) |
| Authentification | Session du navigateur (cookie) — être connecté, sinon `401 {"message":"Unauthenticated."}` |
| CSRF | Header `X-CSRF-TOKEN` requis pour `POST` / `PUT` / `DELETE` (token fourni par le meta tag du layout) |
| Validation | Échec → `422` avec `{ "message": ..., "errors": { "champ": ["message"] } }` |
| Pagination | `?page=1` et `?per_page=5` (max 100), réponse type `LengthAwarePaginator` |

Fichiers clés :

- Routes : `routes/api.php` (enregistrées dans `bootstrap/app.php`)
- Contrôleurs : `app/Http/Controllers/Api/{EventController, CategoryController, StatsController}.php`
- Client JS consommé par les vues : `public/js/api.js` (`window.apiClient`)
- Tests : `tests/Feature/Http/Controllers/Api/`

## Endpoints

### Événements

| Méthode | URI | Description | Réponse |
|---|---|---|---|
| `GET` | `/api/events` | Liste paginée. Filtres : `?search=...&category_id=...&per_page=...` | `200` |
| `GET` | `/api/events/{id}` | Détails d'un événement (`data.category` inclus) | `200` |
| `POST` | `/api/events` | Création (`titre`, `date`, `lieu` requis) | `201` + header `Location` |
| `PUT` | `/api/events/{id}` | Modification | `200` |
| `DELETE` | `/api/events/{id}` | Suppression | `204` (sans contenu) |

### Catégories

| Méthode | URI | Description | Réponse |
|---|---|---|---|
| `GET` | `/api/categories` | Liste avec `events_count` (tri par nom) | `200` |
| `GET` | `/api/categories/{id}` | Catégorie + ses événements | `200` |

### Statistiques

| Méthode | URI | Description | Réponse |
|---|---|---|---|
| `GET` | `/api/stats` | `total_events`, `total_categories`, `upcoming_events` | `200` |

## Exemples avec curl

> En terminal, ajoute `-b cookies.txt` / `-c cookies.txt` pour conserver la session,
> ou connecte-toi d'abord sur `/login` depuis le même navigateur.

```bash
# Liste avec recherche et filtre
curl "http://localhost:8000/api/events?search=Réunion&category_id=1&per_page=5"

# Statistiques
curl http://localhost:8000/api/stats

# Création (le token CSRF provient du meta tag "csrf-token" du HTML)
curl -X POST http://localhost:8000/api/events \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: <token>" \
  -d '{"titre":"Réunion","date":"2026-12-01","lieu":"Salle 1"}'

# Modification
curl -X PUT http://localhost:8000/api/events/1 \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: <token>" \
  -d '{"titre":"Réunion (maj)","date":"2026-12-01","lieu":"Salle 1"}'

# Suppression
curl -X DELETE http://localhost:8000/api/events/1 \
  -H "Accept: application/json" -H "X-CSRF-TOKEN: <token>"
```

## Consommation côté front-end

Le layout `resources/views/layouts/app.blade.php` charge `public/js/api.js`, qui
expose `window.apiClient` (`get`, `post`, `put`, `delete`) : en-têtes JSON + CSRF,
session incluse automatiquement, décodage JSON et gestion des erreurs (redirection
vers `/login` si `401`).

Deux pages consomment l'API :

- **Tableau de bord** (`events/dashboard.blade.php`) : les compteurs sont actualisés
  via `GET /api/stats` au chargement et avec le bouton « Recharger via l'API ».
- **Liste des événements** (`events/index.blade.php`) : la recherche, le filtre par
  catégorie, la pagination et la suppression passent par `GET/DELETE /api/events`
  (badge « ✓ Données chargées via l'API REST »). Sans JavaScript, le rendu serveur
  Blade classique reste utilisé (amélioration progressive).

## Tests

```bash
php artisan test tests/Feature/Http/Controllers/Api
```

17 tests couvrent l'authentification (`401`), la liste, la pagination, les filtres,
la validation (`422`), la création (`201`), la modification, la suppression (`204`)
et les statistiques.
