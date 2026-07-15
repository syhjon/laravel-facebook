<?php

namespace Tests\Feature;

use App\Constants\AuthenticationConstant;
use App\Constants\ProjectConstant;
use App\ExceptionCodes\AuthenticationExceptionCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_view_the_login_and_registration_pages(): void
    {
        $this->get(route(AuthenticationConstant::ROUTE_LOGIN))
            ->assertOk()
            ->assertSee('id="app"', false)
            ->assertSee('<title>'.ProjectConstant::NAME.'</title>', false)
            ->assertSee('--brand-primary: '.ProjectConstant::PRIMARY_COLOR.';', false)
            ->assertSee(ProjectConstant::NAME);

        $this->get(route(AuthenticationConstant::ROUTE_REGISTER))
            ->assertOk()
            ->assertSee('id="app"', false);
    }

    public function test_guest_is_redirected_from_the_dashboard(): void
    {
        $this->get(route(AuthenticationConstant::ROUTE_DASHBOARD))
            ->assertRedirect(route(AuthenticationConstant::ROUTE_LOGIN));
    }

    public function test_user_can_register(): void
    {
        $response = $this->postJson(route(AuthenticationConstant::ROUTE_REGISTER), [
            'name' => '王小明',
            'email' => 'USER@EXAMPLE.COM',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('redirect', route(AuthenticationConstant::ROUTE_DASHBOARD));

        $user = User::where('email', 'user@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_registration_requires_valid_unique_data(): void
    {
        User::factory()->create(['email' => 'used@example.com']);

        $this->postJson(route(AuthenticationConstant::ROUTE_REGISTER), [
            'name' => '',
            'email' => 'used@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('code', AuthenticationExceptionCode::REGISTRATION_DATA_INVALID)
            ->assertJsonStructure([
                'message',
                'errors' => ['name', 'email', 'password'],
            ]);
    }

    public function test_user_can_login_and_view_the_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'password123',
        ]);

        $this->postJson(route(AuthenticationConstant::ROUTE_LOGIN), [
            'email' => 'MEMBER@example.com',
            'password' => 'password123',
            'remember' => true,
        ])
            ->assertOk()
            ->assertJsonPath('redirect', route(AuthenticationConstant::ROUTE_DASHBOARD));

        $this->assertAuthenticatedAs($user);

        $this->get(route(AuthenticationConstant::ROUTE_DASHBOARD))
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee($user->email);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson(route(AuthenticationConstant::ROUTE_LOGIN), [
            'email' => 'member@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('code', AuthenticationExceptionCode::CREDENTIALS_INVALID)
            ->assertJsonStructure([
                'message',
                'errors' => ['email'],
            ]);

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route(AuthenticationConstant::ROUTE_LOGOUT))
            ->assertRedirect(route(AuthenticationConstant::ROUTE_LOGIN));

        $this->assertGuest();
    }
}
