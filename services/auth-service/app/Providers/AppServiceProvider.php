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
        //
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
