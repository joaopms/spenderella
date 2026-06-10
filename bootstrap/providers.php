<?php

use App\Providers\AppServiceProvider;
use App\Providers\NordigenServiceProvider;
use App\Providers\TelescopeServiceProvider;

return [
    AppServiceProvider::class,
    TelescopeServiceProvider::class,

    NordigenServiceProvider::class,
];
