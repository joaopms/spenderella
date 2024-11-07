<?php

namespace App\Integrations\Nordigen;

use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NordigenClient
{
    public const SANDBOX_INSTITUTION = 'SANDBOXFINANCE_SFIN0000';

    private const CACHE_ACCESS_TOKEN = 'nordigen.access_token';

    private const CACHE_REFRESH_TOKEN = 'nordigen.refresh_token';

    private \Nordigen\NordigenPHP\API\NordigenClient $client;

    public function __construct(string $secretId, string $secretKey, ClientInterface $clientInterface = null)
    {
        $this->client = new \Nordigen\NordigenPHP\API\NordigenClient($secretId, $secretKey, $clientInterface);
        $this->initializeClient();
    }

    public function initializeClient(): void
    {
        $this->prepareAccessToken();
    }

    /**
     * Loads the access token from cache, renewing (or creating) it if necessary
     */
    private function prepareAccessToken(): void
    {
        // Use the cached access token, if available
        if (Cache::has(self::CACHE_ACCESS_TOKEN)) {
            Log::debug('Nordigen access token: HIT');
            $accessToken = Cache::get(self::CACHE_ACCESS_TOKEN);
            $this->client->setAccessToken($accessToken);

            return;
        }

        Log::debug('Nordigen access token: MISS');

        // If the access token is not available, try to use the refresh token to get a new one
        if (Cache::has(self::CACHE_REFRESH_TOKEN)) {
            $refreshToken = Cache::get(self::CACHE_REFRESH_TOKEN);
            Log::debug('Nordigen refresh token: HIT');

            $response = $this->client->refreshAccessToken($refreshToken);

            // Cache the new access token
            $this->cacheNewAccessToken($response);

            return;
        }

        Log::debug('Nordigen refresh token: MISS');

        // If no refresh token is available, create a brand-new access token and cache it
        $response = $this->client->createAccessToken();

        // Cache the new access token
        $this->cacheNewAccessToken($response);

        // Cache the new response token
        $this->cacheNewResponseToken($response);
    }

    /**
     * Caches the access token from a Nordigen token response
     */
    private function cacheNewAccessToken(array $response): void
    {
        $accessToken = $response['access'];
        $accessExpires = $response['access_expires'] - 1000;
        Cache::set(self::CACHE_ACCESS_TOKEN, $accessToken, $accessExpires);
        Log::debug("Nordigen access token: SET $accessExpires");
    }

    /**
     * Caches the response token from a Nordigen token response
     */
    private function cacheNewResponseToken(array $response): void
    {
        $refreshToken = $response['refresh'];
        $refreshExpires = $response['refresh_expires'] - 1000;
        Cache::set(self::CACHE_REFRESH_TOKEN, $refreshToken, $refreshExpires);
        Log::debug("Nordigen refresh token: SET $refreshExpires");
    }

    // End user agreements
    // ---------------------------------------------------------------------------

    public function getEndUserAgreement(string $agreementId): array
    {
        return $this->client->endUserAgreement->getEndUserAgreement($agreementId);
    }

    // TODO Deprecate this method; end user agreements are automatically created if needed when a requisition is created
    public function endUserAgreementCreate(string $institutionId): array
    {
        return $this->client->endUserAgreement->createEndUserAgreement($institutionId);
    }

    // Requisitions
    // ---------------------------------------------------------------------------

    public function requisitionGet(string $id): array
    {
        return $this->client->requisition->getRequisition($id);
    }

    public function requisitionCreate(string $redirectUrl, string $institutionId, string $agreementId, string $reference): array
    {
        return $this->client->requisition->createRequisition($redirectUrl, $institutionId, $agreementId, $reference);
    }

    // Accounts
    // ---------------------------------------------------------------------------

    public function accountGetDetails(string $accountId): array
    {
        return $this->client->account($accountId)->getAccountDetails();
    }

    public function accountGetTransactions(string $accountId, ?string $dateFrom): array
    {
        return $this->client->account($accountId)->getAccountTransactions($dateFrom);
    }
}
