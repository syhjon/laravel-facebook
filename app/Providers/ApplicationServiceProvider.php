<?php

namespace App\Providers;

use App\Contracts\Responses\ResponseMakerInterface;
use App\Contracts\ServiceManagers\AuthenticationServiceManagerInterface;
use App\Contracts\ServiceManagers\PostServiceManagerInterface;
use App\Contracts\Transactions\TransactionManagerInterface;
use App\Responses\JsonResponseMaker;
use App\ServiceManagers\AuthenticationServiceManager;
use App\ServiceManagers\PostServiceManager;
use App\Transactions\DatabaseTransactionManager;
use Illuminate\Support\ServiceProvider;

class ApplicationServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public $bindings = [
        AuthenticationServiceManagerInterface::class => AuthenticationServiceManager::class,
        PostServiceManagerInterface::class => PostServiceManager::class,
        TransactionManagerInterface::class => DatabaseTransactionManager::class,
    ];

    /** @var array<class-string, class-string> */
    public $singletons = [
        ResponseMakerInterface::class => JsonResponseMaker::class,
    ];
}
