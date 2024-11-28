<?php

namespace App\Http\Controllers;

use App\Http\Resources\NordigenTransactionResource;
use App\Http\Resources\TransactionResource;
use App\Models\NordigenTransaction;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class LinkedAccountsController extends Controller
{
    private const TRANSACTION_LINK_KEY = 'transaction-link';

    public function showTransactions()
    {
        $transactions = NordigenTransaction::with([
            'account',
            'linkedTransactions',
        ])
            ->orderByDesc('booking_date')
            ->paginate(50);

        $data = [
            'linkToTransactionUrl' => route('transactions.link'),
            'linkNordigenTransactionToTransactionUrl' => route('transactions.transaction.link', '%uuid%'),

            'transactions' => NordigenTransactionResource::collection($transactions),
        ];

        // Get the transaction to link if one is provided
        $transactionToLink = Session::get(self::TRANSACTION_LINK_KEY);
        if ($transactionToLink) {
            $transaction = Transaction::where('uuid', $transactionToLink)
                ->first();

            $data['transactionToLink'] = TransactionResource::make($transaction);
        }

        return Inertia::render('linked-accounts/transactions/show-all', $data);
    }

    public function linkTransaction(Request $request)
    {
        if (($uuid = $request->input('uuid'))) {
            Session::flash(self::TRANSACTION_LINK_KEY, $uuid);
        }

        return to_route('linked-accounts.transactions.show-all');
    }
}
