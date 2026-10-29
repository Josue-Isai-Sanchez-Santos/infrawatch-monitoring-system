<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
        RateLimiter::for(
            'agent-metrics',
            function (Request $request): array {
                // Hash del token solamente para identificar
                // el contador. No se almacena el token
                // original como clave de caché.
                $tokenFingerprint = hash(
                    'sha256',
                    (string) $request->bearerToken()
                );

                return [
                    // Límite compartido por dirección IP.
                    // También restringe solicitudes sin
                    // token o con tokens inventados.
                    Limit::perMinute(120)
                        ->by('agent-ip:'.($request->ip() ?? 'unknown')),

                    // Límite independiente por token.
                    Limit::perMinute(10)
                        ->by('agent-token:'.$tokenFingerprint),
                ];
            }
        );
    }
}
