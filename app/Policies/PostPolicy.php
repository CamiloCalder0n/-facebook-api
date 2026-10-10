<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Solo el dueño puede borrar su publicación. Los posts sin dueño
     * (creados en la Parte 1, user_id null) no los puede borrar nadie.
     */
    public function delete(User $user, Post $post): bool
    {
        return $post->user_id !== null && $user->id === $post->user_id;
    }
}
