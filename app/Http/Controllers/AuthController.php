<?php

namespace App\Http\Controllers;

use App\Constants\AuthenticationConstant;
use App\Constants\HttpCodeConstant;
use App\Contracts\Containers\AuthenticationContainerInterface;
use App\Contracts\Responses\ResponseMakerInterface;
use App\Http\Requests\ApplicationRequest;
use App\Http\Requests\Authentication\LoginRequest;
use App\Http\Requests\Authentication\RegisterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthenticationContainerInterface $authenticationContainer,
        ResponseMakerInterface $responseMaker,
    ) {
        parent::__construct($responseMaker);
    }

    public function showLogin(): View
    {
        return $this->renderAuthenticationPage(AuthenticationConstant::PAGE_LOGIN);
    }

    public function showRegister(): View
    {
        return $this->renderAuthenticationPage(AuthenticationConstant::PAGE_REGISTER);
    }

    public function dashboard(ApplicationRequest $applicationRequest): View
    {
        return $this->renderAuthenticationPage(AuthenticationConstant::PAGE_DASHBOARD, $applicationRequest->userId());
    }

    public function register(RegisterRequest $registerRequest): JsonResponse
    {
        $this->authenticationContainer->register($registerRequest->payload());

        $registerRequest->session()->regenerate();

        return $this->responseMaker->createResponse(
            httpCode: HttpCodeConstant::CREATED,
            message: '註冊成功',
            additionalResponseData: [
                'redirect' => route(AuthenticationConstant::ROUTE_DASHBOARD),
            ],
        );
    }

    public function login(LoginRequest $loginRequest): JsonResponse
    {
        $this->authenticationContainer->login($loginRequest->payload(), $loginRequest->ip());
        $loginRequest->session()->regenerate();

        return $this->responseMaker->createResponse(
            message: '登入成功',
            additionalResponseData: [
                'redirect' => route(AuthenticationConstant::ROUTE_DASHBOARD),
            ],
        );
    }

    public function logout(ApplicationRequest $applicationRequest): RedirectResponse
    {
        $this->authenticationContainer->logout();

        $applicationRequest->session()->invalidate();
        $applicationRequest->session()->regenerateToken();

        return redirect()->route(AuthenticationConstant::ROUTE_LOGIN);
    }

    private function renderAuthenticationPage(string $pageName, ?int $userId = null): View
    {
        return view('app', [
            'applicationData' => $this->authenticationContainer->page($pageName, $userId),
        ]);
    }
}
