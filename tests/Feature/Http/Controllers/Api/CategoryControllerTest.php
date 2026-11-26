<?php

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 401 when the request is not authenticated', function () {
    $this->getJson('/api/categories')->assertUnauthorized();
});

it('returns categories with their event counts', function () {
    $sport = Category::factory()->create(['nom' => 'Sport']);
    Category::factory()->create(['nom' => 'Culture']);
    Event::factory()->count(2)->for($sport)->create();

    $this->actingAs(User::factory()->create())
        ->getJson('/api/categories')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.nom', 'Culture')
        ->assertJsonPath('data.0.events_count', 0)
        ->assertJsonPath('data.1.nom', 'Sport')
        ->assertJsonPath('data.1.events_count', 2);
});

it('returns a category with its events', function () {
    $category = Category::factory()->create();
    Event::factory()->count(2)->for($category)->create();

    $this->actingAs(User::factory()->create())
        ->getJson("/api/categories/{$category->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $category->id)
        ->assertJsonCount(2, 'data.events');
});
