<?php

namespace App\Integrations\Nordigen;

use App\Models\NordigenAgreement;
use App\Models\NordigenRequisition;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NordigenClient extends \Nordigen\NordigenPHP\API\NordigenClient
{
    public const SANDBOX_INSTITUTION = 'SANDBOXFINANCE_SFIN0000';

    private const NORDIGEN_ACCESS_TOKEN = 'nordigen.access_token';

    private const NORDIGEN_REFRESH_TOKEN = 'nordigen.refresh_token';

    /**
     * Initialize the client with values from the config and loads it with an access token
     */
    public function __construct(string $secretId = null, string $secretKey = null, ClientInterface $client = null)
    {
        parent::__construct(
            $secretId ?? config('services.nordigen.id'),
            $secretKey ?? config('services.nordigen.key'),
            $client
        );

        $this->initializeClient();
    }

    private function initializeClient(): void
    {
        $this->prepareAccessToken();
    }

    /**
     * Loads the access token from cache, renewing (or creating) it if necessary
     */
    private function prepareAccessToken(): void
    {
        // Use the cached access token, if available
        if (Cache::has(self::NORDIGEN_ACCESS_TOKEN)) {
            Log::debug('Nordigen access token: HIT');
            $accessToken = Cache::get(self::NORDIGEN_ACCESS_TOKEN);
            $this->setAccessToken($accessToken);

            return;
        }

        Log::debug('Nordigen access token: MISS');

        // If the access token is not available, try to use the refresh token to get a new one
        if (Cache::has(self::NORDIGEN_REFRESH_TOKEN)) {
            $refreshToken = Cache::get(self::NORDIGEN_REFRESH_TOKEN);
            Log::debug('Nordigen refresh token: HIT');
            $response = $this->refreshAccessToken($refreshToken);

            // Cache the new access token
            $this->cacheNewAccessToken($response);

            return;
        }

        Log::debug('Nordigen refresh token: MISS');

        // If no refresh token is available, create a brand-new access token and cache it
        $response = $this->createAccessToken();

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
        Cache::set(self::NORDIGEN_ACCESS_TOKEN, $accessToken, $accessExpires);
        Log::debug("Nordigen access token: SET $accessExpires");
    }

    /**
     * Caches the response token from a Nordigen token response
     */
    private function cacheNewResponseToken(array $response): void
    {
        $refreshToken = $response['refresh'];
        $refreshExpires = $response['refresh_expires'] - 1000;
        Cache::set(self::NORDIGEN_REFRESH_TOKEN, $refreshToken, $refreshExpires);
        Log::debug("Nordigen refresh token: SET $refreshExpires");
    }

    /**
     * Creates a Nordigen requisition, saves it to the database, and returns it
     */
    public function newRequisition(): NordigenRequisition
    {
        $institutionId = NordigenClient::SANDBOX_INSTITUTION;

        // Create the end user agreement
        $agreementData = $this->endUserAgreement->createEndUserAgreement($institutionId);

        DB::beginTransaction();

        $agreement = NordigenAgreement::create([
            'nordigen_id' => $agreementData['id'],
            'institution_id' => $agreementData['institution_id'],
            'nordigen_created_at' => $agreementData['created'],
        ]);

        // Create the requisition
        $redirectUrl = config('app.url').'/nordigen/callback';
        $agreementId = $agreement->nordigen_id;

        $requisition = $agreement->requisition()->create();
        $requisitionReference = $requisition->uuid;

        $requisitionData = $this->requisition->createRequisition(
            $redirectUrl,
            $institutionId,
            $agreementId,
            $requisitionReference
        );

        $requisition->update([
            'nordigen_id' => $requisitionData['id'],
            'link' => $requisitionData['link'],
            'nordigen_created_at' => $requisitionData['created'],
        ]);

        DB::commit();

        return $requisition;
    }
}
