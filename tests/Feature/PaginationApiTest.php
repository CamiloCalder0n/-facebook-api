<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationApiTest extends TestCase
{
    use RefreshDatabase;

    private function crearPosts(int $n): void
    {
        for ($i = 1; $i <= $n; $i++) {
            Post::create(['title' => "Post {$i}", 'content' => "Contenido {$i}"]);
        }
    }

    public function test_respuesta_trae_datos_e_info_de_paginacion(): void
    {
        $this->crearPosts(15);

        $this->getJson('/api/posts')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data', 'current_page', 'last_page', 'per_page', 'total',
                'links', 'next_page_url', 'prev_page_url',
            ])
            ->assertJsonPath('per_page', 10)
            ->assertJsonPath('total', 15)
            ->assertJsonPath('last_page', 2);
    }

    public function test_pagina_1_trae_10_y_pagina_2_trae_5(): void
    {
        $this->crearPosts(15);

        $this->getJson('/api/posts?page=1')
            ->assertJsonPath('current_page', 1)
            ->assertJsonCount(10, 'data');

        $this->getJson('/api/posts?page=2')
            ->assertJsonPath('current_page', 2)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('next_page_url', null);
    }

    public function test_pagina_fuera_de_rango_devuelve_data_vacio(): void
    {
        $this->crearPosts(15);

        $this->getJson('/api/posts?page=99')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('total', 15);
    }

    public function test_las_imagenes_conservan_image_url(): void
    {
        $post = Post::create(['title' => 'T', 'content' => 'C']);
        $post->images()->create(['image_path' => 'posts/a.jpg']);

        $this->getJson('/api/posts')
            ->assertJsonPath('data.0.images.0.image_url', asset('storage/posts/a.jpg'));
    }
}
