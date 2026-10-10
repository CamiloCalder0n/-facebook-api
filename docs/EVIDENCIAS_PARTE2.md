# Evidencias · Parte 2

Capturas para el PDF de la Parte 2. Guárdalas directo en una carpeta (por ejemplo `Escritorio/evidencias-parte2`) con `Cmd + Shift + 5` → Opciones → Guardar en → Otra ubicación. Así no se pierden como las capturas temporales de la Parte 1.

Antes de empezar:

```bash
php artisan migrate:fresh
php artisan serve
```

## Postman (una captura por prueba, con el status y el JSON visibles)

| # | Archivo | Prueba | Esperado |
|---|---|---|---|
| 01 | `P2-01-registrar-a-201.png` | Registrar usuario A · `POST /api/v1/register` | 201 + token |
| 02 | `P2-02-registrar-b-201.png` | Registrar usuario B | 201 |
| 03 | `P2-03-login-a-200.png` | Login A (guarda `token`) | 200 |
| 04 | `P2-04-login-b-200.png` | Login B (guarda `token_b`) | 200 |
| 05 | `P2-05-login-clave-mala-401.png` | Login con clave incorrecta | 401 `Credenciales inválidas` |
| 06 | `P2-06-user-200.png` | `GET /api/v1/user` con token | 200 |
| 07 | `P2-07-crear-post-sin-token-401.png` | Crear post sin token (No Auth) | 401 |
| 08 | `P2-08-crear-post-201.png` | Crear post con imágenes (token A) | 201 con `user_id` |
| 09 | `P2-09-crear-mas-posts.png` | 10 posts más (Runner de Postman) | 201 |
| 10 | `P2-10-paginacion-page-1.png` | `GET /api/v1/posts?page=1` | 200, 10 elementos, `total` |
| 11 | `P2-11-paginacion-page-2.png` | `GET /api/v1/posts?page=2` | 200, el resto |
| 12 | `P2-12-busqueda-viaje.png` | `GET /api/v1/posts?search=viaje` | 200, solo coincidencias |
| 13 | `P2-13-busqueda-facultad.png` | `GET /api/v1/posts?search=facultad` | 200 |
| 14 | `P2-14-like-a-200.png` | Like con token A | 200, `likes_count: 1` |
| 15 | `P2-15-like-repetido-409.png` | Mismo like otra vez con token A | 409, `likes_count: 1` |
| 16 | `P2-16-like-b-200.png` | Like con token B | 200, `likes_count: 2` |
| 17 | `P2-17-comentar-sin-token-401.png` | Comentar sin token | 401 |
| 18 | `P2-18-comentar-b-201.png` | B comenta el post de A | 201 |
| 19 | `P2-19-listar-comentarios-200.png` | Listar comentarios | 200 |
| 20 | `P2-20-v1-vs-alias.png` | `GET /api/v1/posts` y `GET /api/posts` | 200, mismo formato |
| 21 | `P2-21-v2-posts.png` | `GET /api/v2/posts` | 200, formato nuevo |
| 22 | `P2-22-borrar-ajeno-403.png` | B intenta borrar el post de A | 403 |
| 23 | `P2-23-borrar-propio-204.png` | A borra su post | 204 |
| 24 | `P2-24-ver-borrado-404.png` | Ver el post borrado | 404 |
| 25 | `P2-25-logout-y-user-401.png` | Logout A y luego `GET /api/v1/user` con ese token | 200 y luego 401 |

## Terminal y navegador

| Archivo | Qué muestra |
|---|---|
| `P2-26-terminal-antes-de-borrar.png` | Antes de la prueba 23 (ver el comando abajo) |
| `P2-27-terminal-despues-de-borrar.png` | Después de la prueba 23: archivos borrados y cascada en `post_images`, `comments` y `post_user` |
| `P2-28-mailtrap-nuevo-comentario.png` | Bandeja de Mailtrap con el correo "Nuevo comentario" (después de la prueba 18) |
| `P2-29-docs-scribe.png` | `http://127.0.0.1:8000/docs` en el navegador |
| `P2-30-php-artisan-test.png` | `php artisan test` en verde |
| `P2-31-route-list.png` | `php artisan route:list --path=api` (v1, alias y v2) |
| `P2-32-github-repo.png` | Repositorio en GitHub con los commits de la Parte 2 |

Comando para las capturas 26 y 27 (córrelo en una terminal distinta a la de `php artisan serve`):

```bash
ls -la storage/app/public/posts && php artisan tinker --execute="echo 'posts='.App\Models\Post::count().' post_images='.App\Models\PostImage::count().' comments='.App\Models\Comment::count().' likes='.DB::table('post_user')->count();"
```
