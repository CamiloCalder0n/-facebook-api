<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VersioningApiTest extends TestCase
{
    use RefreshDatabase;

    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        $owner = User::factory()->create(['name' => 'Ana', 'email' => 'ana@example.com']);
        $this->post = Post::create(['user_id' => $owner->id, 'title' => 'Mi viaje', 'content' => 'Fotos']);
        $this->post->images()->create(['image_path' => 'posts/a.jpg']);
        $this->post->comments()->create(['author' => 'Luis', 'content' => 'Uno']);
        $this->post->comments()->create(['author' => 'Eva', 'content' => 'Dos']);
    }

    public function test_v1_y_alias_sin_version_responden_igual(): void
    {
        $v1 = $this->getJson('/api/v1/posts')->assertStatus(200);
        $alias = $this->getJson('/api/posts')->assertStatus(200);

        $this->assertSame($v1->json('data'), $alias->json('data'));
        $v1->assertJsonStructure(['data' => [['id', 'title', 'images' => [['image_url']], 'comments']], 'current_page', 'total']);
    }

    public function test_v1_show_y_alias_show_responden_igual(): void
    {
        $this->assertSame(
            $this->getJson("/api/v1/posts/{$this->post->id}")->assertOk()->json(),
            $this->getJson("/api/posts/{$this->post->id}")->assertOk()->json(),
        );
    }

    public function test_rutas_protegidas_de_v1_piden_token(): void
    {
        $this->postJson('/api/v1/posts', ['title' => 'T', 'content' => 'C'])->assertStatus(401);
        $this->postJson("/api/v1/posts/{$this->post->id}/like")->assertStatus(401);

        Sanctum::actingAs(User::factory()->create());
        $this->postJson("/api/v1/posts/{$this->post->id}/like")->assertStatus(200);
    }

    public function test_auth_funciona_en_v1(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Leo', 'email' => 'leo@example.com',
            'password' => 'secreto123', 'password_confirmation' => 'secreto123',
        ])->assertStatus(201)->assertJsonStructure(['token']);

        $this->postJson('/api/v1/login', ['email' => 'leo@example.com', 'password' => 'secreto123'])
            ->assertStatus(200);
    }

    public function test_v2_index_devuelve_el_formato_nuevo(): void
    {
        $this->getJson('/api/v2/posts')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [[
                    'id', 'title', 'content',
                    'author' => ['id', 'name'],
                    'images' => [['id', 'url']],
                    'likes_count', 'comments_count', 'created_at',
                ]],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('data.0.author.name', 'Ana')
            ->assertJsonPath('data.0.comments_count', 2)
            ->assertJsonPath('data.0.images.0.url', asset('storage/posts/a.jpg'))
            ->assertJsonMissingPath('data.0.comments')
            ->assertJsonMissingPath('data.0.author.email');
    }

    public function test_v2_show_y_404(): void
    {
        $this->getJson("/api/v2/posts/{$this->post->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $this->post->id)
            ->assertJsonPath('data.author.name', 'Ana')
            ->assertJsonPath('data.comments_count', 2);

        $this->getJson('/api/v2/posts/999')->assertStatus(404);
    }

    public function test_v2_post_sin_duenio_tiene_author_null(): void
    {
        $viejo = Post::create(['title' => 'Parte 1', 'content' => 'Sin dueño']);

        $this->getJson("/api/v2/posts/{$viejo->id}")->assertJsonPath('data.author', null);
    }
}
