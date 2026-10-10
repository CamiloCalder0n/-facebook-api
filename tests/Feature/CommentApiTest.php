<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_comentar_post_guarda_usuario_y_author_con_su_nombre(): void
    {
        $post = Post::create(['title' => 'T', 'content' => 'C']);
        $user = User::factory()->create(['name' => 'Ana']);
        Sanctum::actingAs($user);

        $this->postJson("/api/posts/{$post->id}/comments", ['content' => 'Buen post'])
            ->assertStatus(201)
            ->assertJsonPath('author', 'Ana')
            ->assertJsonPath('user_id', $user->id)
            ->assertJsonPath('post_id', $post->id);

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id, 'user_id' => $user->id, 'author' => 'Ana', 'content' => 'Buen post',
        ]);
    }

    public function test_author_del_body_se_ignora(): void
    {
        $post = Post::create(['title' => 'T', 'content' => 'C']);
        Sanctum::actingAs(User::factory()->create(['name' => 'Ana']));

        $this->postJson("/api/posts/{$post->id}/comments", ['author' => 'Impostor', 'content' => 'Hola'])
            ->assertStatus(201)
            ->assertJsonPath('author', 'Ana');
    }

    public function test_comentar_sin_content_devuelve_422(): void
    {
        $post = Post::create(['title' => 'T', 'content' => 'C']);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/posts/{$post->id}/comments", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['content']);

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_comentar_sin_token_devuelve_401(): void
    {
        $post = Post::create(['title' => 'T', 'content' => 'C']);

        $this->postJson("/api/posts/{$post->id}/comments", ['content' => 'Hola'])
            ->assertStatus(401);

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_comentar_post_inexistente_devuelve_404(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/posts/999/comments', ['content' => 'Hola'])
            ->assertStatus(404);
    }

    public function test_listar_comentarios(): void
    {
        $post = Post::create(['title' => 'T', 'content' => 'C']);
        $post->comments()->create(['author' => 'Ana', 'content' => 'Uno']);
        $post->comments()->create(['author' => 'Luis', 'content' => 'Dos']);

        $this->getJson("/api/posts/{$post->id}/comments")
            ->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonStructure([['id', 'post_id', 'author', 'content']]);
    }
}
