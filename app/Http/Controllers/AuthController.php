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
        return $this->render(AuthenticationConstant::PAGE_LOGIN);
    }

    public function showRegister(): View
    {
        return $this->render(AuthenticationConstant::PAGE_REGISTER);
    }

    public function dashboard(ApplicationRequest $request): View
    {
        return $this->render(AuthenticationConstant::PAGE_DASHBOARD, $request->userId());
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $this->authenticationContainer->register($request->payload());

        $request->session()->regenerate();

        return $this->responseMaker->make(
            httpCode: HttpCodeConstant::CREATED,
            message: '註冊成功',
            additional: [
                'redirect' => route(AuthenticationConstant::ROUTE_DASHBOARD),
            ],
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $this->authenticationContainer->login($request->payload(), $request->ip());
        $request->session()->regenerate();

        return $this->responseMaker->make(
            message: '登入成功',
            additional: [
                'redirect' => route(AuthenticationConstant::ROUTE_DASHBOARD),
            ],
        );
    }

    public function logout(ApplicationRequest $request): RedirectResponse
    {
        $this->authenticationContainer->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route(AuthenticationConstant::ROUTE_LOGIN);
    }

    private function render(string $page, ?int $userId = null): View
    {
        return view('app', [
            'appData' => $this->authenticationContainer->page($page, $userId),
        ]);
    }
}
