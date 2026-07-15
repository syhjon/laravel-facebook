<?php

namespace App\Containers\Authentication;

use App\Checkers\AuthenticationChecker;
use App\CombinationManagers\AuthenticationPageCombinationManager;
use App\Contracts\Containers\AuthenticationContainerInterface;
use App\ServiceManagers\AuthenticationServiceManager;
use Illuminate\Support\Facades\DB;

class WebAuthenticationContainer implements AuthenticationContainerInterface
{
    public function __construct(
        private readonly AuthenticationChecker $authenticationChecker,
        private readonly AuthenticationServiceManager $authenticationServiceManager,
        private readonly AuthenticationPageCombinationManager $pageCombinationManager,
    ) {}

    public function page(string $page, ?int $userId = null): array
    {
        return $this->pageCombinationManager->build($page, $userId);
    }

    public function register(array $input): void
    {
        $validated = $this->authenticationChecker->checkRegistration($input);

        DB::transaction(
            fn () => $this->authenticationServiceManager->register($validated),
        );
    }

    public function login(array $input, ?string $ipAddress): void
    {
        $validated = $this->authenticationChecker->checkLogin($input);

        $this->authenticationServiceManager->authenticate($validated, $ipAddress);
    }

    public function logout(): void
    {
        $this->authenticationServiceManager->logout();
    }
}
