<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\Concerns\ResolvesAdminModel;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolvesAdminModelTest extends TestCase
{
    use RefreshDatabase;

    private object $consumer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consumer = new class {
            use ResolvesAdminModel;

            public function resolve(string $modelClass, Model|string|int $item, bool $withTrashed = false): Model
            {
                return $this->resolveModel($modelClass, $item, $withTrashed);
            }
        };
    }

    public function test_it_returns_instance_directly(): void
    {
        $category = Category::create([
            'name' => 'Direct Instance',
            'slug' => 'direct-'.uniqid(),
        ]);

        $resolved = $this->consumer->resolve(Category::class, $category);

        $this->assertSame($category, $resolved);
    }

    public function test_it_resolves_model_by_primary_key(): void
    {
        $category = Category::create([
            'name' => 'By ID',
            'slug' => 'by-id-'.uniqid(),
        ]);

        $resolved = $this->consumer->resolve(Category::class, $category->id);

        $this->assertSame($category->id, $resolved->id);
    }

    public function test_it_resolves_model_by_custom_route_key(): void
    {
        $user = User::factory()->create([
            'email' => 'custom-route-key-'.uniqid().'@example.com',
        ]);

        $resolved = $this->consumer->resolve(User::class, $user->email);

        $this->assertSame($user->id, $resolved->id);
        $this->assertSame($user->email, $resolved->email);
    }

    public function test_it_resolves_soft_deleted_model_when_with_trashed_is_true(): void
    {
        $user = User::factory()->create([
            'email' => 'trashed-'.uniqid().'@example.com',
        ]);
        $user->delete();

        $this->assertSoftDeleted($user);

        $resolved = $this->consumer->resolve(User::class, $user->id, true);
        $this->assertSame($user->id, $resolved->id);

        $resolvedByEmail = $this->consumer->resolve(User::class, $user->email, true);
        $this->assertSame($user->id, $resolvedByEmail->id);
    }

    public function test_it_throws_model_not_found_for_soft_deleted_model_when_with_trashed_is_false(): void
    {
        $user = User::factory()->create([
            'email' => 'trashed-not-found-'.uniqid().'@example.com',
        ]);
        $user->delete();

        $this->expectException(ModelNotFoundException::class);
        $this->consumer->resolve(User::class, $user->id, false);
    }

    public function test_it_throws_model_not_found_for_non_existent_key(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $this->consumer->resolve(Category::class, 999999);
    }
}
