<?php

namespace App\Http\Controllers;

use App\Contracts\Containers\AuthenticationContainerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthenticationContainerInterface $authenticationContainer,
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
        DB::transaction(
            fn () => $this->authenticationContainer->register($request->all()),
        );

        $request->session()->regenerate();

        return response()->json([
            'message' => '註冊成功',
            'redirect' => route('dashboard'),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $this->authenticationContainer->login($request->all(), $request->ip());
        $request->session()->regenerate();

        return response()->json([
            'message' => '登入成功',
            'redirect' => route('dashboard'),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->authenticationContainer->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function render(string $page, ?int $userId = null): View
    {
        return view('app', [
            'appData' => $this->authenticationContainer->page($page, $userId),
        ]);
    }
}
