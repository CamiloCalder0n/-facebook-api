<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LikeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_primer_like_devuelve_200_y_conteo_1(): void
    {
        $post = Post::create(['title' => 'T', 'content' => 'C']);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/posts/{$post->id}/like")
            ->assertStatus(200)
            ->assertJsonPath('message', 'Me gusta añadido correctamente')
            ->assertJsonPath('likes_count', 1);

        $this->assertDatabaseHas('post_user', ['post_id' => $post->id, 'user_id' => $user->id]);
        $this->assertSame(1, $post->fresh()->likes_count);
    }

    public function test_segundo_like_del_mismo_usuario_devuelve_409_y_no_suma(): void
    {
        $post = Post::create(['title' => 'T', 'content' => 'C']);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/posts/{$post->id}/like")->assertStatus(200);

        $this->postJson("/api/posts/{$post->id}/like")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Ya diste like a esta publicación')
            ->assertJsonPath('likes_count', 1);

        $this->assertDatabaseCount('post_user', 1);
        $this->assertSame(1, $post->fresh()->likes_count);
    }

    public function test_dos_usuarios_distintos_suman_2(): void
    {
        $post = Post::create(['title' => 'T', 'content' => 'C']);

        Sanctum::actingAs(User::factory()->create());
        $this->postJson("/api/posts/{$post->id}/like")->assertJsonPath('likes_count', 1);

        Sanctum::actingAs(User::factory()->create());
        $this->postJson("/api/posts/{$post->id}/like")
            ->assertStatus(200)
            ->assertJsonPath('likes_count', 2);

        $this->assertSame(2, $post->fresh()->likes_count);
    }

    public function test_like_a_post_inexistente_devuelve_404(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/posts/999/like')->assertStatus(404);
    }

    public function test_la_bd_rechaza_un_like_duplicado(): void
    {
        $post = Post::create(['title' => 'T', 'content' => 'C']);
        $user = User::factory()->create();
        $post->likers()->attach($user->id);

        $this->expectException(UniqueConstraintViolationException::class);
        $post->likers()->attach($user->id);
    }

    public function test_borrar_post_borra_sus_likes_por_cascada(): void
    {
        $user = User::factory()->create();
        $post = Post::create(['user_id' => $user->id, 'title' => 'T', 'content' => 'C']);
        $post->likers()->attach(User::factory()->create()->id);
        Sanctum::actingAs($user);

        $this->deleteJson("/api/posts/{$post->id}")->assertStatus(204);
        $this->assertDatabaseCount('post_user', 0);
    }
}
