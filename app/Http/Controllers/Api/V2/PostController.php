<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;

/**
 * @group Publicaciones v2
 *
 * Formato v2 con API Resources: autor como objeto, imágenes como {id, url} y comments_count.
 */
class PostController extends Controller
{
    /**
     * Listar publicaciones (v2)
     *
     * Devuelve las publicaciones más recientes paginadas de a 10 en formato v2.
     *
     * @unauthenticated
     *
     * @queryParam page integer Número de página. Example: 1
     * @queryParam search string Filtra por coincidencia en el título o el contenido. Example: hola
     *
     * @response 200 {"data": [{"id": 1, "title": "Mi primera publicación", "content": "Hola a todos", "author": {"id": 1, "name": "Camilo Calderón"}, "images": [{"id": 1, "url": "http://localhost/storage/posts/foto.jpg"}], "likes_count": 1, "comments_count": 1, "created_at": "2026-10-09T12:00:00.000000Z", "updated_at": "2026-10-09T12:00:00.000000Z"}], "links": {"first": "http://localhost/api/v2/posts?page=1", "last": "http://localhost/api/v2/posts?page=1", "prev": null, "next": null}, "meta": {"current_page": 1, "from": 1, "last_page": 1, "links": [{"url": null, "label": "&laquo; Previous", "active": false}, {"url": "http://localhost/api/v2/posts?page=1", "label": "1", "active": true}, {"url": null, "label": "Next &raquo;", "active": false}], "path": "http://localhost/api/v2/posts", "per_page": 10, "to": 1, "total": 1}}
     */
    public function index(Request $request)
    {
        $posts = Post::with(['user:id,name', 'images'])
            ->withCount('comments')
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) =>
                $w->whereLike('title', "%{$s}%")->orWhereLike('content', "%{$s}%")))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return PostResource::collection($posts);
    }

    /**
     * Ver publicación (v2)
     *
     * Devuelve una publicación en formato v2.
     *
     * @unauthenticated
     *
     * @urlParam id integer required ID de la publicación. Example: 1
     *
     * @response 200 {"data": {"id": 1, "title": "Mi primera publicación", "content": "Hola a todos", "author": {"id": 1, "name": "Camilo Calderón"}, "images": [{"id": 1, "url": "http://localhost/storage/posts/foto.jpg"}], "likes_count": 1, "comments_count": 1, "created_at": "2026-10-09T12:00:00.000000Z", "updated_at": "2026-10-09T12:00:00.000000Z"}}
     * @response 404 {"message": "No query results for model [App\\Models\\Post] 999"}
     */
    public function show($id)
    {
        $post = Post::with(['user:id,name', 'images'])->withCount('comments')->findOrFail($id);

        return new PostResource($post);
    }
}
