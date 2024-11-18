<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentMethodSelectionResource;
use App\Http\Resources\TransactionResource;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class TransactionsController extends Controller
{
    public function show()
    {
        $paymentMethods = PaymentMethod::all();
        $transactions = Transaction::parent()
            ->with([
                'paymentMethod',
                'splitTransactions' => fn (HasMany $query) => $query->orderBy('parent_transaction_order')]
            )
            ->orderByDesc('date')
            ->get();

        return Inertia::render('transactions/show', [
            'paymentMethods' => PaymentMethodSelectionResource::collection($paymentMethods),
            'transactions' => TransactionResource::collection($transactions),
        ]);
    }

    public function storeTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
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

        $paymentMethod = PaymentMethod::where('uuid', $validated['paymentMethod'])
            ->first();

        // Run custom validation rules
        $validated = $validator->after(function (\Illuminate\Validation\Validator $validator) use ($validated, $paymentMethod) {
            if (abs($validated['amount']) < 1) {
                $validator->errors()->add('amount', 'Amount must be at least one cent');

                return;
            }

            // Check if the payment method exists
            if (! $paymentMethod) {
                $validator->errors()->add('paymentMethod', 'This payment method does not exist');

                return;
            }
        })->validate();

        $transactionData = [
            ...$validated,
            'payment_method_id' => $paymentMethod->id,
        ];

        // Handle split transactions
        if ($validated['parentTransaction']) {
            $parentTransaction = Transaction::where('uuid', $validated['parentTransaction'])
                ->first();

            // Run custom validation rules
            $validated = $validator->after(function (\Illuminate\Validation\Validator $validator) use ($parentTransaction) {
                // Check if the parent transaction exists
                if (! $parentTransaction) {
                    $validator->errors()->add('parentTransaction', 'Parent transaction does not exists');

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

        // Create the transaction
        Transaction::create($transactionData);

        return to_route('transactions.show');
    }
}
