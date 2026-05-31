<?php

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('allows verified admins to access the admin panel', function () {
    $user = User::factory()->admin()->create();

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
});

it('prevents regular users from accessing the admin panel', function () {
    $user = User::factory()->create();

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});

it('lists users in the admin panel', function () {
    $admin = User::factory()->admin()->create();
    $managedUser = User::factory()->create([
        'name' => 'Managed User',
        'email' => 'managed@example.com',
    ]);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$admin, $managedUser]);
});

it('allows admins to update a user role from the admin panel', function () {
    $admin = User::factory()->admin()->create();
    $managedUser = User::factory()->create([
        'role' => User::ROLE_USER,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $managedUser->getRouteKey()])
        ->fillForm([
            'role' => User::ROLE_ADMIN,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertRedirect(UserResource::getUrl('index'));

    expect($managedUser->fresh()->role)->toBe(User::ROLE_ADMIN);
});

it('prevents admins from removing their own admin role', function () {
    $admin = User::factory()->admin()->create();

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
        ->assertFormFieldIsDisabled('role')
        ->fillForm([
            'role' => User::ROLE_USER,
        ])
        ->call('save')
        ->assertHasErrors(['role']);

    expect($admin->fresh()->role)->toBe(User::ROLE_ADMIN);
});
