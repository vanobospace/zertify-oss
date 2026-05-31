<?php

namespace App\Providers;

use App\Models\Exam;
use App\Models\Module;
use App\Models\Question;
use App\Models\QuestionAudioAsset;
use App\Models\QuestionGenerationTheme;
use App\Services\ContentCatalogService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureSignedUrls();

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $this->configureDefaults();
        $this->configureLocalContentCatalogAutoExport();
    }

    protected function configureSignedUrls(): void
    {
        VerifyEmail::createUrlUsing(function (object $notifiable): string {
            $canonicalAppUrl = rtrim((string) config('app.url'), '/');
            $canonicalScheme = parse_url($canonicalAppUrl, PHP_URL_SCHEME);

            URL::useOrigin($canonicalAppUrl !== '' ? $canonicalAppUrl : null);
            URL::forceScheme(is_string($canonicalScheme) ? $canonicalScheme : null);

            try {
                return URL::temporarySignedRoute(
                    'verification.verify',
                    now()->addMinutes((int) config('auth.verification.expire', 60)),
                    [
                        'id' => $notifiable->getKey(),
                        'hash' => sha1($notifiable->getEmailForVerification()),
                    ],
                );
            } finally {
                URL::forceScheme(null);
                URL::useOrigin(null);
            }
        });
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    protected function configureLocalContentCatalogAutoExport(): void
    {
        if (! $this->app->isLocal() || $this->app->runningUnitTests()) {
            return;
        }

        $exportCatalog = function (): void {
            if (ContentCatalogService::isSyncInProgress()) {
                return;
            }

            app(ContentCatalogService::class)->exportCatalog();
        };

        foreach ([Exam::class, Module::class, Question::class, QuestionAudioAsset::class, QuestionGenerationTheme::class] as $modelClass) {
            $modelClass::saved($exportCatalog);
            $modelClass::deleted($exportCatalog);
        }
    }
}
