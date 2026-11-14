<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        RateLimiter::for('inquiries', function (Request $request): array {
            $key = hash('sha256', (string) $request->ip());
            $response = function (Request $request, array $headers): Response {
                $request->session()->flashInput($request->only(['name', 'email', 'phone', 'company', 'subject', 'message', 'quantity', 'unit']));

                return response()->view('errors.inquiry-limit', [], 429)->withHeaders($headers);
            };

            return [Limit::perMinute(5)->by('minute:'.$key)->response($response), Limit::perHour(20)->by('hour:'.$key)->response($response)];
        });
    }
}
