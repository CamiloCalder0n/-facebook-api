<?php

namespace Tests\Feature;

use App\Mail\NewCommentMail;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_comentar_post_ajeno_envia_correo_al_duenio(): void
    {
        $owner = User::factory()->create(['email' => 'duenio@example.com']);
        $post = Post::create(['user_id' => $owner->id, 'title' => 'Mi viaje', 'content' => 'C']);
        Sanctum::actingAs(User::factory()->create(['name' => 'Luis']));

        $this->postJson("/api/posts/{$post->id}/comments", ['content' => '¡Qué buenas fotos!'])
            ->assertStatus(201);

        Mail::assertSent(NewCommentMail::class, function (NewCommentMail $mail) {
            return $mail->hasTo('duenio@example.com')
                && $mail->hasSubject('Nuevo comentario')
                && $mail->comment->author === 'Luis';
        });
        Mail::assertSentCount(1);
    }

    public function test_comentar_tu_propio_post_no_envia_correo(): void
    {
        $owner = User::factory()->create();
        $post = Post::create(['user_id' => $owner->id, 'title' => 'T', 'content' => 'C']);
        Sanctum::actingAs($owner);

        $this->postJson("/api/posts/{$post->id}/comments", ['content' => 'Hola'])->assertStatus(201);

        Mail::assertNothingSent();
    }

    public function test_post_sin_duenio_no_envia_correo(): void
    {
        $post = Post::create(['title' => 'Parte 1', 'content' => 'C']);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/posts/{$post->id}/comments", ['content' => 'Hola'])->assertStatus(201);

        Mail::assertNothingSent();
    }

    public function test_el_correo_muestra_post_autor_y_comentario(): void
    {
        $owner = User::factory()->create();
        $post = Post::create(['user_id' => $owner->id, 'title' => 'Mi viaje', 'content' => 'C']);
        $comment = $post->comments()->create(['author' => 'Luis', 'content' => '¡Qué buenas fotos!']);

        (new NewCommentMail($comment))
            ->assertHasSubject('Nuevo comentario')
            ->assertSeeInHtml('Mi viaje')
            ->assertSeeInHtml('Luis')
            ->assertSeeInHtml('¡Qué buenas fotos!');
    }
}
