<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostImage;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index(Request $request)
    {
        // ?search= filtra en SQL por título o contenido; el OR va agrupado en su propio where
        // Paginado de a 10: data + current_page, last_page, per_page, total, links...
        $posts = Post::with(['user:id,name', 'images', 'comments'])
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) =>
                $w->where('title', 'like', "%{$s}%")->orWhere('content', 'like', "%{$s}%")))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($post) => $this->addImageUrls($post));

        return response()->json($posts, 200);
    }

    public function show($id)
    {
        $post = Post::with(['user:id,name', 'images', 'comments'])->findOrFail($id);

        return response()->json($this->addImageUrls($post), 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'content'  => 'required|string',
            'images'   => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $post = Post::create([
            'user_id' => $request->user()->id,
            'title'   => $request->title,
            'content' => $request->content,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('posts', 'public');
                PostImage::create([
                    'post_id'    => $post->id,
                    'image_path' => $path,
                ]);
            }
        }

        return response()->json($this->addImageUrls($post->load(['user:id,name', 'images'])), 201);
    }

    public function like(Request $request, $id)
    {
        $post = Post::findOrFail($id);
        $userId = $request->user()->id;

        if ($post->likers()->whereKey($userId)->exists()) {
            return $this->likeConflict($post);
        }

        try {
            $post->likers()->attach($userId);
        } catch (UniqueConstraintViolationException) {
            // Dos peticiones simultáneas del mismo usuario: el índice único frena la segunda
            return $this->likeConflict($post);
        }

        // La fuente de verdad es la tabla pivote; likes_count guarda el conteo real
        $post->update(['likes_count' => $post->likers()->count()]);

        return response()->json([
            'message'     => 'Me gusta añadido correctamente',
            'likes_count' => $post->likes_count,
        ], 200);
    }

    private function likeConflict(Post $post)
    {
        return response()->json([
            'message'     => 'Ya diste like a esta publicación',
            'likes_count' => $post->likers()->count(),
        ], 409);
    }

    public function destroy($id)
    {
        $post = Post::findOrFail($id);

        // 403 si no es el dueño (PostPolicy)
        Gate::authorize('delete', $post);

        foreach ($post->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        // La BD borra post_images y comments por la FK con cascada
        $post->delete();

        return response()->json(null, 204);
    }

    // Agrega la URL pública completa a cada imagen del post
    private function addImageUrls(Post $post): Post
    {
        $post->images->transform(function ($img) {
            $img->image_url = asset('storage/' . $img->image_path);
            return $img;
        });

        return $post;
    }
}
