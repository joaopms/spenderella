<?php

use App\Http\Controllers\LinkedAccountsController;
use App\Http\Controllers\PlaygroundController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TransactionsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/settings', [SettingsController::class, 'show'])->name('settings.show');
Route::post('/settings/payment-method', [SettingsController::class, 'storePaymentMethod']);

Route::get('/transactions', [TransactionsController::class, 'show'])->name('transactions.show');
Route::post('/transactions', [TransactionsController::class, 'storeTransaction'])->name('transactions.store');
// Called by a Nordigen transaction
Route::post('/transactions/link', [TransactionsController::class, 'linkTransaction'])->name('transactions.link');
// Called by a Nordigen transaction
Route::post('/transactions/{transaction}/link', [TransactionsController::class, 'linkNordigenTransaction'])->name('transactions.transaction.link');

Route::get('/linked-accounts/transactions', [LinkedAccountsController::class, 'showTransactions'])->name('linked-accounts.transactions.show-all');
// Called by a transaction
Route::post('/linked-accounts/transactions/link', [LinkedAccountsController::class, 'linkTransaction'])->name('linked-accounts.transactions.link');

// -----------------------

Route::get('/accounts/new', [PlaygroundController::class, 'listInstitutions'])->name('accounts.new');

Route::get('/nordigen/new/{institutionId}', [PlaygroundController::class, 'createRequisition'])->name('nordigen.new');
Route::get('/nordigen/callback/{requisition}', [PlaygroundController::class, 'handleRequisition'])->name('nordigen.callback.requisition');
Route::get('/nordigen/callback', function (Illuminate\Http\Request $request) {
    // Convert /nordigen/callback?ref=xxx to /nordigen/callback/xxx so route model binding works
    return redirect()->route(
        'nordigen.callback.requisition',
        ['requisition' => $request->input('ref')]
    );
})->name('nordigen.callback');

Route::get('/nordigen/account/{account}/transactions', [PlaygroundController::class, 'syncAccount'])->name('nordigen.account.transactions');
Route::get('/nordigen/all-accounts/transactions', [PlaygroundController::class, 'syncAllAccounts'])->name('nordigen.all-accounts.transactions');
