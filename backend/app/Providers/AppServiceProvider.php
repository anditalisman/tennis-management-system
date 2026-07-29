<?php

namespace App\Providers;

use App\Models\Role;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        Gate::before(fn ($user) => $user->hasRole(Role::SUPER_ADMIN) ? true : null);

        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));

        // Voucher codes are a brute-forceable secret (fixed/percentage discount) —
        // throttle lookups the same as auth to slow down guessing.
        RateLimiter::for('lookup', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // The gateway webhook is signature-verified, but still capped to blunt
        // flood/DoS attempts against an unauthenticated public endpoint.
        RateLimiter::for('webhook', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }
}
