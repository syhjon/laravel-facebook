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
        $providers = $this->app->getLoadedProviders();

        $this->assertArrayHasKey(RepositoryServiceProvider::class, $providers);
        $this->assertArrayHasKey(ApplicationServiceProvider::class, $providers);
        $this->assertArrayHasKey(EntryContextServiceProvider::class, $providers);
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
        $controller = $this->app->make(AuthController::class);
        $property = new \ReflectionProperty($controller, 'authenticationContainer');
        $responseMaker = new \ReflectionProperty($controller, 'responseMaker');

        $this->assertInstanceOf(
            WebAuthenticationContainer::class,
            $property->getValue($controller),
        );
        $this->assertInstanceOf(
            ResponseMakerInterface::class,
            $responseMaker->getValue($controller),
        );
    }

    public function test_feed_controller_receives_the_web_feed_container_contextually(): void
    {
        $controller = $this->app->make(FeedController::class);
        $property = new \ReflectionProperty($controller, 'feedContainer');
        $responseMaker = new \ReflectionProperty($controller, 'responseMaker');

        $this->assertInstanceOf(
            WebFeedContainer::class,
            $property->getValue($controller),
        );
        $this->assertInstanceOf(
            ResponseMakerInterface::class,
            $responseMaker->getValue($controller),
        );
    }

    public function test_user_service_reads_presented_user_data_through_repository_cache(): void
    {
        $user = User::factory()->create([
            'name' => '王小明',
            'email' => 'member@example.com',
        ]);

        $profile = $this->app->make(UserService::class)->profile($user->getKey());
        $cacheKey = $this->app->make(UserCacheManager::class)->key($user->getKey());

        $this->assertSame($user->getKey(), $profile['id']);
        $this->assertSame('王', $profile['initials']);
        $this->assertSame('member@example.com', $profile['email']);
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertIsArray(Cache::get($cacheKey));
        $this->assertSame(UserConstant::CACHE_PAYLOAD_VERSION, Cache::get($cacheKey)['version']);
        $this->assertArrayNotHasKey('password', Cache::get($cacheKey)['attributes']);
        $this->assertArrayNotHasKey('remember_token', Cache::get($cacheKey)['attributes']);
        $this->assertIsString(Cache::get($cacheKey)['attributes']['created_at']);
    }

    public function test_project_name_is_sourced_from_project_constants(): void
    {
        $this->assertSame(ProjectConstant::NAME, config('app.name'));
    }

    public function test_user_cache_recovers_from_an_incomplete_serialized_object(): void
    {
        $user = User::factory()->create();
        $cacheManager = $this->app->make(UserCacheManager::class);
        $cacheKey = $cacheManager->key($user->getKey());

        Cache::put(
            $cacheKey,
            unserialize('O:17:"MissingCacheClass":0:{}'),
            60,
        );

        $resolved = $cacheManager->remember(
            $user->getKey(),
            fn (): User => $user,
        );

        $this->assertTrue($resolved->is($user));
        $this->assertIsArray(Cache::get($cacheKey));
        $this->assertSame($user->getKey(), Cache::get($cacheKey)['attributes']['id']);
    }

    public function test_user_cache_recovers_when_a_cached_attribute_is_an_incomplete_object(): void
    {
        $user = User::factory()->create();
        $cacheManager = $this->app->make(UserCacheManager::class);
        $cacheKey = $cacheManager->key($user->getKey());

        Cache::put($cacheKey, [
            'version' => UserConstant::CACHE_PAYLOAD_VERSION,
            'attributes' => [
                'id' => $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => unserialize('O:17:"MissingCacheClass":0:{}'),
            ],
        ], 60);

        $resolved = $cacheManager->remember(
            $user->getKey(),
            fn (): User => $user,
        );

        $this->assertTrue($resolved->is($user));
        $this->assertIsString(Cache::get($cacheKey)['attributes']['created_at']);
    }
}
