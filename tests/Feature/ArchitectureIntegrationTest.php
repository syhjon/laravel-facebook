<?php

namespace Tests\Feature;

use App\CacheManagers\UserCacheManager;
use App\Constants\ProjectConstant;
use App\Constants\UserConstant;
use App\Containers\Authentication\WebAuthenticationContainer;
use App\Containers\Feed\WebFeedContainer;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Responses\ResponseMakerInterface;
use App\Contracts\ServiceManagers\AuthenticationServiceManagerInterface;
use App\Contracts\ServiceManagers\PostServiceManagerInterface;
use App\Contracts\Transactions\TransactionManagerInterface;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FeedController;
use App\Models\User;
use App\Providers\ApplicationServiceProvider;
use App\Providers\EntryContextServiceProvider;
use App\Providers\RepositoryServiceProvider;
use App\Repositories\UserRepository;
use App\Responses\JsonResponseMaker;
use App\ServiceManagers\AuthenticationServiceManager;
use App\ServiceManagers\PostServiceManager;
use App\Services\UserService;
use App\Transactions\DatabaseTransactionManager;
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
            AuthenticationServiceManager::class,
            $this->app->make(AuthenticationServiceManagerInterface::class),
        );
        $this->assertInstanceOf(
            PostServiceManager::class,
            $this->app->make(PostServiceManagerInterface::class),
        );
        $this->assertInstanceOf(
            DatabaseTransactionManager::class,
            $this->app->make(TransactionManagerInterface::class),
        );
    }

    public function test_layer_specific_service_providers_are_loaded(): void
    {
        $loadedServiceProviders = $this->app->getLoadedProviders();

        $this->assertArrayHasKey(RepositoryServiceProvider::class, $loadedServiceProviders);
        $this->assertArrayHasKey(ApplicationServiceProvider::class, $loadedServiceProviders);
        $this->assertArrayHasKey(EntryContextServiceProvider::class, $loadedServiceProviders);
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

    public function test_auth_controller_receives_the_web_authentication_container_contextually(): void
    {
        $authenticationController = $this->app->make(AuthController::class);
        $authenticationContainerProperty = new \ReflectionProperty($authenticationController, 'authenticationContainer');
        $responseMakerProperty = new \ReflectionProperty($authenticationController, 'responseMaker');

        $this->assertInstanceOf(
            WebAuthenticationContainer::class,
            $authenticationContainerProperty->getValue($authenticationController),
        );
        $this->assertInstanceOf(
            ResponseMakerInterface::class,
            $responseMakerProperty->getValue($authenticationController),
        );
    }

    public function test_feed_controller_receives_the_web_feed_container_contextually(): void
    {
        $feedController = $this->app->make(FeedController::class);
        $feedContainerProperty = new \ReflectionProperty($feedController, 'feedContainer');
        $responseMakerProperty = new \ReflectionProperty($feedController, 'responseMaker');

        $this->assertInstanceOf(
            WebFeedContainer::class,
            $feedContainerProperty->getValue($feedController),
        );
        $this->assertInstanceOf(
            ResponseMakerInterface::class,
            $responseMakerProperty->getValue($feedController),
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
