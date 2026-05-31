<?php

use App\Http\Controllers\Modules\ShowLesenModuleController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Preview/ScholarlyAi');
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', function () {
        return Inertia::render('Auth/Login');
    })->name('login');

    Route::get('/register', function () {
        return Inertia::render('Auth/Register');
    })->name('register');
});

Route::middleware('auth')->get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

Route::middleware('auth')->group(function (): void {
    Route::get('/modules/lesen', ShowLesenModuleController::class)->name('modules.lesen');

    Route::get('/verify-email', function () {
        return Inertia::render('Auth/VerifyEmail');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('dashboard');
    })->middleware('signed')->name('verification.verify');
});

Route::prefix('preview')->group(function (): void {
    Route::get('/scholarly-ai', function () {
        return Inertia::render('Preview/ScholarlyAi');
    })->name('preview.scholarly-ai');
});
