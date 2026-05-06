<?php

namespace App\Providers;

use App\Models\ApiClient;
use App\Models\Ticket;
use App\Observers\TicketObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Ticket::observe(TicketObserver::class);

        ResetPassword::createUrlUsing(fn (object $notifiable, string $token) => config('app.frontend_url').'/reset-password?token='.$token.'&email='.urlencode($notifiable->getEmailForPasswordReset())
        );

        RateLimiter::for('api-client', function (Request $request): Limit {
            $client = $request->attributes->get('apiClient');

            $key = $client instanceof ApiClient
                ? "api-client:{$client->id}"
                : 'api-client:anon:'.$request->ip();

            return Limit::perMinute(60)->by($key);
        });
    }
}
