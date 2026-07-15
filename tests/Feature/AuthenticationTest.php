<?php

namespace Tests\Feature;

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
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('id="app"', false);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('id="app"', false);
    }

    public function test_guest_is_redirected_from_the_dashboard(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_user_can_register(): void
    {
        $response = $this->postJson(route('register'), [
            'name' => '王小明',
            'email' => 'USER@EXAMPLE.COM',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('redirect', route('dashboard'));

        $user = User::where('email', 'user@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_registration_requires_valid_unique_data(): void
    {
        User::factory()->create(['email' => 'used@example.com']);

        $this->postJson(route('register'), [
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

        $this->postJson(route('login'), [
            'email' => 'MEMBER@example.com',
            'password' => 'password123',
            'remember' => true,
        ])
            ->assertOk()
            ->assertJsonPath('redirect', route('dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->get(route('dashboard'))
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

        $response = $this->postJson(route('login'), [
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
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
