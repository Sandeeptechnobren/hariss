<?php


namespace App\Providers;


use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;


class AuthServiceProvider extends ServiceProvider
{
    /** @var array<class-string, array<int, string>> */
    protected $policies = [];


    public function boot(): void
    {
        $this->registerPolicies();


        // Register Passport routes. (For Laravel 11/12 + Passport 12, this still works.)
        Passport::routes();


        // Optional: token TTLs
        // Passport::tokensExpireIn(now()->addDays(15));
        // Passport::refreshTokensExpireIn(now()->addDays(30));
        // Passport::personalAccessTokensExpireIn(now()->addMonths(6));
    }
}
