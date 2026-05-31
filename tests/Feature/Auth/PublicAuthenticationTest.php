<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('login and register pages are available to guests', function () {
    $this->get('/login')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/Login'));

    $this->get('/register')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/Register'));
});

test('dashboard requires authentication', function () {
    $this->get('/dashboard')
        ->assertRedirect('/login');
});

test('user can register through the public form backend', function () {
    $this->post('/register', [
        'name' => 'Neue Nutzerin',
        'email' => 'neue@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'email' => 'neue@example.com',
        'role' => User::ROLE_USER,
    ]);
});

test('user can log in and see the dashboard', function () {
    $user = User::factory()->create([
        'password' => 'password',
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect('/dashboard');

    $this->followRedirects($this->get('/dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('auth.user.email', $user->email));
});
