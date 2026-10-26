<?php

namespace App\Providers;

use App\Contracts\Authenticator;
use App\Models\RoleAssignment;
use App\Policies\RoleAssignmentPolicy;
use App\Services\Auth\DevAuthenticator;
use App\Services\Auth\LdapAuthenticator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(Authenticator::class, function ($app) {
            $mode = config('auth.auth_mode', 'dev');

            return match ($mode) {
                'ldap' => new LdapAuthenticator(),
                'dev' => new DevAuthenticator(
                    devPassword: (string) config('auth.dev_password', 'secret123')
                ),
                default => throw new InvalidArgumentException("Unsupported AUTH_MODE: [{$mode}]"),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(RoleAssignment::class, RoleAssignmentPolicy::class);
        Gate::policy(\App\Models\Indent::class, \App\Policies\IndentPolicy::class);
        Paginator::defaultView('vendor.pagination.default');

        if (config('app.env') === 'production' && config('auth.auth_mode') === 'dev') {
            throw new RuntimeException('FATAL CONFIGURATION ERROR: AUTH_MODE cannot be set to [dev] in production.');
        }
    }
}
