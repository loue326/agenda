<?php

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 401 when the request is not authenticated', function () {
    $this->getJson('/api/stats')->assertUnauthorized();
});

it('returns the dashboard counters', function () {
    $category = Category::factory()->create();
    Category::factory()->create();
    Event::factory()->count(3)->for($category)->create();

    $this->actingAs(User::factory()->create())
        ->getJson('/api/stats')
        ->assertOk()
        ->assertJsonStructure([
            'data' => ['total_events', 'total_categories', 'upcoming_events'],
        ])
        ->assertJsonPath('data.total_events', 3)
        ->assertJsonPath('data.total_categories', 2)
        ->assertJsonPath('data.upcoming_events', 3);
});
