<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    private function registrar(array $overrides = [])
    {
        return $this->postJson('/api/register', array_merge([
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ], $overrides));
    }

    public function test_registro_devuelve_201_y_token(): void
    {
        $this->registrar()
            ->assertStatus(201)
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token'])
            ->assertJsonMissingPath('user.password');

        $this->assertDatabaseHas('users', ['email' => 'ana@example.com']);
    }

    public function test_registro_con_email_repetido_devuelve_422(): void
    {
        User::factory()->create(['email' => 'ana@example.com']);

        $this->registrar()
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_login_correcto_devuelve_200_y_token(): void
    {
        User::factory()->create(['email' => 'ana@example.com', 'password' => 'secreto123']);

        $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'secreto123'])
            ->assertStatus(200)
            ->assertJsonStructure(['user', 'token']);
    }

    public function test_login_con_clave_incorrecta_devuelve_401(): void
    {
        User::factory()->create(['email' => 'ana@example.com', 'password' => 'secreto123']);

        $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'otra-clave'])
            ->assertStatus(401)
            ->assertExactJson(['message' => 'Credenciales inválidas']);
    }

    public function test_user_sin_token_devuelve_401_y_con_token_200(): void
    {
        $this->getJson('/api/user')->assertStatus(401);

        $token = $this->registrar()->json('token');

        $this->withToken($token)->getJson('/api/user')
            ->assertStatus(200)
            ->assertJsonPath('email', 'ana@example.com');
    }

    public function test_logout_invalida_el_token(): void
    {
        $token = $this->registrar()->json('token');

        $this->withToken($token)->postJson('/api/logout')
            ->assertStatus(200)
            ->assertJsonPath('message', 'Sesión cerrada');

        $this->assertSame(0, PersonalAccessToken::count());

        // El guard cachea el usuario dentro de la misma petición de prueba
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/user')->assertStatus(401);
    }

    public function test_logout_revoca_solo_el_token_actual(): void
    {
        $user = User::factory()->create();
        $tokenWeb = $user->createToken('web')->plainTextToken;
        $tokenMovil = $user->createToken('movil')->plainTextToken;

        $this->withToken($tokenWeb)->postJson('/api/logout')->assertStatus(200);
        $this->app['auth']->forgetGuards();

        $this->withToken($tokenWeb)->getJson('/api/user')->assertStatus(401);
        $this->app['auth']->forgetGuards();
        $this->withToken($tokenMovil)->getJson('/api/user')->assertStatus(200);
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_login_sin_datos_devuelve_422(): void
    {
        $this->postJson('/api/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_logout_sin_token_devuelve_401(): void
    {
        $this->postJson('/api/logout')->assertStatus(401);
    }

    public function test_registro_con_datos_invalidos_devuelve_422(): void
    {
        $this->postJson('/api/register', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);

        $this->registrar(['email' => 'no-es-correo', 'password' => 'corta', 'password_confirmation' => 'otra'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);

        $this->assertDatabaseCount('users', 0);
    }
}
