<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Ignore Passport's default migrations - we have our own
        Passport::ignoreMigrations();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::tokensCan([
            'admin' => 'Full access to all resources', 
            'user' => 'Limited access to resources',
        ]);

        Passport::setDefaultScope([
            'user',
        ]);
    }
}
