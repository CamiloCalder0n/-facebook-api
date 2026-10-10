# CLAUDE.md — API REST tipo Facebook (Laravel)

Taller de Arquitectura y Desarrollo Backend (UNAB), docente Fabian Enrique Suarez Carvajal.
Guía de pasos: `docs/PASOS_CLAUDE_CODE.md`. Fuentes: `docs/Taller_API_REST.md` (taller) y `docs/Building_Laravel_REST_APIs.md` (teoría).
Estado actual del trabajo: ver `PROGRESS.md`.

## Decisiones del proyecto (Paso 0)

- Nombre: `facebook-api`, en la raíz de este repo (no subcarpeta).
- Laravel 13.x (ya creado con `composer create-project`), PHP 8.4.
- BD: SQLite (`database/database.sqlite`).
- Tests: PHPUnit (default de Laravel).
- Repo git ya inicializado en rama `main`.
- Parte 1 sin autenticación; Parte 2 con Sanctum (tokens Bearer).
- Servidor: `php artisan serve` con PHP 8.4 en http://127.0.0.1:8000. No usar Apache de XAMPP (su PHP 8.2.4 no cumple el mínimo de Laravel 13).

## REGLAS DE TRABAJO (obligatorias)

1. **Un paso a la vez.** Al terminar cada paso: resume en máx. 5 líneas lo hecho, muestra la verificación, y **DETENTE** esperando mi "siguiente".
2. **NO ejecutes `git commit` ni `git push`.** Los commits los hago yo. Tú solo `git status` / `git diff --stat` y me dejas el **mensaje de commit sugerido** (formato Conventional Commits, en español, como pide el taller).
3. **No saltes nada.** Cada paso tiene Tareas + Verificación. Si no puedes verificar algo, dilo explícitamente; no lo marques como hecho.
4. **No inventes.** Si falta un dato (versión, nombre, credencial), pregúntame.
5. **Economía de tokens:**
   - No releas archivos que acabas de escribir; no imprimas archivos enormes.
   - Usa `php artisan route:list --path=api`, `git diff --stat`, y salidas recortadas (`| head -n 40`).
   - Subagentes (Agent) **solo** para tareas aisladas y pesadas (indicadas abajo como 🤖). Dales un prompt corto y autosuficiente y pídeles un resumen de máx. 10 líneas.
   - Al cerrar cada fase mayor, sugiéreme `/compact` (o `/clear` si todo está commiteado). El estado vive en `CLAUDE.md` y `PROGRESS.md`, no en la conversación.
6. **Idioma:** respóndeme en español colombiano casual, directo. Código, rutas y commits como indica el taller.
7. **Seguridad básica:** nunca commitear `.env`, `database.sqlite`, ni `storage/app/public/*` con imágenes de prueba (verifica `.gitignore`).

## Modelo de datos

```
posts (id, title, content, likes_count default 0, timestamps)
  ├─1:N─ post_images (id, post_id FK cascade, image_path, timestamps)
  └─1:N─ comments    (id, post_id FK cascade, author, content, timestamps)
```

## Endpoints (todos bajo /api)

| Método | Ruta | Content-Type | Código éxito |
|---|---|---|---|
| POST | /api/posts | multipart/form-data (title, content, images[]) | 201 |
| GET | /api/posts | json | 200 |
| GET | /api/posts/{id} | json | 200 |
| POST | /api/posts/{id}/comments | json (author, content) | 201 |
| GET | /api/posts/{id}/comments | json | 200 |
| POST | /api/posts/{id}/like | json | 200 |
| DELETE | /api/posts/{id} | json | 204 |

Principios REST: URL = sustantivo, método = verbo, nada de verbos en URLs; stateless (sin CSRF); `422` validación, `201` al crear, `204` al borrar sin cuerpo, `404` si no existe; nunca `200` con `{"error": ...}`.

## Convención de commits

Conventional Commits en español. Los hace Cami, no Claude. Orden esperado:

| # | Mensaje |
|---|---|
| 1 | `feat: crear proyecto Laravel` |
| 2 | `feat: crear modelos y migraciones para posts, comentarios e imágenes` |
| 3 | `feat: configurar relaciones entre Post, Comment y PostImage` |
| 4 | `feat: implementar CRUD y lógica de likes` |
| 5 | `feat: registrar endpoints de la API REST` |
| 6 | `feat: implementar creación de publicaciones con imágenes` *(opcional)* |
| 7 | `test: agregar pruebas feature de la API` *(opcional)* |
| 8a | `test: validar endpoints en Postman` *(si hubo ajustes)* |
| 8b | `test: exportar colección de Postman` |
| 9 | `fix: corregir errores de validación y almacenamiento` *(solo si hubo cambios)* |
| 10 | `docs: agregar README del proyecto` |

## Parte 2 · Retos avanzados

Guía: `docs/PASOS_CLAUDE_CODE_PARTE2.md`. Fuente: `docs/Taller_Parte_2_API_REST.md`. Ignorar `AGENTS.md` (Laravel Boost no es parte del taller).
La Parte 1 queda congelada en el tag `parte-1` (sobre `b2bf945`). Las migraciones de la Parte 1 no se editan: todo cambio de esquema va en migraciones nuevas. La suite (`php artisan test`) debe quedar en verde al cerrar cada paso.

### Decisiones confirmadas

| # | Decisión |
|---|---|
| D1 | Los 7 retos |
| D2 | Trabajo en `main` después del tag `parte-1` |
| D3 | Orden de la guía: Auth (Reto 1) → Likes (Reto 3) → Paginación (Reto 2) → Búsqueda (Reto 4) → Correo (Reto 5) → Versionado (Reto 6) → Docs (Reto 7) |
| D4 | Login con credenciales malas → 401 `{"message":"Credenciales inválidas"}` |
| D5 | Borrar post ajeno → 403 con `PostPolicy`. Los posts sin dueño (Parte 1, `user_id` null) tampoco se pueden borrar (403) |
| D6 | Comentarios guardan `user_id`; `author` = `user->name` (ya no es obligatorio en el body) |
| D7 | Like repetido → 409 Conflict |
| D8 | Fuente de verdad de likes: tabla `post_user`; `likes_count` se recalcula con el conteo real |
| D9 | Sin `DELETE /posts/{id}/like` (no lo pide el taller) |
| D10 | Correo: `log` en desarrollo, `Mail::fake()` en tests, Mailtrap Sandbox para la evidencia (credenciales solo en `.env`) |
| D11 | `/api/v1/*` = API actual; `/api/*` alias de v1 (mismas URLs; el comportamiento sí cambia respecto a la Parte 1: paginación y token, y se documenta así en el README); `/api/v2/posts` con API Resources |
| D12 | Documentación con Scribe (plan B: l5-swagger) |
| D13 | Colección Postman nueva "Facebook API - Taller Backend · Parte 2" → `facebook-api-parte2-postman-collection.json`; la de la Parte 1 no se toca |

No se corre la "Recomendación" del Reto 1 (`composer require laravel/sanctum` + `vendor:publish`): Sanctum ya vino con `install:api` y publicarlo duplicaría la migración de `personal_access_tokens`.

### Modelo de datos objetivo

```
users (id, name, email, password, ...)
posts (id, user_id FK nullable, title, content, likes_count, timestamps)
  ├─1:N─ post_images (id, post_id FK cascade, image_path, timestamps)
  ├─1:N─ comments    (id, post_id FK cascade, user_id FK nullable, author, content, timestamps)
  └─N:M─ users vía post_user (id, post_id FK cascade, user_id FK cascade, timestamps, UNIQUE(post_id,user_id))
```

### Endpoints objetivo (`/api/v1/...` y alias `/api/...`)

| Método | Ruta | Auth | Éxito | Errores |
|---|---|---|---|---|
| POST | /register | — | 201 + token | 422 |
| POST | /login | — | 200 + token | 401, 422 |
| POST | /logout | Bearer | 200 | 401 |
| GET | /user | Bearer | 200 | 401 |
| GET | /posts?page=&search= | — | 200 paginado | — |
| GET | /posts/{id} | — | 200 | 404 |
| POST | /posts | Bearer | 201 | 401, 422 |
| DELETE | /posts/{id} | Bearer (solo dueño) | 204 | 401, 403, 404 |
| POST | /posts/{id}/like | Bearer | 200 | 401, 404, 409 |
| GET | /posts/{id}/comments | — | 200 | 404 |
| POST | /posts/{id}/comments | Bearer | 201 (+ correo al dueño) | 401, 404, 422 |
| GET | /api/v2/posts, /api/v2/posts/{id} | — | 200 (formato nuevo) | 404 |
| GET | /docs | — | 200 | — |

### Commits Parte 2

`docs: agregar guía de la parte 2 del taller` · `feat: agregar registro e inicio de sesión con Sanctum` · `feat: proteger endpoints y asociar publicaciones y comentarios al usuario` · `feat: likes únicos por usuario con tabla pivote` · `feat: paginar el listado de publicaciones` · `feat: buscar publicaciones por título o contenido` · `feat: notificar por correo al dueño del post al comentar` · `feat: versionar la API en v1 y agregar v2` · `docs: documentar la API con Scribe` · `test: completar pruebas de la parte 2` (si aplica) · `test: exportar colección de Postman de la parte 2` · `docs: actualizar README con la parte 2`.
Cami hace los commits. Claude deja una "foto" del árbol al final de cada paso (`git add -A && git write-tree`) para que Cami los cree en orden con el script de commits.
