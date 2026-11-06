<?php

namespace App\Providers;

use App\Contracts\Responses\ResponseMakerInterface;
use App\Contracts\Storage\ProfileImageStorageInterface;
use App\Contracts\Transactions\TransactionManagerInterface;
use App\Responses\JsonResponseMaker;
use App\Storage\LocalProfileImageStorage;
use App\Transactions\DatabaseTransactionManager;
use Illuminate\Support\ServiceProvider;

class ApplicationServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public $bindings = [
        ProfileImageStorageInterface::class => LocalProfileImageStorage::class,
        TransactionManagerInterface::class => DatabaseTransactionManager::class,
    ];

    /** @var array<class-string, class-string> */
    public $singletons = [
        ResponseMakerInterface::class => JsonResponseMaker::class,
    ];
}
