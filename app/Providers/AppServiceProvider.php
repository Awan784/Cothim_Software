<?php

namespace App\Providers;

use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
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
        Route::bind('order', function ($value) {
            return \App\Models\SalesOrder::query()->findOrFail($value);
        });

        Gate::define('module-access', function (?User $user, string $module, string $action) {
            return $user !== null && $user->canModule($module, $action);
        });

        View::composer('template.*', function ($view) {
            $settings = app(SettingsService::class);
            $pendingSalesOrders = 0;
            if (auth()->guard('web')->check()) {
                $pendingSalesOrders = \App\Models\SalesOrder::query()
                    ->where('status', \App\Models\SalesOrder::STATUS_PENDING)
                    ->count();
            }

            $view->with('shell', [
                'company' => $settings->companyName(),
                'plan' => $settings->plan(),
                'trialDays' => $settings->trialDaysLeft(),
            ]);
            $view->with('pendingSalesOrders', $pendingSalesOrders);
        });
    }
}
