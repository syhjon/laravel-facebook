<?php

namespace App\Http\Controllers;

use App\Checkers\AuthenticationChecker;
use App\CombinationManagers\AuthenticationPageCombinationManager;
use App\ServiceManagers\AuthenticationServiceManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthenticationChecker $authenticationChecker,
        private readonly AuthenticationServiceManager $authenticationServiceManager,
        private readonly AuthenticationPageCombinationManager $pageCombinationManager,
    ) {}

    public function showLogin(): View
    {
        return $this->render('login');
    }

    public function showRegister(): View
    {
        return $this->render('register');
    }

    public function dashboard(Request $request): View
    {
        return $this->render('dashboard', $request->user()->getKey());
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $this->authenticationChecker->checkRegistration($request->all());

        DB::transaction(
            fn () => $this->authenticationServiceManager->register($validated),
        );

        $request->session()->regenerate();

        return response()->json([
            'message' => '註冊成功',
            'redirect' => route('dashboard'),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $this->authenticationChecker->checkLogin($request->all());

        $this->authenticationServiceManager->authenticate($validated, $request->ip());
        $request->session()->regenerate();

        return response()->json([
            'message' => '登入成功',
            'redirect' => route('dashboard'),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->authenticationServiceManager->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function render(string $page, ?int $userId = null): View
    {
        return view('app', [
            'appData' => $this->pageCombinationManager->build($page, $userId),
        ]);
    }
}
