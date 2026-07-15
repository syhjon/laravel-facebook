<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
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
        return $this->render('dashboard', $request->user());
    }

    public function register(Request $request): JsonResponse
    {
        $request->merge([
            'email' => Str::lower((string) $request->input('email')),
        ]);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $validated = $validator->validated();

        $user = User::create($validated);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => '註冊成功',
            'redirect' => route('dashboard'),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $validated = $validator->validated();

        $key = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return $this->validationError([
                'email' => ["登入嘗試次數過多，請在 {$seconds} 秒後再試。"],
            ]);
        }

        $credentials = [
            'email' => Str::lower($validated['email']),
            'password' => $validated['password'],
        ];

        if (! Auth::attempt($credentials, (bool) ($validated['remember'] ?? false))) {
            RateLimiter::hit($key, 60);

            return $this->validationError([
                'email' => ['Email 或密碼不正確。'],
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return response()->json([
            'message' => '登入成功',
            'redirect' => route('dashboard'),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function render(string $page, ?User $user = null): View
    {
        return view('app', [
            'appData' => [
                'page' => $page,
                'user' => $user?->only(['id', 'name', 'email']),
                'routes' => [
                    'login' => route('login'),
                    'register' => route('register'),
                    'dashboard' => route('dashboard'),
                    'logout' => route('logout'),
                ],
            ],
        ]);
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    private function validationError(array $errors): JsonResponse
    {
        return response()->json([
            'message' => '輸入資料有誤。',
            'errors' => $errors,
        ], 422);
    }
}
