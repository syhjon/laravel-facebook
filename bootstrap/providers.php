<?php

use App\Providers\ApplicationServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\EntryContextServiceProvider;
use App\Providers\RepositoryServiceProvider;

return [
    AppServiceProvider::class,
    RepositoryServiceProvider::class,
    ApplicationServiceProvider::class,
    EntryContextServiceProvider::class,
];
