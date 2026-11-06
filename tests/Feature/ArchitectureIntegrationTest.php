<?php

namespace Tests\Feature;

use App\CacheManagers\UserCacheManager;
use App\Constants\ProjectConstant;
use App\Constants\UserConstant;
use App\Contracts\Repositories\PostRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Responses\ResponseMakerInterface;
use App\Contracts\Storage\ProfileImageStorageInterface;
use App\Contracts\Transactions\TransactionManagerInterface;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\ProfileController;
use App\Models\User;
use App\Providers\ApplicationServiceProvider;
use App\Providers\RepositoryServiceProvider;
use App\Repositories\UserRepository;
use App\Responses\JsonResponseMaker;
use App\ServiceManagers\AuthenticationServiceManager;
use App\ServiceManagers\PostServiceManager;
use App\ServiceManagers\UserProfileServiceManager;
use App\Services\UserService;
use App\Storage\LocalProfileImageStorage;
use App\Transactions\DatabaseTransactionManager;
use Database\Factories\CommentFactory;
use Database\Factories\PostFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

class ArchitectureIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_repository_contract_is_bound_to_the_eloquent_repository(): void
    {
        $this->assertInstanceOf(
            UserRepository::class,
            $this->app->make(UserRepositoryInterface::class),
        );
    }

    public function test_repositories_and_service_managers_do_not_share_mutable_instances(): void
    {
        foreach ([UserRepositoryInterface::class, PostRepositoryInterface::class, AuthenticationServiceManager::class, PostServiceManager::class, UserProfileServiceManager::class] as $resolvedClass) {
            $this->assertNotSame($this->app->make($resolvedClass), $this->app->make($resolvedClass));
        }
    }

    public function test_factory_entrypoints_and_model_relationships_resolve_after_namespace_changes(): void
    {
        $createdUser = UserFactory::new()->create();
        $createdPost = PostFactory::new()->for($createdUser)->create();
        $createdComment = CommentFactory::new()->for($createdPost)->for($createdUser)->create();

        $this->assertTrue($createdPost->user->is($createdUser));
        $this->assertTrue($createdComment->post->is($createdPost));
        $this->assertTrue($createdComment->user->is($createdUser));
        $this->assertTrue($createdUser->posts->first()->is($createdPost));
        $this->assertTrue($createdUser->comments->first()->is($createdComment));
        $this->assertTrue($createdPost->comments->first()->is($createdComment));
    }

    public function test_response_maker_contract_is_bound_to_the_json_response_maker(): void
    {
        $this->assertInstanceOf(
            JsonResponseMaker::class,
            $this->app->make(ResponseMakerInterface::class),
        );
    }

    public function test_application_contracts_are_bound_to_their_implementations(): void
    {
        $this->assertInstanceOf(
            DatabaseTransactionManager::class,
            $this->app->make(TransactionManagerInterface::class),
        );
        $this->assertInstanceOf(
            LocalProfileImageStorage::class,
            $this->app->make(ProfileImageStorageInterface::class),
        );
    }

    public function test_layer_specific_service_providers_are_loaded(): void
    {
        $loadedServiceProviders = $this->app->getLoadedProviders();

        $this->assertArrayHasKey(RepositoryServiceProvider::class, $loadedServiceProviders);
        $this->assertArrayHasKey(ApplicationServiceProvider::class, $loadedServiceProviders);
    }

    public function test_transaction_manager_rolls_back_failed_work(): void
    {
        try {
            $this->app->make(TransactionManagerInterface::class)->run(function (): void {
                User::factory()->create(['email' => 'rollback@example.com']);

                throw new RuntimeException('Rollback test.');
            });

            $this->fail('Transaction manager should rethrow the original exception.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Rollback test.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('users', ['email' => 'rollback@example.com']);
    }

    public function test_auth_controller_receives_the_authentication_service_manager(): void
    {
        $authenticationController = $this->app->make(AuthController::class);
        $authenticationContainerProperty = new \ReflectionProperty($authenticationController, 'authenticationContainer');
        $responseMakerProperty = new \ReflectionProperty($authenticationController, 'responseMaker');

        $this->assertInstanceOf(
            AuthenticationServiceManager::class,
            $authenticationContainerProperty->getValue($authenticationController),
        );
        $this->assertInstanceOf(
            ResponseMakerInterface::class,
            $responseMakerProperty->getValue($authenticationController),
        );
    }

    public function test_feed_controller_receives_the_post_service_manager(): void
    {
        $feedController = $this->app->make(FeedController::class);
        $feedContainerProperty = new \ReflectionProperty($feedController, 'feedContainer');
        $responseMakerProperty = new \ReflectionProperty($feedController, 'responseMaker');

        $this->assertInstanceOf(
            PostServiceManager::class,
            $feedContainerProperty->getValue($feedController),
        );
        $this->assertInstanceOf(
            ResponseMakerInterface::class,
            $responseMakerProperty->getValue($feedController),
        );
    }

    public function test_profile_controller_receives_the_profile_service_manager(): void
    {
        $profileController = $this->app->make(ProfileController::class);
        $profileContainerProperty = new \ReflectionProperty($profileController, 'profileContainer');
        $responseMakerProperty = new \ReflectionProperty($profileController, 'responseMaker');

        $this->assertInstanceOf(
            UserProfileServiceManager::class,
            $profileContainerProperty->getValue($profileController),
        );
        $this->assertInstanceOf(
            ResponseMakerInterface::class,
            $responseMakerProperty->getValue($profileController),
        );
    }

    public function test_user_service_reads_presented_user_data_through_repository_cache(): void
    {
        $createdUser = User::factory()->create([
            'name' => '王小明',
            'email' => 'member@example.com',
        ]);

        $userProfile = $this->app->make(UserService::class)->profile($createdUser->getKey());
        $userCacheKey = $this->app->make(UserCacheManager::class)->cacheKeyForUser($createdUser->getKey());

        $this->assertSame($createdUser->getKey(), $userProfile['id']);
        $this->assertSame('王', $userProfile['initials']);
        $this->assertSame('member@example.com', $userProfile['email']);
        $this->assertTrue(Cache::has($userCacheKey));
        $this->assertIsArray(Cache::get($userCacheKey));
        $this->assertSame(UserConstant::CACHE_PAYLOAD_VERSION, Cache::get($userCacheKey)['version']);
        $this->assertArrayNotHasKey('password', Cache::get($userCacheKey)['attributes']);
        $this->assertArrayNotHasKey('remember_token', Cache::get($userCacheKey)['attributes']);
        $this->assertIsString(Cache::get($userCacheKey)['attributes']['created_at']);
    }

    public function test_project_name_is_sourced_from_project_constants(): void
    {
        $this->assertSame(ProjectConstant::NAME, config('app.name'));
    }

    public function test_user_cache_recovers_from_an_incomplete_serialized_object(): void
    {
        $createdUser = User::factory()->create();
        $userCacheManager = $this->app->make(UserCacheManager::class);
        $userCacheKey = $userCacheManager->cacheKeyForUser($createdUser->getKey());

        Cache::put(
            $userCacheKey,
            unserialize('O:17:"MissingCacheClass":0:{}'),
            60,
        );

        $resolvedUser = $userCacheManager->rememberUser(
            $createdUser->getKey(),
            fn (): User => $createdUser,
        );

        $this->assertTrue($resolvedUser->is($createdUser));
        $this->assertIsArray(Cache::get($userCacheKey));
        $this->assertSame($createdUser->getKey(), Cache::get($userCacheKey)['attributes']['id']);
    }

    public function test_user_cache_recovers_when_a_cached_attribute_is_an_incomplete_object(): void
    {
        $createdUser = User::factory()->create();
        $userCacheManager = $this->app->make(UserCacheManager::class);
        $userCacheKey = $userCacheManager->cacheKeyForUser($createdUser->getKey());

        Cache::put($userCacheKey, [
            'version' => UserConstant::CACHE_PAYLOAD_VERSION,
            'attributes' => [
                'id' => $createdUser->getKey(),
                'name' => $createdUser->name,
                'email' => $createdUser->email,
                'created_at' => unserialize('O:17:"MissingCacheClass":0:{}'),
            ],
        ], 60);

        $resolvedUser = $userCacheManager->rememberUser(
            $createdUser->getKey(),
            fn (): User => $createdUser,
        );

        $this->assertTrue($resolvedUser->is($createdUser));
        $this->assertIsString(Cache::get($userCacheKey)['attributes']['created_at']);
    }
}
