<?php

use App\Jobs\NordigenSyncAllAccounts;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new NordigenSyncAllAccounts)->daily();
