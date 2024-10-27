<?php

namespace App\Providers;

use App\Exceptions\SpenderellaConfigException;
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
            $secretId = config('services.nordigen.id');
            $secretKey = config('services.nordigen.key');

            if (! $secretId || ! $secretKey) {
                throw new SpenderellaConfigException('Unset secret values for Nordigen');
            }

            $client = new NordigenClient($secretId, $secretKey);

            return new NordigenService($client);
        });
    }

    public function provides(): array
    {
        return [NordigenService::class];
    }
}
