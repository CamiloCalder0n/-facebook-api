<?php

namespace Tests\Feature;

use Tests\TestCase;

class DocsTest extends TestCase
{
    public function test_la_documentacion_responde_200(): void
    {
        $this->get('/docs')
            ->assertStatus(200)
            ->assertSee('Facebook API · Documentación', false);
    }

    public function test_coleccion_postman_y_openapi_de_scribe_estan_disponibles(): void
    {
        $this->get('/docs.postman')->assertStatus(200);
        $this->get('/docs.openapi')->assertStatus(200);
    }
}
