<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethodType;
use App\Http\Resources\NordigenAccountResource;
use App\Http\Resources\PaymentMethodResource;
use App\Models\NordigenAccount;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function show()
    {
        $linkedAccounts = NordigenAccount::all();
        $paymentMethods = PaymentMethod::with(['nordigenAccount'])->get();

        return Inertia::render('settings/show', [
            'linkedAccounts' => NordigenAccountResource::collection($linkedAccounts),
            'paymentMethodTypes' => PaymentMethodType::human(),
            'paymentMethods' => PaymentMethodResource::collection($paymentMethods),
        ]);
    }

    public function storePaymentMethod(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:20',
            'type' => [
                'required',
                Rule::enum(PaymentMethodType::class),
            ],
            'accountToLink' => 'nullable',
        ]);

        // Run the built-in validation rules
        $validated = $validator->validate();

        $accountToLink = null;
        if ($validated['accountToLink']) {
            // Get the account to link
            $accountToLink = NordigenAccount::where('uuid', $validated['accountToLink'])
                ->with(['paymentMethod'])
                ->first();

            // Run the custom validation rules
            $validated = $validator->after(function ($validator) use ($accountToLink) {
                // Check if the account exists
                if (! $accountToLink) {
                    $validator->errors()->add('accountToLink', 'This account does not exist');

                    return;
                }

                // Check if the account is already linked to a payment method
                if ($accountToLink->paymentMethod) {
                    $validator->errors()->add('accountToLink', 'This account is already linked to another account');

                    return;
                }
            })->validate();
        }

        // Create the payment method and (optionally) link it to the account
        PaymentMethod::create([
            ...$validated,
            'nordigen_account_id' => $accountToLink?->id,
        ]);

        return to_route('settings.show');
    }
}
