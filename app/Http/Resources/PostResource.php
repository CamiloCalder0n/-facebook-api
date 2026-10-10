<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Formato v2: autor como objeto, imágenes como {id, url}
     * y comments_count en lugar del arreglo completo de comentarios.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'title'          => $this->title,
            'content'        => $this->content,
            'author'         => $this->user ? new UserResource($this->user) : null,
            'images'         => ImageResource::collection($this->whenLoaded('images')),
            'likes_count'    => $this->likes_count,
            'comments_count' => $this->comments_count,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
