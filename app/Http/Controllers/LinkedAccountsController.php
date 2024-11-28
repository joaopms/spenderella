<?php

namespace App\Http\Controllers;

use App\Http\Resources\NordigenTransactionResource;
use App\Models\NordigenTransaction;
use Inertia\Inertia;

class LinkedAccountsController extends Controller
{
    public function showTransactions()
    {
        $transactions = NordigenTransaction::with([
            'account',
            'linkedTransactions',
        ])
            ->orderByDesc('booking_date')
            ->paginate(50);

        return Inertia::render('linked-accounts/transactions/show-all', [
            'transactions' => NordigenTransactionResource::collection($transactions),
        ]);
    }
}
