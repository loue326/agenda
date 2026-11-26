<?php

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 401 when the request is not authenticated', function (string $method, string $uri) {
    $this->{$method.'Json'}($uri)->assertUnauthorized();
})->with([
    'GET /api/events' => ['get', '/api/events'],
    'POST /api/events' => ['post', '/api/events'],
]);

// La résolution du modèle ({event}) a lieu avant le middleware "auth",
// donc l'événement doit exister pour que l'authentification soit refusée (401).
it('returns 401 when deleting an event without a session', function () {
    $event = Event::factory()->create();

    $this->deleteJson("/api/events/{$event->id}")->assertUnauthorized();
});

it('returns a single event with its category', function () {
    $category = Category::factory()->create(['nom' => 'Sport']);
    $event = Event::factory()->create(['category_id' => $category->id]);

    $this->actingAs(User::factory()->create())
        ->getJson("/api/events/{$event->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $event->id)
        ->assertJsonPath('data.category.nom', 'Sport');
});

it('returns events as a paginated json list', function () {
    Event::factory()->count(2)->create();

    $this->actingAs(User::factory()->create())
        ->getJson('/api/events')
        ->assertOk()
        ->assertJsonStructure([
            'current_page',
            'last_page',
            'total',
            'data' => [['id', 'titre', 'date', 'heure', 'lieu', 'category']],
        ])
        ->assertJsonCount(2, 'data');
});

it('paginates the event list with per_page', function () {
    Event::factory()->count(3)->create();

    $this->actingAs(User::factory()->create())
        ->getJson('/api/events?per_page=2&page=2')
        ->assertOk()
        ->assertJsonPath('current_page', 2)
        ->assertJsonPath('last_page', 2)
        ->assertJsonCount(1, 'data');
});

it('filters events by search term', function () {
    Event::factory()->create(['titre' => 'Réunion du conseil']);
    Event::factory()->create(['titre' => 'Atelier de couture']);

    $this->actingAs(User::factory()->create())
        ->getJson('/api/events?search=Atelier')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.titre', 'Atelier de couture');
});

it('filters events by category', function () {
    $conference = Category::factory()->create(['nom' => 'Conférence']);
    $workshop = Category::factory()->create(['nom' => 'Atelier']);
    Event::factory()->create(['category_id' => $conference->id]);
    Event::factory()->create(['category_id' => $workshop->id]);

    $this->actingAs(User::factory()->create())
        ->getJson('/api/events?category_id='.$conference->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.category.id', $conference->id);
});

it('creates an event', function () {
    $category = Category::factory()->create();
    $payload = [
        'titre' => 'Séance de yoga',
        'description' => 'Séance au parc.',
        'date' => '2027-03-15',
        'heure' => '18:30',
        'lieu' => 'Salle 2',
        'category_id' => $category->id,
    ];

    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/events', $payload);

    $response
        ->assertCreated()
        ->assertJsonPath('data.titre', 'Séance de yoga')
        ->assertJsonPath('data.category.id', $category->id);

    $this->assertDatabaseHas('events', ['titre' => 'Séance de yoga', 'lieu' => 'Salle 2']);
    $response->assertHeader('Location', route('api.events.show', Event::sole()));
});

it('rejects an invalid event payload', function () {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/events', ['titre' => '', 'date' => 'pas-une-date'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['titre', 'date', 'lieu']);
});

it('updates an event', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->create())
        ->putJson("/api/events/{$event->id}", [
            'titre' => 'Nouveau titre',
            'date' => $event->date,
            'lieu' => $event->lieu,
        ])
        ->assertOk()
        ->assertJsonPath('data.titre', 'Nouveau titre');

    $this->assertDatabaseHas('events', ['id' => $event->id, 'titre' => 'Nouveau titre']);
});

it('deletes an event', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->create())
        ->deleteJson("/api/events/{$event->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('events', ['id' => $event->id]);
});
