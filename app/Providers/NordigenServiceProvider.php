<?php

namespace App\Providers;

use App\Integrations\Nordigen\NordigenClient;
use App\Services\NordigenService;
use Illuminate\Support\ServiceProvider;

class NordigenServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(NordigenService::class, function () {
            $client = new NordigenClient(
                config('services.nordigen.id'),
                config('services.nordigen.key'),
            );

            return new NordigenService($client);
        });
    }

    public function provides(): array
    {
        return [NordigenService::class];
    }
}
