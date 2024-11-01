<?php

namespace App\Http\Controllers;

use App\Integrations\Nordigen\NordigenClient;
use App\Models\NordigenAccount;
use App\Models\NordigenRequisition;
use App\Services\NordigenService;

class PlaygroundController extends Controller
{
    private NordigenService $nordigenService;

    public function __construct(NordigenService $nordigenService)
    {
        $this->nordigenService = $nordigenService;
    }

    public function createRequisition()
    {
        $requisition = $this->nordigenService->newRequisition(
            NordigenClient::SANDBOX_INSTITUTION
        );

        return redirect($requisition->link);
    }

    public function handleRequisition(NordigenRequisition $requisition)
    {
        // TODO Handle the exception properly
        $updated = $this->nordigenService->updateAfterUserConsent($requisition);

        // Show the accounts on the response for debug purposes
        $requisition->load('accounts');

        return response(['updated' => $updated, 'requisition' => $requisition]);
    }

    public function syncAccount(NordigenAccount $account)
    {
        $new = $this->nordigenService->syncAccount($account);
        $all = $account->transactions;

        return response(['new' => $new, 'all' => $all]);
    }

    // TODO Turn this into a job
    public function syncAllAccounts()
    {
        $result = $this->nordigenService->syncAllAccounts();

        return [
            'errors' => $result->getErrorMessagesByAccountId(),
            'success' => $result->getTransactionsByAccountId(),
        ];
    }
}
