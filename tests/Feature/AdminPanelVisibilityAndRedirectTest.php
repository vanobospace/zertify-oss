<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('home scholarly page stays available for authenticated regular users', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_USER,
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Preview/ScholarlyAi')
            ->where('auth.user.email', $user->email));
});

test('regular users are redirected away from the admin panel', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_USER,
    ]);

    $response = $this->actingAs($user)->get('/admin');

    $response->assertRedirect(route('home'));
});

test('verified admins can access the admin panel route', function () {
    $user = User::factory()->admin()->create();

    $response = $this->actingAs($user)->get('/admin');

    $response->assertOk();
});

test('guests are redirected to the admin login screen', function () {
    $response = $this->get('/admin');

    $response->assertRedirect('/admin/login');
});
