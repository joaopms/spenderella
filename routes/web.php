<?php

use App\Http\Controllers\PlaygroundController;
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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/nordigen', [PlaygroundController::class, 'createRequisition']);
Route::get('/nordigen/callback/{requisition}', [PlaygroundController::class, 'handleRequisition'])->name('nordigen.callback.requisition');
Route::get('/nordigen/callback', function (Illuminate\Http\Request $request) {
    // Convert /nordigen/callback?ref=xxx to /nordigen/callback/xxx so route model binding works
    return redirect()->route(
        'nordigen.callback.requisition',
        ['requisition' => $request->input('ref')]
    );
})->name('nordigen.callback');
