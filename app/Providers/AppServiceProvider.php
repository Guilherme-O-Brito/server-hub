<?php

namespace App\Providers;

use App\Models\MinecraftServer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        Relation::enforceMorphMap([
            MinecraftServer::MORPH_TYPE => MinecraftServer::class,
        ]);

        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower(trim((string) $request->input('email')));

            $cfIp = trim((string) $request->header('CF-Connecting-IP'));

            $ip = filter_var($cfIp, FILTER_VALIDATE_IP) ? $cfIp : $request->ip();

            return Limit::perMinute(5)->by($email.'|'.$ip);
        });
    }
}
