<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostImage;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * @group Publicaciones v1
 *
 * Publicaciones con imágenes, comentarios y likes (formato v1, también disponible sin prefijo en /api/...).
 */
class PostController extends Controller
{
    /**
     * Listar publicaciones
     *
     * Devuelve las publicaciones más recientes paginadas de a 10, con su autor, imágenes (con image_url) y comentarios.
     *
     * @unauthenticated
     *
     * @queryParam page integer Número de página. Example: 1
     * @queryParam search string Filtra por coincidencia en el título o el contenido. Example: hola
     *
     * @response 200 {"current_page": 1, "data": [{"id": 1, "user_id": 1, "title": "Mi primera publicación", "content": "Hola a todos", "likes_count": 1, "created_at": "2026-10-09T12:00:00.000000Z", "updated_at": "2026-10-09T12:00:00.000000Z", "user": {"id": 1, "name": "Camilo Calderón"}, "images": [{"id": 1, "post_id": 1, "image_path": "posts/foto.jpg", "created_at": "2026-10-09T12:00:00.000000Z", "updated_at": "2026-10-09T12:00:00.000000Z", "image_url": "http://localhost/storage/posts/foto.jpg"}], "comments": [{"id": 1, "post_id": 1, "user_id": 2, "author": "Ana Gómez", "content": "¡Buena publicación!", "created_at": "2026-10-09T12:05:00.000000Z", "updated_at": "2026-10-09T12:05:00.000000Z"}]}], "first_page_url": "http://localhost/api/v1/posts?page=1", "from": 1, "last_page": 1, "last_page_url": "http://localhost/api/v1/posts?page=1", "links": [{"url": null, "label": "&laquo; Previous", "active": false}, {"url": "http://localhost/api/v1/posts?page=1", "label": "1", "active": true}, {"url": null, "label": "Next &raquo;", "active": false}], "next_page_url": null, "path": "http://localhost/api/v1/posts", "per_page": 10, "prev_page_url": null, "to": 1, "total": 1}
     */
    public function index(Request $request)
    {
        // ?search= filtra en SQL por título o contenido; el OR va agrupado en su propio where
        // Paginado de a 10: data + current_page, last_page, per_page, total, links...
        $posts = Post::with(['user:id,name', 'images', 'comments'])
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) =>
                $w->whereLike('title', "%{$s}%")->orWhereLike('content', "%{$s}%")))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($post) => $this->addImageUrls($post));

        return response()->json($posts, 200);
    }

    /**
     * Ver publicación
     *
     * Devuelve una publicación con su autor, imágenes (con image_url) y comentarios.
     *
     * @unauthenticated
     *
     * @urlParam id integer required ID de la publicación. Example: 1
     *
     * @response 200 {"id": 1, "user_id": 1, "title": "Mi primera publicación", "content": "Hola a todos", "likes_count": 1, "created_at": "2026-10-09T12:00:00.000000Z", "updated_at": "2026-10-09T12:00:00.000000Z", "user": {"id": 1, "name": "Camilo Calderón"}, "images": [{"id": 1, "post_id": 1, "image_path": "posts/foto.jpg", "created_at": "2026-10-09T12:00:00.000000Z", "updated_at": "2026-10-09T12:00:00.000000Z", "image_url": "http://localhost/storage/posts/foto.jpg"}], "comments": [{"id": 1, "post_id": 1, "user_id": 2, "author": "Ana Gómez", "content": "¡Buena publicación!", "created_at": "2026-10-09T12:05:00.000000Z", "updated_at": "2026-10-09T12:05:00.000000Z"}]}
     * @response 404 {"message": "No query results for model [App\\Models\\Post] 999"}
     */
    public function show($id)
    {
        $post = Post::with(['user:id,name', 'images', 'comments'])->findOrFail($id);

        return response()->json($this->addImageUrls($post), 200);
    }

    /**
     * Crear publicación
     *
     * Crea una publicación a nombre del usuario autenticado. Las imágenes se envían como multipart/form-data.
     *
     * @authenticated
     *
     * @bodyParam title string required Título. Máximo 255 caracteres. Example: Mi primera publicación
     * @bodyParam content string required Contenido de la publicación. Example: Hola a todos
     * @bodyParam images file[] Imágenes opcionales (jpeg, png, jpg o gif; máximo 2 MB cada una).
     *
     * @response 201 {"user_id": 1, "title": "Mi primera publicación", "content": "Hola a todos", "updated_at": "2026-10-09T12:00:00.000000Z", "created_at": "2026-10-09T12:00:00.000000Z", "id": 1, "user": {"id": 1, "name": "Camilo Calderón"}, "images": [{"id": 1, "post_id": 1, "image_path": "posts/foto.jpg", "created_at": "2026-10-09T12:00:00.000000Z", "updated_at": "2026-10-09T12:00:00.000000Z", "image_url": "http://localhost/storage/posts/foto.jpg"}]}
     * @response 401 {"message": "Unauthenticated."}
     * @response 422 {"message": "The title field is required.", "errors": {"title": ["The title field is required."]}}
     */
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

    /**
     * Dar like
     *
     * Registra el like del usuario autenticado. Cada usuario puede dar un solo like por publicación.
     *
     * @authenticated
     *
     * @urlParam id integer required ID de la publicación. Example: 1
     *
     * @response 200 {"message": "Me gusta añadido correctamente", "likes_count": 1}
     * @response 409 {"message": "Ya diste like a esta publicación", "likes_count": 1}
     * @response 401 {"message": "Unauthenticated."}
     * @response 404 {"message": "No query results for model [App\\Models\\Post] 999"}
     */
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

    /**
     * Eliminar publicación
     *
     * Elimina la publicación junto con sus imágenes y comentarios. Solo el dueño puede eliminarla.
     *
     * @authenticated
     *
     * @urlParam id integer required ID de la publicación. Example: 1
     *
     * @response 204 scenario="Eliminada"
     * @response 401 {"message": "Unauthenticated."}
     * @response 403 {"message": "This action is unauthorized."}
     * @response 404 {"message": "No query results for model [App\\Models\\Post] 999"}
     */
    public function destroy($id)
    {
        $post = Post::findOrFail($id);

        // 403 si no es el dueño (PostPolicy)
        Gate::authorize('delete', $post);

        foreach ($post->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        // La BD borra post_images, comments y post_user por la FK con cascada
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
