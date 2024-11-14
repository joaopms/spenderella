<?php

namespace App\Http\Controllers;

use App\Http\Resources\NordigenAccountResource;
use App\Models\NordigenAccount;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function show()
    {
        $linkedAccounts = NordigenAccount::all();

        return Inertia::render('settings/show', [
            'linkedAccounts' => NordigenAccountResource::collection($linkedAccounts),
        ]);
    }
}
