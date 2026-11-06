<?php

namespace Tests\Feature;

use App\Combinations\PostCombination;
use App\Combinations\UserCombination;
use App\Constants\UserConstant;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\ServiceManagers\AuthenticationServiceManager;
use App\ServiceManagers\PostServiceManager;
use App\ServiceManagers\UserProfileServiceManager;
use App\Services\AuthenticationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class ServiceManageTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_rolls_back_when_authentication_fails_after_creating_the_user(): void
    {
        $this->mock(AuthenticationService::class, function (MockInterface $authenticationService): void {
            $authenticationService->shouldReceive('login')->once()->andThrow(new RuntimeException('Authentication failed.'));
        });

        try {
            $this->app->make(AuthenticationServiceManager::class)->register([
                'name' => '交易測試',
                'email' => 'rollback-registration@example.com',
                'password' => 'password123',
            ]);

            $this->fail('The original authentication failure must be rethrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Authentication failed.', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_post_creation_rolls_back_when_output_combination_fails(): void
    {
        $authenticatedUser = User::factory()->create();
        $this->mock(PostCombination::class, function (MockInterface $postCombination): void {
            $postCombination->shouldReceive('post')->once()->andThrow(new RuntimeException('Post combination failed.'));
        });

        try {
            $this->app->make(PostServiceManager::class)->create($authenticatedUser->getKey(), [
                'body' => '組裝失敗時不可留下貼文。',
            ]);

            $this->fail('The original combination failure must be rethrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Post combination failed.', $exception->getMessage());
        }

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_profile_update_rolls_back_when_output_combination_fails(): void
    {
        $authenticatedUser = User::factory()->create([
            'name' => '原有姓名',
            'email' => 'original@example.com',
        ]);
        $this->mock(UserCombination::class, function (MockInterface $userCombination): void {
            $userCombination->shouldReceive('profile')->once()->andThrow(new RuntimeException('Profile combination failed.'));
        });

        try {
            $this->app->make(UserProfileServiceManager::class)->updateProfile($authenticatedUser->getKey(), [
                'name' => '不可保留的姓名',
                'email' => 'changed@example.com',
            ]);

            $this->fail('The original combination failure must be rethrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Profile combination failed.', $exception->getMessage());
        }

        $this->assertDatabaseHas('users', [
            'id' => $authenticatedUser->getKey(),
            'name' => '原有姓名',
            'email' => 'original@example.com',
        ]);
    }

    public function test_failed_image_path_write_removes_the_new_image_and_preserves_the_previous_image(): void
    {
        Storage::fake(UserConstant::PROFILE_IMAGE_DISK);
        $authenticatedUser = User::factory()->create();
        $previousProfileImagePath = 'profile-images/'.$authenticatedUser->getKey().'/previous.jpg';
        $authenticatedUser->update(['profile_image_path' => $previousProfileImagePath]);
        Storage::disk(UserConstant::PROFILE_IMAGE_DISK)->put($previousProfileImagePath, 'previous-image');
        $this->mock(UserRepositoryInterface::class, function (MockInterface $userRepository) use ($authenticatedUser): void {
            $userRepository->shouldReceive('find')->once()->with($authenticatedUser->getKey())->andReturn($authenticatedUser);
            $userRepository->shouldReceive('updateProfileImagePath')->once()->andThrow(new RuntimeException('Image path write failed.'));
        });

        try {
            $this->app->make(UserProfileServiceManager::class)->updateProfileImage(
                $authenticatedUser->getKey(),
                UploadedFile::fake()->image('new.jpg', 100, 100),
            );

            $this->fail('The original repository failure must be rethrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Image path write failed.', $exception->getMessage());
        }

        $this->assertSame(
            [$previousProfileImagePath],
            Storage::disk(UserConstant::PROFILE_IMAGE_DISK)->allFiles('profile-images/'.$authenticatedUser->getKey()),
        );
        $this->assertSame($previousProfileImagePath, $authenticatedUser->fresh()->profile_image_path);
    }
}
