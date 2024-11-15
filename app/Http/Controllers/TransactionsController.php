<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentMethodSelectionResource;
use App\Http\Resources\TransactionResource;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class TransactionsController extends Controller
{
    public function show()
    {
        $paymentMethods = PaymentMethod::all();
        $transactions = Transaction::with(['paymentMethod'])
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
            'date' => 'required|date',
            'name' => 'required|max:30',
            'category' => 'required|max:20',
            'description' => 'nullable',
            'amount' => 'required|integer',
            'paymentMethod' => 'required',
        ]);

        // Run the built-in validation rules
        $validated = $validator->validate();

        $paymentMethod = PaymentMethod::where('uuid', $validated['paymentMethod'])
            ->first();

        // Run the custom validation rules
        $validated = $validator->after(function ($validator) use ($paymentMethod) {
            // Check if the payment method exists
            if (! $paymentMethod) {
                $validator->errors()->add('paymentMethod', 'This payment method does not exist');

                return;
            }
        })->validate();

        // Create the transaction
        Transaction::create([
            ...$validated,
            'payment_method_id' => $paymentMethod->id,
        ]);

        return to_route('transactions.show');
    }
}
