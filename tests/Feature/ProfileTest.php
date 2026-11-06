<?php

namespace Tests\Feature;

use App\Constants\ProfileConstant;
use App\Constants\UserConstant;
use App\ExceptionCodes\UserExceptionCode;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_profile_page_or_api(): void
    {
        $this->get(route(ProfileConstant::ROUTE_PROFILE_PAGE))
            ->assertRedirect('/login');

        $this->getJson(route(ProfileConstant::ROUTE_PROFILE_SHOW))
            ->assertUnauthorized();

        $this->patchJson(route(ProfileConstant::ROUTE_PROFILE_UPDATE), [])
            ->assertUnauthorized();

        $this->postJson(route(ProfileConstant::ROUTE_PROFILE_IMAGE_UPDATE), [])
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_view_profile_page(): void
    {
        $authenticatedUser = User::factory()->create([
            'name' => '王小明',
            'email' => 'member@example.com',
        ]);

        $this->actingAs($authenticatedUser)
            ->get(route(ProfileConstant::ROUTE_PROFILE_PAGE))
            ->assertOk()
            ->assertSee('王小明')
            ->assertSee('member@example.com')
            ->assertSee(ProfileConstant::PAGE_PROFILE);
    }

    public function test_authenticated_user_can_read_profile_api(): void
    {
        $authenticatedUser = User::factory()->create([
            'name' => '王小明',
            'email' => 'member@example.com',
        ]);

        $this->actingAs($authenticatedUser)
            ->getJson(route(ProfileConstant::ROUTE_PROFILE_SHOW))
            ->assertOk()
            ->assertJsonPath('data.id', $authenticatedUser->getKey())
            ->assertJsonPath('data.name', '王小明')
            ->assertJsonPath('data.email', 'member@example.com')
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'name', 'email', 'member_since', 'initials', 'profile_image_url'],
                'duration',
            ]);
    }

    public function test_authenticated_user_can_update_profile(): void
    {
        $authenticatedUser = User::factory()->create([
            'name' => '舊名字',
            'email' => 'old@example.com',
        ]);

        $this->app->make(UserService::class)->profile($authenticatedUser->getKey());

        $this->actingAs($authenticatedUser)
            ->patchJson(route(ProfileConstant::ROUTE_PROFILE_UPDATE), [
                'name' => '新名字',
                'email' => ' NEW@EXAMPLE.COM ',
                'user_id' => 999999,
                'password' => 'should-not-change',
            ])
            ->assertOk()
            ->assertJsonPath('message', ProfileConstant::PROFILE_UPDATED_MESSAGE)
            ->assertJsonPath('data.name', '新名字')
            ->assertJsonPath('data.email', 'new@example.com')
            ->assertJsonMissingPath('data.password');

        $this->assertDatabaseHas('users', [
            'id' => $authenticatedUser->getKey(),
            'name' => '新名字',
            'email' => 'new@example.com',
        ]);

        $this->actingAs($authenticatedUser)
            ->getJson(route(ProfileConstant::ROUTE_PROFILE_SHOW))
            ->assertJsonPath('data.name', '新名字')
            ->assertJsonPath('data.email', 'new@example.com');
    }

    public function test_profile_update_allows_the_authenticated_users_current_email(): void
    {
        $authenticatedUser = User::factory()->create([
            'email' => 'member@example.com',
        ]);

        $this->actingAs($authenticatedUser)
            ->patchJson(route(ProfileConstant::ROUTE_PROFILE_UPDATE), [
                'name' => '更新姓名',
                'email' => 'member@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('data.email', 'member@example.com');
    }

    public function test_profile_update_rejects_invalid_or_duplicate_data(): void
    {
        User::factory()->create(['email' => 'used@example.com']);
        $authenticatedUser = User::factory()->create(['email' => 'member@example.com']);

        $this->actingAs($authenticatedUser)
            ->patchJson(route(ProfileConstant::ROUTE_PROFILE_UPDATE), [
                'name' => '',
                'email' => 'used@example.com',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('code', UserExceptionCode::PROFILE_DATA_INVALID)
            ->assertJsonStructure([
                'message',
                'errors' => ['name', 'email'],
            ]);
    }

    public function test_authenticated_user_can_upload_a_profile_image(): void
    {
        Storage::fake(UserConstant::PROFILE_IMAGE_DISK);
        $authenticatedUser = User::factory()->create();
        $profileImage = UploadedFile::fake()->image('profile-image.jpg', 600, 600)->size(512);

        $uploadResponse = $this->actingAs($authenticatedUser)
            ->post(
                route(ProfileConstant::ROUTE_PROFILE_IMAGE_UPDATE),
                ['profile_image' => $profileImage],
                ['Accept' => 'application/json'],
            )
            ->assertOk()
            ->assertJsonPath('message', ProfileConstant::PROFILE_IMAGE_UPDATED_MESSAGE)
            ->assertJsonPath('data.id', $authenticatedUser->getKey());

        $storedProfileImagePath = $authenticatedUser->fresh()->profile_image_path;

        $this->assertNotNull($storedProfileImagePath);
        $this->assertSame(
            UserConstant::PROFILE_IMAGE_PUBLIC_PATH_PREFIX.'/'.$storedProfileImagePath,
            $uploadResponse->json('data.profile_image_url'),
        );
        Storage::disk(UserConstant::PROFILE_IMAGE_DISK)->assertExists($storedProfileImagePath);
    }

    public function test_uploading_a_new_profile_image_deletes_the_previous_image(): void
    {
        Storage::fake(UserConstant::PROFILE_IMAGE_DISK);
        $previousProfileImagePath = 'profile-images/1/previous-image.jpg';
        Storage::disk(UserConstant::PROFILE_IMAGE_DISK)->put($previousProfileImagePath, 'previous-image');
        $authenticatedUser = User::factory()->create([
            'profile_image_path' => $previousProfileImagePath,
        ]);

        $this->actingAs($authenticatedUser)
            ->post(
                route(ProfileConstant::ROUTE_PROFILE_IMAGE_UPDATE),
                ['profile_image' => UploadedFile::fake()->image('new-image.png', 500, 500)],
                ['Accept' => 'application/json'],
            )
            ->assertOk();

        $storedProfileImagePath = $authenticatedUser->fresh()->profile_image_path;

        Storage::disk(UserConstant::PROFILE_IMAGE_DISK)->assertMissing($previousProfileImagePath);
        Storage::disk(UserConstant::PROFILE_IMAGE_DISK)->assertExists($storedProfileImagePath);
    }

    public function test_profile_image_upload_rejects_non_image_files(): void
    {
        Storage::fake(UserConstant::PROFILE_IMAGE_DISK);
        $authenticatedUser = User::factory()->create();

        $this->actingAs($authenticatedUser)
            ->post(
                route(ProfileConstant::ROUTE_PROFILE_IMAGE_UPDATE),
                [
                    'profile_image' => UploadedFile::fake()->create(
                        'not-an-image.txt',
                        10,
                        'text/plain',
                    ),
                ],
                ['Accept' => 'application/json'],
            )
            ->assertUnprocessable()
            ->assertJsonPath('code', UserExceptionCode::PROFILE_IMAGE_INVALID)
            ->assertJsonStructure(['errors' => ['profile_image']]);
    }
}
