<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('home page renders the scholarly main page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Preview/ScholarlyAi'));
});

test('home page stays available for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Preview/ScholarlyAi')
            ->where('auth.user.email', $user->email));
});

test('login route redirects to the admin login screen', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/Login'));
});

test('preview mockup routes are available', function () {
    $this->get('/preview/scholarly-ai')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Preview/ScholarlyAi'));
});
