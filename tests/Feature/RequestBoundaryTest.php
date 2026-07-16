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
        $registerRequest = RegisterRequest::create('/register', 'POST', [
            'name' => '王小明',
            'email' => ' USER@EXAMPLE.COM ',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_admin' => true,
        ]);

        $this->validateRequest($registerRequest);

        $this->assertSame([
            'name' => '王小明',
            'email' => 'user@example.com',
            'password' => 'password123',
        ], $registerRequest->payload());
        $this->assertArrayNotHasKey('is_admin', $registerRequest->payload());
        $this->assertArrayNotHasKey('password_confirmation', $registerRequest->payload());
    }

    public function test_post_request_keeps_authenticated_identity_outside_the_input_payload(): void
    {
        $authenticatedUser = User::factory()->create();
        $storePostRequest = StorePostRequest::create('/posts', 'POST', [
            'body' => '這是一篇經過驗證的貼文。',
            'user_id' => 999999,
        ]);
        $storePostRequest->setUserResolver(fn (): User => $authenticatedUser);

        $this->validateRequest($storePostRequest);

        $this->assertSame(['body' => '這是一篇經過驗證的貼文。'], $storePostRequest->payload());
        $this->assertSame($authenticatedUser->getKey(), $storePostRequest->userId());
        $this->assertArrayNotHasKey('user_id', $storePostRequest->payload());
    }

    private function validateRequest(ApplicationRequest $applicationRequest): void
    {
        $applicationRequest->setContainer($this->app);
        $applicationRequest->setRedirector($this->app->make(Redirector::class));
        $applicationRequest->validateResolved();
    }
}
