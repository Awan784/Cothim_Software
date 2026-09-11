<?php

namespace App\Providers;

use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        Gate::define('module-access', function (?User $user, string $module, string $action) {
            return $user !== null && $user->canModule($module, $action);
        });

        View::composer('template.*', function ($view) {
            $settings = app(SettingsService::class);

            $view->with('shell', [
                'company' => $settings->companyName(),
                'plan' => $settings->plan(),
                'trialDays' => $settings->trialDaysLeft(),
            ]);
        });
    }
}
