<?php

namespace App\Providers;

use App\Containers\Authentication\WebAuthenticationContainer;
use App\Containers\Feed\WebFeedContainer;
use App\Contracts\Containers\AuthenticationContainerInterface;
use App\Contracts\Containers\FeedContainerInterface;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FeedController;
use Illuminate\Support\ServiceProvider;

class EntryContextServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app
            ->when(AuthController::class)
            ->needs(AuthenticationContainerInterface::class)
            ->give(WebAuthenticationContainer::class);

        $this->app
            ->when(FeedController::class)
            ->needs(FeedContainerInterface::class)
            ->give(WebFeedContainer::class);
    }
}
