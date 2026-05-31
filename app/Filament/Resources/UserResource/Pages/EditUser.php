<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $currentUser = Filament::auth()->user();

        if (
            $currentUser instanceof User &&
            $currentUser->is($this->getRecord()) &&
            ($data['role'] ?? null) !== User::ROLE_ADMIN
        ) {
            throw ValidationException::withMessages([
                'role' => 'Нельзя снять роль администратора у собственного аккаунта.',
            ]);
        }

        return $data;
    }
}
