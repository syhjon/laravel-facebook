<?php

namespace Tests\Feature;

use App\Constants\PostConstant;
use App\ExceptionCodes\PostExceptionCode;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_requires_authentication(): void
    {
        $this->getJson(route(PostConstant::ROUTE_FEED))->assertUnauthorized();
    }

    public function test_user_can_create_a_post(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route(PostConstant::ROUTE_POST_CREATE), [
                'body' => '今天開始使用 WeTalk，和大家分享第一篇貼文。',
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Created')
            ->assertJsonPath('data.body', '今天開始使用 WeTalk，和大家分享第一篇貼文。')
            ->assertJsonPath('data.author.id', $user->getKey())
            ->assertJsonPath('data.likes_count', 0)
            ->assertJsonPath('data.comments_count', 0);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->getKey(),
            'body' => '今天開始使用 WeTalk，和大家分享第一篇貼文。',
        ]);
    }

    public function test_post_content_is_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route(PostConstant::ROUTE_POST_CREATE), ['body' => ''])
            ->assertUnprocessable()
            ->assertJsonPath('code', PostExceptionCode::POST_DATA_INVALID)
            ->assertJsonStructure([
                'message',
                'data',
                'duration',
                'errors' => ['body'],
            ]);
    }

    public function test_feed_uses_ten_item_cursor_pages_without_duplicates(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(25)->for($user)->create();

        $firstPage = $this->actingAs($user)
            ->getJson(route(PostConstant::ROUTE_FEED))
            ->assertOk()
            ->assertJsonCount(PostConstant::FEED_PER_PAGE, 'data')
            ->assertJsonPath('message', 'OK')
            ->assertJsonPath('meta.has_more', true);

        $firstIds = collect($firstPage->json('data'))->pluck('id');
        $cursor = $firstPage->json('meta.next_cursor');

        $secondPage = $this->getJson(
            route(PostConstant::ROUTE_FEED).'?cursor='.urlencode($cursor),
        )
            ->assertOk()
            ->assertJsonCount(PostConstant::FEED_PER_PAGE, 'data');

        $secondIds = collect($secondPage->json('data'))->pluck('id');

        $this->assertNotNull($cursor);
        $this->assertCount(0, $firstIds->intersect($secondIds));
        $this->assertTrue($firstIds->first() > $firstIds->last());
    }

    public function test_feed_cursor_is_validated_before_reaching_the_container(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route(PostConstant::ROUTE_FEED).'?cursor='.str_repeat('x', PostConstant::CURSOR_MAX_LENGTH + 1))
            ->assertUnprocessable()
            ->assertJsonPath('code', PostExceptionCode::FEED_QUERY_INVALID)
            ->assertJsonStructure(['errors' => ['cursor']]);
    }

    public function test_user_can_toggle_a_post_like(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();

        $this->actingAs($user)
            ->postJson(route(PostConstant::ROUTE_POST_LIKE, ['postId' => $post->getKey()]))
            ->assertOk()
            ->assertJsonPath('data.liked', true)
            ->assertJsonPath('data.likes_count', 1);

        $this->assertDatabaseHas('post_likes', [
            'post_id' => $post->getKey(),
            'user_id' => $user->getKey(),
        ]);

        $this->postJson(route(PostConstant::ROUTE_POST_LIKE, ['postId' => $post->getKey()]))
            ->assertOk()
            ->assertJsonPath('data.liked', false)
            ->assertJsonPath('data.likes_count', 0);
    }

    public function test_user_can_reply_to_a_post(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $post = Post::factory()->for($author)->create();

        $this->actingAs($commenter)
            ->postJson(
                route(PostConstant::ROUTE_POST_COMMENT, ['postId' => $post->getKey()]),
                ['body' => '很棒的分享，期待你的下一篇文章！'],
            )
            ->assertCreated()
            ->assertJsonPath('data.comments_count', 1)
            ->assertJsonPath('data.comments.0.author.id', $commenter->getKey())
            ->assertJsonPath('data.comments.0.body', '很棒的分享，期待你的下一篇文章！');

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->getKey(),
            'user_id' => $commenter->getKey(),
        ]);
    }

    public function test_comment_content_is_validated_before_reaching_the_container(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->create();

        $this->actingAs($user)
            ->postJson(
                route(PostConstant::ROUTE_POST_COMMENT, ['postId' => $post->getKey()]),
                ['body' => ''],
            )
            ->assertUnprocessable()
            ->assertJsonPath('code', PostExceptionCode::COMMENT_DATA_INVALID)
            ->assertJsonStructure(['errors' => ['body']]);
    }
}
