<?php

namespace App\Providers;

use App\Containers\Authentication\WebAuthenticationContainer;
use App\Containers\Feed\WebFeedContainer;
use App\Contracts\Containers\AuthenticationContainerInterface;
use App\Contracts\Containers\FeedContainerInterface;
use App\Contracts\Repositories\PostRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FeedController;
use App\Repositories\PostRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(PostRepositoryInterface::class, PostRepository::class);

        $this->app
            ->when(AuthController::class)
            ->needs(AuthenticationContainerInterface::class)
            ->give(WebAuthenticationContainer::class);

        $this->app
            ->when(FeedController::class)
            ->needs(FeedContainerInterface::class)
            ->give(WebFeedContainer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
