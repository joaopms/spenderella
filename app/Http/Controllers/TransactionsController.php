<?php

namespace App\Http\Controllers;

use App\Http\Resources\NordigenTransactionResource;
use App\Http\Resources\PaymentMethodSelectionResource;
use App\Http\Resources\TransactionResource;
use App\Models\NordigenTransaction;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class TransactionsController extends Controller
{
    private const TRANSACTION_LINK_KEY = 'nordigen-transaction-link';

    public function show(Request $request)
    {
        $paymentMethods = PaymentMethod::all();
        $transactions = Transaction::parent()
            ->with([
                'paymentMethod',
                'nordigenTransaction',
                'splitTransactions' => fn (HasMany $query) => $query->orderBy('parent_transaction_order')]
            )
            ->orderByDesc('date')
            ->get();

        $data = [
            'storeTransactionUrl' => route('transactions.store'),
            'linkTransactionUrl' => route('linked-accounts.transactions.link'),
            'paymentMethods' => PaymentMethodSelectionResource::collection($paymentMethods),
            'transactions' => TransactionResource::collection($transactions),
        ];

        // Get the Nordigen transaction to link if one is provided
        $nordigenTransactionToLink = Session::get(self::TRANSACTION_LINK_KEY);
        if ($nordigenTransactionToLink) {
            $transaction = NordigenTransaction::where('uuid', $nordigenTransactionToLink)
                ->first();

            $data['transactionToLink'] = NordigenTransactionResource::make($transaction);
        }

        return Inertia::render('transactions/show', $data);
    }

    public function linkTransaction(Request $request)
    {
        if (($uuid = $request->input('uuid'))) {
            Session::flash(self::TRANSACTION_LINK_KEY, $uuid);
        }

        return to_route('transactions.show');
    }

    public function linkNordigenTransaction(Request $request, Transaction $transaction)
    {
        // Get the Nordigen transaction
        $nordigenTransaction = NordigenTransaction::where('uuid', $request->input('uuid'))
            ->firstOrFail();

        // TODO Check if we're replacing an already existing link?
        // if ($transaction->nordigenTransaction) { }

        // Link the transaction to the Nordigen transaction
        $transaction->nordigen_transaction_id = $nordigenTransaction->id;
        $transaction->save();

        return to_route('transactions.show');
    }

    public function storeTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transactionToLink' => 'nullable',
            'parentTransaction' => 'nullable',
            'date' => 'required|date',
            'name' => 'required|max:30',
            'category' => 'required_without:parentTransaction|max:20',
            'description' => 'nullable',
            'amount' => 'required|integer',
            'paymentMethod' => 'required',
        ]);

        // Run the built-in validation rules
        $validated = $validator->validate();

        // Validate the payment method
        $paymentMethod = PaymentMethod::where('uuid', $validated['paymentMethod'])
            ->first();

        // Run custom validation rules
        $validated = $validator->after(function (\Illuminate\Validation\Validator $validator) use ($validated, $paymentMethod) {
            // Check if the amount is valid (must be above zero)
            $validator->errors()->addIf(
                abs($validated['amount']) < 1,
                'amount',
                'Amount must be at least one cent'
            );

            // Check if the payment method exists
            $validator->errors()->addIf(
                ! $paymentMethod,
                'paymentMethod',
                'This payment method does not exist'
            );
        })->validate();

        // Save common data
        $transactionData = [
            ...$validated,
            'payment_method_id' => $paymentMethod->id,
        ];

        // Handle split transactions
        if ($validated['parentTransaction']) {
            $parentTransaction = Transaction::where('uuid', $validated['parentTransaction'])
                ->first();

            // Run custom validation rules
            $validator->after(function (\Illuminate\Validation\Validator $validator) use ($parentTransaction) {
                // Check if the parent transaction exists
                if (! $parentTransaction) {
                    $validator->errors()->add(
                        'parentTransaction',
                        'Parent transaction does not exist'
                    );

                    return;
                }

                // Check if the parent transaction is a split transaction
                $validator->errors()->addIf(
                    $parentTransaction->parentTransaction,
                    'parentTransaction',
                    "This transaction is already a split and can't be split further"
                );
            })->validate();

            // Set some fields
            $transactionData['category'] = 'Split';
            $transactionData['parent_transaction_id'] = $parentTransaction->id;
            $transactionData['parent_transaction_order'] = $parentTransaction->splitTransactions()->max('parent_transaction_order') + 1;
        }

        // Handle Nordigen transaction link
        if ($validated['transactionToLink']) {
            $nordigenTransaction = NordigenTransaction::where('uuid', $validated['transactionToLink'])
                ->first();

            // Run custom validation rules
            $validator->after(function (\Illuminate\Validation\Validator $validator) use ($nordigenTransaction) {
                // Check if the Nordigen transaction exists
                $validator->errors()->addIf(
                    ! $nordigenTransaction,
                    'transactionToLink',
                    'The transaction to link does not exists'
                );
            })->validate();

            // Set some fields
            $transactionData['nordigen_transaction_id'] = $nordigenTransaction->id;
        }

        // Create the transaction
        Transaction::create($transactionData);

        return to_route('transactions.show');
    }
}
