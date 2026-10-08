<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function createPostWithImages(int $count = 2): array
    {
        $images = [];
        for ($i = 0; $i < $count; $i++) {
            $images[] = UploadedFile::fake()->image("img{$i}.jpg");
        }

        $response = $this->post('/api/posts', [
            'title' => 'Mi post',
            'content' => 'Contenido',
            'images' => $images,
        ], ['Accept' => 'application/json']);

        return [$response, Post::find($response->json('id'))];
    }

    public function test_crear_post_con_dos_imagenes(): void
    {
        [$response] = $this->createPostWithImages(2);

        $response->assertStatus(201)
            ->assertJsonPath('title', 'Mi post')
            ->assertJsonCount(2, 'images')
            ->assertJsonStructure(['id', 'title', 'content', 'images' => [['id', 'post_id', 'image_path', 'image_url']]]);

        $this->assertCount(2, Storage::disk('public')->files('posts'));
        $this->assertDatabaseCount('post_images', 2);

        foreach ($response->json('images') as $img) {
            Storage::disk('public')->assertExists($img['image_path']);
            $this->assertStringEndsWith('storage/'.$img['image_path'], $img['image_url']);
        }
    }

    public function test_crear_post_sin_titulo_devuelve_422(): void
    {
        $this->postJson('/api/posts', ['content' => 'Contenido'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_crear_post_con_pdf_devuelve_422(): void
    {
        $this->post('/api/posts', [
            'title' => 'Post',
            'content' => 'Contenido',
            'images' => [UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')],
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['images.0']);

        $this->assertDatabaseCount('posts', 0);
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_listar_posts(): void
    {
        [, $post] = $this->createPostWithImages(1);
        $post->comments()->create(['author' => 'Ana', 'content' => 'Hola']);

        $this->getJson('/api/posts')
            ->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonStructure([[
                'id', 'title', 'content', 'likes_count',
                'images' => [['id', 'image_path', 'image_url']],
                'comments' => [['id', 'author', 'content']],
            ]]);
    }

    public function test_ver_post_existente(): void
    {
        $post = Post::create(['title' => 'T', 'content' => 'C']);

        $this->getJson("/api/posts/{$post->id}")
            ->assertStatus(200)
            ->assertJsonPath('id', $post->id)
            ->assertJsonStructure(['id', 'title', 'content', 'images', 'comments']);
    }

    public function test_ver_post_inexistente_devuelve_404(): void
    {
        $this->getJson('/api/posts/999')->assertStatus(404);
    }

    public function test_like_incrementa_contador(): void
    {
        $post = Post::create(['title' => 'T', 'content' => 'C']);
        $this->assertEquals(0, $post->fresh()->likes_count);

        $this->postJson("/api/posts/{$post->id}/like")
            ->assertStatus(200)
            ->assertJsonStructure(['message', 'likes_count'])
            ->assertJsonPath('likes_count', 1);

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'likes_count' => 1]);
    }

    public function test_borrar_post_elimina_archivos_y_filas(): void
    {
        [, $post] = $this->createPostWithImages(2);
        $post->comments()->create(['author' => 'Ana', 'content' => 'Hola']);
        $paths = PostImage::pluck('image_path')->all();
        $this->assertCount(2, $paths);

        $response = $this->deleteJson("/api/posts/{$post->id}");
        $response->assertStatus(204)->assertNoContent();
        $this->assertSame('', $response->getContent());

        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertDatabaseCount('post_images', 0);
        $this->assertDatabaseCount('comments', 0);
        $this->assertSame(0, Comment::count());
    }

    public function test_metodo_incorrecto_en_like_devuelve_405(): void
    {
        $post = Post::create(['title' => 'T', 'content' => 'C']);

        $this->getJson("/api/posts/{$post->id}/like")->assertStatus(405);
    }
}
