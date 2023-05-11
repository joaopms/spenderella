<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Nordigen\NordigenPHP\API\NordigenClient;

class PlaygroundController extends Controller
{
    const SANDBOX_INSTITUTION = 'SANDBOXFINANCE_SFIN0000';

    public function createRequisition()
    {
        $client = $this->getNordigenClient();

        $redirectUrl = config('app.url').'/nordigen/callback';
        $session = $client->initSession(self::SANDBOX_INSTITUTION, $redirectUrl);

        $link = $session['link'];

        echo $link;
    }

    public function handleRequisition(Request $request)
    {
        // Get the requisition ID from the request reference
        // The request reference defaults to the requisition ID, but it can be overwritten when creating the session
        $requisitionId = $request->input('ref');

        $client = $this->getNordigenClient();
        $requisition = $client->requisition->getRequisition($requisitionId);

        $accountId = $requisition['accounts'][0];

        $account = $client->account($accountId);
        $balances = $account->getAccountBalances();
        $transactions = $account->getAccountTransactions();

        return [$balances, $transactions];
    }

    public function getNordigenClient(): NordigenClient
    {
        $client = new NordigenClient(config('services.nordigen.id'), config('services.nordigen.key'));

        // TODO Cache the access and refresh token for later use
        $client->createAccessToken();

        return $client;
    }
}
