<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('lesen module page requires authentication', function () {
    $this->get('/modules/lesen')
        ->assertRedirect('/login');
});

test('authenticated user can open the lesen module page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('modules.lesen'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Modules/Lesen')
            ->where('module.title', 'Prüfungsvorbereitung: Lesen')
            ->where('parts.0.key', 'teil1')
            ->where('task.texts.0.id', 'text_a')
            ->where('task.extra_answer.label', 'X')
            ->where('auth.user.email', $user->email));
});
