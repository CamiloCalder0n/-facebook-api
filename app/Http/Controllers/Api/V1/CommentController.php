<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\NewCommentMail;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CommentController extends Controller
{
    public function index($postId)
    {
        $post = Post::findOrFail($postId);

        return response()->json($post->comments()->latest()->get(), 200);
    }

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
