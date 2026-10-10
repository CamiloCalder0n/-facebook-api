<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @group Autenticación
 *
 * Registro, inicio y cierre de sesión con tokens de Laravel Sanctum.
 */
class AuthController extends Controller
{
    /**
     * Registrar usuario
     *
     * Crea una cuenta nueva y devuelve el usuario junto con un token Bearer de Sanctum.
     *
     * @unauthenticated
     *
     * @bodyParam name string required Nombre del usuario. Máximo 255 caracteres. Example: Camilo Calderón
     * @bodyParam email string required Correo electrónico único. Example: camilo@example.com
     * @bodyParam password string required Contraseña de al menos 8 caracteres. Example: secreto123
     * @bodyParam password_confirmation string required Debe coincidir con password. Example: secreto123
     *
     * @response 201 {"user": {"name": "Camilo Calderón", "email": "camilo@example.com", "updated_at": "2026-10-09T12:00:00.000000Z", "created_at": "2026-10-09T12:00:00.000000Z", "id": 1}, "token": "1|aBcDeFgHiJkLmNoPqRsTuVwXyZ0123456789abcd"}
     * @response 422 {"message": "The email has already been taken.", "errors": {"email": ["The email has already been taken."]}}
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create($data);

        return response()->json([
            'user'  => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ], 201);
    }

    /**
     * Iniciar sesión
     *
     * Valida las credenciales y devuelve el usuario con un token Bearer nuevo.
     *
     * @unauthenticated
     *
     * @bodyParam email string required Correo electrónico registrado. Example: camilo@example.com
     * @bodyParam password string required Contraseña del usuario. Example: secreto123
     *
     * @response 200 {"user": {"id": 1, "name": "Camilo Calderón", "email": "camilo@example.com", "email_verified_at": null, "created_at": "2026-10-09T12:00:00.000000Z", "updated_at": "2026-10-09T12:00:00.000000Z"}, "token": "2|aBcDeFgHiJkLmNoPqRsTuVwXyZ0123456789abcd"}
     * @response 401 {"message": "Credenciales inválidas"}
     * @response 422 {"message": "The email field is required.", "errors": {"email": ["The email field is required."]}}
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        return response()->json([
            'user'  => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ], 200);
    }

    /**
     * Cerrar sesión
     *
     * Revoca únicamente el token con el que se hizo la petición.
     *
     * @authenticated
     *
     * @response 200 {"message": "Sesión cerrada"}
     * @response 401 {"message": "Unauthenticated."}
     */
    public function logout(Request $request)
    {
        // Revoca solo el token con el que se hizo esta petición
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada'], 200);
    }

    /**
     * Usuario autenticado
     *
     * Devuelve los datos del usuario dueño del token.
     *
     * @authenticated
     *
     * @response 200 {"id": 1, "name": "Camilo Calderón", "email": "camilo@example.com", "email_verified_at": null, "created_at": "2026-10-09T12:00:00.000000Z", "updated_at": "2026-10-09T12:00:00.000000Z"}
     * @response 401 {"message": "Unauthenticated."}
     */
    public function me(Request $request)
    {
        return response()->json($request->user(), 200);
    }
}
