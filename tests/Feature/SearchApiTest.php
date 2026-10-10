<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Post::create(['title' => 'Mi viaje a Cartagena', 'content' => 'Playa y sol']);
        Post::create(['title' => 'Clase de backend', 'content' => 'Hoy en la facultad vimos Laravel']);
        Post::create(['title' => 'Receta', 'content' => 'Arepas con queso']);
    }

    public function test_busca_por_titulo(): void
    {
        $this->getJson('/api/posts?search=viaje')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Mi viaje a Cartagena');
    }

    public function test_busca_por_contenido(): void
    {
        $this->getJson('/api/posts?search=facultad')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Clase de backend');
    }

    public function test_busqueda_sin_coincidencias_devuelve_data_vacio(): void
    {
        $this->getJson('/api/posts?search=inexistente')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('total', 0);
    }

    public function test_search_y_page_juntos_y_links_conservan_search(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            Post::create(['title' => "Viaje {$i}", 'content' => 'x']);
        }

        // 13 coincidencias ("Mi viaje a Cartagena" + 12): 10 en la página 1 y 3 en la 2
        $page1 = $this->getJson('/api/posts?search=viaje&page=1')
            ->assertJsonPath('total', 13)
            ->assertJsonCount(10, 'data');
        $this->assertStringContainsString('search=viaje', $page1->json('next_page_url'));

        $this->getJson('/api/posts?search=viaje&page=2')
            ->assertJsonCount(3, 'data');
    }
}
