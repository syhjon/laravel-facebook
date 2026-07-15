<?php

namespace Tests\Feature;

use App\Http\Requests\ApplicationRequest;
use App\Http\Requests\Authentication\RegisterRequest;
use App\Http\Requests\Feed\StorePostRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Redirector;
use Tests\TestCase;

class RequestBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_request_normalizes_email_and_only_exposes_validated_payload(): void
    {
        $request = RegisterRequest::create('/register', 'POST', [
            'name' => '王小明',
            'email' => ' USER@EXAMPLE.COM ',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_admin' => true,
        ]);

        $this->validateRequest($request);

        $this->assertSame([
            'name' => '王小明',
            'email' => 'user@example.com',
            'password' => 'password123',
        ], $request->payload());
        $this->assertArrayNotHasKey('is_admin', $request->payload());
        $this->assertArrayNotHasKey('password_confirmation', $request->payload());
    }

    public function test_post_request_keeps_authenticated_identity_outside_the_input_payload(): void
    {
        $user = User::factory()->create();
        $request = StorePostRequest::create('/posts', 'POST', [
            'body' => '這是一篇經過驗證的貼文。',
            'user_id' => 999999,
        ]);
        $request->setUserResolver(fn (): User => $user);

        $this->validateRequest($request);

        $this->assertSame(['body' => '這是一篇經過驗證的貼文。'], $request->payload());
        $this->assertSame($user->getKey(), $request->userId());
        $this->assertArrayNotHasKey('user_id', $request->payload());
    }

    private function validateRequest(ApplicationRequest $request): void
    {
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(Redirector::class));
        $request->validateResolved();
    }
}
