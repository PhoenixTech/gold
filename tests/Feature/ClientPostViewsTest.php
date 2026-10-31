<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Group;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPostViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_posts_index_renders_clean_editorial_layout(): void
    {
        $group = Group::factory()->create(['name' => 'آموزش طلا']);
        $post = Post::factory()->create([
            'status' => 1,
            'title' => 'راهنمای خرید طلا و سکه',
            'group_id' => $group->id,
        ]);

        $response = $this->get(route('client.posts'));

        $response->assertStatus(200);
        $response->assertSee('posts-page-view');
        $response->assertSee('راهنمای خرید طلا و سکه');
        $response->assertSee('آموزش طلا');
        $response->assertSee($post->created_at->ldate('Y/m/d'));
        $response->assertDontSee('assets/default/header-bg.jpg');
        $response->assertSee('role="search"', false);
    }

    public function test_post_single_view_renders_semantic_article_and_reply_elements(): void
    {
        $group = Group::factory()->create(['name' => 'دانستنی‌ها']);
        $post = Post::factory()->create([
            'status' => 1,
            'slug' => 'gold-investment-guide',
            'title' => 'سرمایه‌گذاری در طلا',
            'group_id' => $group->id,
        ]);

        $approvedComment = new Comment();
        $approvedComment->commentable_type = Post::class;
        $approvedComment->commentable_id = $post->id;
        $approvedComment->name = 'رضا';
        $approvedComment->body = 'دیدگاه تأیید شده';
        $approvedComment->ip = '127.0.0.1';
        $approvedComment->status = 1;
        $approvedComment->save();

        $pendingComment = new Comment();
        $pendingComment->commentable_type = Post::class;
        $pendingComment->commentable_id = $post->id;
        $pendingComment->name = 'علی';
        $pendingComment->body = 'دیدگاه در انتظار';
        $pendingComment->ip = '127.0.0.1';
        $pendingComment->status = 0;
        $pendingComment->save();

        $response = $this->get(route('client.post', ['post' => $post->slug]));

        $response->assertStatus(200);
        $response->assertSee('post-single-view');
        $response->assertSee('سرمایه‌گذاری در طلا');
        $response->assertSee($post->created_at->ldate('Y/m/d'));
        $response->assertSee('id="replying-to-badge"', false);
        $response->assertSee('id="cancel-reply-btn"', false);
        $response->assertSee('دیدگاه تأیید شده');
        $response->assertDontSee('دیدگاه در انتظار');
    }
}
