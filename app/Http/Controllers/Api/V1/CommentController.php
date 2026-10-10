<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\NewCommentMail;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * @group Comentarios v1
 *
 * Comentarios de las publicaciones.
 */
class CommentController extends Controller
{
    /**
     * Listar comentarios
     *
     * Devuelve los comentarios de una publicación, del más reciente al más antiguo.
     *
     * @unauthenticated
     *
     * @urlParam id integer required ID de la publicación. Example: 1
     *
     * @response 200 [{"id": 1, "post_id": 1, "user_id": 2, "author": "Ana Gómez", "content": "¡Buena publicación!", "created_at": "2026-10-09T12:05:00.000000Z", "updated_at": "2026-10-09T12:05:00.000000Z"}]
     * @response 404 {"message": "No query results for model [App\\Models\\Post] 999"}
     */
    public function index($postId)
    {
        $post = Post::findOrFail($postId);

        return response()->json($post->comments()->latest()->get(), 200);
    }

    /**
     * Crear comentario
     *
     * Agrega un comentario a la publicación; author se llena con el nombre del usuario autenticado y user_id con su ID.
     * Si el comentarista no es el dueño, se envía al dueño del post el correo "Nuevo comentario".
     *
     * @authenticated
     *
     * @urlParam id integer required ID de la publicación. Example: 1
     *
     * @bodyParam content string required Texto del comentario. Example: ¡Buena publicación!
     *
     * @response 201 {"user_id": 2, "author": "Ana Gómez", "content": "¡Buena publicación!", "post_id": 1, "updated_at": "2026-10-09T12:05:00.000000Z", "created_at": "2026-10-09T12:05:00.000000Z", "id": 1}
     * @response 401 {"message": "Unauthenticated."}
     * @response 404 {"message": "No query results for model [App\\Models\\Post] 999"}
     * @response 422 {"message": "The content field is required.", "errors": {"content": ["The content field is required."]}}
     */
    public function store(Request $request, $postId)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $post = Post::findOrFail($postId);

        // El autor es el usuario autenticado (author se llena con su nombre)
        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'author'  => $request->user()->name,
            'content' => $request->content,
        ]);

        // Avisa al dueño del post (no a sí mismo, ni en posts sin dueño de la Parte 1)
        if ($post->user && $post->user_id !== $request->user()->id) {
            try {
                Mail::to($post->user->email)->send(new NewCommentMail($comment));
            } catch (Throwable $e) {
                // El comentario ya se guardó: un fallo del servidor de correo no debe convertirse en 500
                report($e);
            }
        }

        return response()->json($comment, 201);
    }
}
