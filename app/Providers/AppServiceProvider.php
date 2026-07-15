<?php

namespace App\Providers;

use App\Containers\Authentication\WebAuthenticationContainer;
use App\Contracts\Containers\AuthenticationContainerInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Http\Controllers\AuthController;
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

        $this->app
            ->when(AuthController::class)
            ->needs(AuthenticationContainerInterface::class)
            ->give(WebAuthenticationContainer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
