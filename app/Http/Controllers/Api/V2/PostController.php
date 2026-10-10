<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::with(['user:id,name', 'images'])
            ->withCount('comments')
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) =>
                $w->where('title', 'like', "%{$s}%")->orWhere('content', 'like', "%{$s}%")))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return PostResource::collection($posts);
    }

    public function show($id)
    {
        $post = Post::with(['user:id,name', 'images'])->withCount('comments')->findOrFail($id);

        return new PostResource($post);
    }
}
