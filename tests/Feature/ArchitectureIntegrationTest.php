<?php

namespace Tests\Feature;

use App\CacheManagers\UserCacheManager;
use App\Constants\ProjectConstant;
use App\Constants\UserConstant;
use App\Containers\Authentication\WebAuthenticationContainer;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Http\Controllers\AuthController;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

    public function test_auth_controller_receives_the_web_authentication_container_contextually(): void
    {
        $controller = $this->app->make(AuthController::class);
        $property = new \ReflectionProperty($controller, 'authenticationContainer');

        $this->assertInstanceOf(
            WebAuthenticationContainer::class,
            $property->getValue($controller),
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
}
