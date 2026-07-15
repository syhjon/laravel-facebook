<?php

namespace App\Providers;

use App\Contracts\Repositories\PostRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Repositories\PostRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public $bindings = [
        UserRepositoryInterface::class => UserRepository::class,
        PostRepositoryInterface::class => PostRepository::class,
    ];
}
