<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_comentar_post(): void
    {
        $post = Post::create(['title' => 'T', 'content' => 'C']);

        $this->postJson("/api/posts/{$post->id}/comments", ['author' => 'Ana', 'content' => 'Buen post'])
            ->assertStatus(201)
            ->assertJsonPath('author', 'Ana')
            ->assertJsonPath('post_id', $post->id);

        $this->assertDatabaseHas('comments', ['post_id' => $post->id, 'author' => 'Ana', 'content' => 'Buen post']);
    }

    public function test_comentar_sin_author_devuelve_422(): void
    {
        $post = Post::create(['title' => 'T', 'content' => 'C']);

        $this->postJson("/api/posts/{$post->id}/comments", ['content' => 'Hola'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['author']);

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_comentar_post_inexistente_devuelve_404(): void
    {
        $this->postJson('/api/posts/999/comments', ['author' => 'Ana', 'content' => 'Hola'])
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
