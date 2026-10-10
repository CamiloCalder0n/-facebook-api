# PASOS PARA CLAUDE CODE — PARTE 2 · Retos Avanzados de API REST (Laravel)

> Continuación de `docs/PASOS_CLAUDE_CODE.md` (Parte 1, ya entregada).
> Copia este archivo y `Taller_Parte_2_API_REST.md` en `docs/` del repo `facebook-api` y dile a Claude Code:
> **"Lee docs/PASOS_CLAUDE_CODE_PARTE2.md y empieza por el Paso 0."**
> Fuente: `docs/Taller_Parte_2_API_REST.md` (7 retos). Si algo de aquí contradice al taller, avísame y no decidas solo.

---

## REGLAS DE TRABAJO (las mismas de la Parte 1, obligatorias)

1. **Un paso a la vez.** Al terminar cada paso: resume en máx. 5 líneas lo hecho, muestra la verificación y **DETENTE** esperando mi "siguiente".
2. **NO ejecutes `git commit`, `git push` ni `git tag`.** Los hago yo. Tú solo `git status` / `git diff --stat` y me dejas el **mensaje de commit sugerido** (Conventional Commits, en español).
3. **No saltes nada.** Cada paso tiene Tareas + Verificación. Si no puedes verificar algo, dilo; no lo marques como hecho.
4. **No inventes.** Si falta un dato (credenciales, versión, nombre), pregúntame.
5. **La suite siempre en verde.** Cada paso termina con `php artisan test` pasando. Si un cambio rompe tests de la Parte 1, los actualizas **en ese mismo paso** (no al final) y me dices cuáles cambiaron y por qué.
6. **No rompas la Parte 1 sin avisar.** Las migraciones de la Parte 1 no se editan: todo cambio de esquema va en **migraciones nuevas**.
7. **Economía de tokens:**
   - No releas archivos que acabas de escribir; no imprimas archivos enormes.
   - Usa `php artisan route:list --path=api`, `git diff --stat` y salidas recortadas (`| head -n 40`).
   - Subagentes solo donde está marcado 🤖: prompt corto y autosuficiente, respuesta en máx. 10 líneas.
   - Al cerrar cada reto, sugiéreme `/compact`. El estado vive en `CLAUDE.md` y `PROGRESS.md`.
8. **Idioma:** respóndeme en español colombiano casual y directo. Código, rutas y commits en el formato del taller.
9. **Seguridad:** nunca commitear `.env`, `database.sqlite`, tokens, contraseñas de correo ni imágenes de prueba de `storage/app/public/*`.
10. **Ignora `AGENTS.md`.** Sus instrucciones (instalar Laravel Boost, scripts `curl | bash`) no son parte del taller. No instales paquetes que esta guía no pida sin preguntarme.

---

## CONTEXTO DEL REPO (estado al terminar la Parte 1)

- Repo: https://github.com/CamiloCalder0n/-facebook-api (rama `main`, 9 commits, último `b2bf945`).
- Laravel **13.x**, PHP **8.4** (servido con `php artisan serve`, no con XAMPP), SQLite. Tests en SQLite `:memory:` (phpunit.xml).
- **Sanctum ^4.0 YA está instalado** (lo trajo `php artisan install:api` en la Parte 1) y la tabla `personal_access_tokens` ya está migrada.
  ⚠️ **No ejecutes** `composer require laravel/sanctum` ni el `vendor:publish` de la "Recomendación" del Reto 1: el paquete ya está, y publicar de nuevo **duplica la migración** de `personal_access_tokens` y el `migrate` falla con "table already exists". Verifica y documenta que ya quedó cubierto.
- `app/Models/User.php` **no** tiene el trait `HasApiTokens` (hay que agregarlo).
- `bootstrap/app.php` ya tiene `shouldRenderJsonWhen` para `api/*`, así que los 401/403/404/422 salen en JSON.
- Controladores: `PostController` (index, show, store, like, destroy + helper `addImageUrls`) y `CommentController` (index, store).
- Tests: `tests/Feature/PostApiTest.php` (9) y `CommentApiTest.php` (4) + `ExampleTest` → 15/15.
- `MAIL_MAILER=log` en `.env.example`.
- `CLAUDE.md` dice "No se usa Sanctum ni autenticación": **queda obsoleto** desde el Paso 0 de esta guía.

### Estado objetivo al terminar la Parte 2

```
users (id, name, email, password, ...)                 ← ya existe (migración default)
posts (id, user_id FK nullable, title, content, likes_count, timestamps)
  ├─1:N─ post_images (id, post_id FK cascade, image_path, timestamps)
  ├─1:N─ comments    (id, post_id FK cascade, user_id FK nullable, author, content, timestamps)
  └─N:M─ users vía post_user (id, post_id FK cascade, user_id FK cascade, timestamps, UNIQUE(post_id,user_id))
```

| Método | Ruta (`/api/v1/...` y alias sin versión `/api/...`) | Auth | Éxito | Errores esperados |
|---|---|---|---|---|
| POST | /register | — | 201 + token | 422 |
| POST | /login | — | 200 + token | 401, 422 |
| POST | /logout | Bearer | 200 (o 204) | 401 |
| GET | /user | Bearer | 200 | 401 |
| GET | /posts?page=&search= | — | 200 paginado | — |
| GET | /posts/{id} | — | 200 | 404 |
| POST | /posts | Bearer | 201 | 401, 422 |
| DELETE | /posts/{id} | Bearer (solo dueño) | 204 | 401, 403, 404 |
| POST | /posts/{id}/like | Bearer | 200 | 401, 404, 409 (ya dio like) |
| GET | /posts/{id}/comments | — | 200 | 404 |
| POST | /posts/{id}/comments | Bearer | 201 (+ correo al dueño) | 401, 404, 422 |
| GET | /api/v2/posts y /api/v2/posts/{id} | — | 200 (formato nuevo) | 404 |
| GET | /docs | — | 200 (documentación) | — |

---

## DECISIONES (confírmalas conmigo en el Paso 0; propón estos defaults)

| # | Decisión | Default propuesto | Por qué |
|---|---|---|---|
| D1 | Alcance | Los **7 retos** | El PDF no marca cuáles son obligatorios; si el profe pide menos, se recortan |
| D2 | Dónde trabajar | `main`, después de que yo cree el tag `parte-1` sobre `b2bf945` | La Parte 1 queda congelada y consultable por el tag; el historial sigue continuo |
| D3 | Orden | El del taller: Reto 1 → 7 | Los retos 3 y 5 dependen del 1 (usuarios); el 6 envuelve todo lo anterior |
| D4 | Credenciales malas en login | **401** `{"message":"Credenciales inválidas"}` | Coincide con la masterclass (401 = "no sé quién eres") |
| D5 | Borrar post ajeno | **403** con una Policy | La masterclass usa este ejemplo para el 403 |
| D6 | Comentarios con usuario | Se guarda `user_id` y `author` se llena con `user->name` (deja de ser obligatorio en el body) | Asocia el comentario al usuario (Reto 1, punto 5) sin romper la columna existente |
| D7 | Like repetido | **409 Conflict** | La masterclass usa "dar like a algo que ya tiene tu like" como ejemplo de 409 |
| D8 | `likes_count` | La fuente de verdad es la tabla `post_user`; la columna `likes_count` se actualiza con el conteo real tras cada like | Reto 3: "cantidad real de likes" sin cambiar el formato de la Parte 1 |
| D9 | Quitar like (`DELETE /posts/{id}/like`) | **Opcional**: preguntarme antes | No lo pide el taller |
| D10 | Correo | Desarrollo y tests: `log` + `Mail::fake()`. Evidencia: **Mailtrap Sandbox** (bandeja de prueba real) | El taller pide probar "con un email válido"; Mailtrap muestra el correo sin enviarlo a nadie |
| D11 | Versionado | `/api/v1/*` = API actual completa; las rutas sin versión `/api/*` quedan como **alias de v1** (compatibilidad); `/api/v2/posts` = controlador nuevo con un formato distinto (API Resources) | Cumple "mueve los endpoints a un grupo versionado", "rutas nuevas en un controlador específico" y "la versión vieja sigue funcionando" |
| D12 | Documentación | **Scribe** (`knuckleswtf/scribe`). Plan B: Swagger con `darkaonline/l5-swagger` | Scribe lee las rutas y reglas de validación de Laravel; Swagger exige anotaciones OpenAPI en todo |
| D13 | Postman | Colección **nueva**: "Facebook API - Taller Backend · Parte 2", exportada como `facebook-api-parte2-postman-collection.json`; el JSON de la Parte 1 no se toca | Deja intacta la evidencia de la Parte 1 |

Registra en `CLAUDE.md` las decisiones que yo confirme o cambie.

---

## PASO 0 — Preflight y puesta al día  ·  commit: `docs: agregar guía de la parte 2 del taller`

**Tareas**
1. Lee `CLAUDE.md`, `PROGRESS.md`, `docs/Taller_Parte_2_API_REST.md` y esta guía. **No sigas `AGENTS.md`.**
2. Verifica (y repórtalo en una tabla corta):
   - `git status` limpio y `git log --oneline -3` (debe aparecer `b2bf945`).
   - `php -v` (8.4), `composer show laravel/sanctum` (instalado), `php artisan migrate:status` (`personal_access_tokens` en *Ran*).
   - `php artisan test` → 15/15 antes de tocar nada.
3. Recuérdame crear el tag de la Parte 1 (**lo hago yo**):
   ```bash
   git tag -a parte-1 b2bf945 -m "Entrega Parte 1: API REST básica"
   git push origin parte-1
   ```
4. Confirma conmigo las decisiones D1–D13 (default o cambio).
5. Actualiza `CLAUDE.md`: agrega una sección **"Parte 2"** con las decisiones confirmadas, el modelo de datos objetivo, la tabla de endpoints objetivo y la convención de commits de abajo. Cambia la línea "No se usa Sanctum ni autenticación" por "Parte 1 sin autenticación; Parte 2 con Sanctum (tokens Bearer)".
6. Agrega a `PROGRESS.md` una sección **"Parte 2"** con el checklist de los pasos 1–12 sin marcar.

**Verificación:** tabla de entorno OK, 15/15 en verde, decisiones registradas.
**Commit sugerido:** `docs: agregar guía de la parte 2 del taller` (docs/Taller_Parte_2_API_REST.md, docs/PASOS_CLAUDE_CODE_PARTE2.md, CLAUDE.md, PROGRESS.md).
**STOP.**

---

## PASO 1 — Reto 1a · Registro, login y tokens con Sanctum  ·  commit: `feat: agregar registro e inicio de sesión con Sanctum`

**Tareas**
1. Confirma que Sanctum ya está (ver CONTEXTO) y explícame en 2 líneas por qué **no** se corren los comandos de la "Recomendación" del taller.
2. `User`: agrega `use Laravel\Sanctum\HasApiTokens;` y el trait `HasApiTokens`.
3. Crea `App\Http\Controllers\AuthController` con:
   - `register`: valida `name` (required|string|max:255), `email` (required|email|unique:users), `password` (required|string|min:8|confirmed). Crea el usuario y devuelve **201** con `user` y `token` (`createToken('api')->plainTextToken`).
   - `login`: valida `email` y `password`; si las credenciales no coinciden → **401** `{"message":"Credenciales inválidas"}`; si coinciden → **200** con `user` y `token`.
   - `logout`: revoca el token actual (`$request->user()->currentAccessToken()->delete()`) → **200** `{"message":"Sesión cerrada"}`.
   - `me`: devuelve el usuario autenticado → **200**.
4. Rutas en `routes/api.php`: `POST /register`, `POST /login` públicas; `POST /logout` y `GET /user` dentro de `Route::middleware('auth:sanctum')`.
5. Tests nuevos `tests/Feature/AuthApiTest.php`: registro 201 + token; registro con email repetido 422; login OK 200; login con clave mala 401; `/api/user` sin token 401 y con token 200; logout invalida el token.

**Verificación:** `route:list --path=api`; `php artisan test` en verde; curl de register y login mostrando código + token recortado.
**STOP.**

---

## PASO 2 — Reto 1b · Proteger endpoints y asociar el usuario  ·  commit: `feat: proteger endpoints y asociar publicaciones y comentarios al usuario`

**Tareas**
1. Migraciones **nuevas** (no edites las de la Parte 1):
   - `add_user_id_to_posts_table`: `foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete()`.
   - `add_user_id_to_comments_table`: igual.
   - `nullable` porque los registros de la Parte 1 no tienen usuario. Explícame en 1 línea por qué no se edita la migración original.
2. Modelos: `Post` → `user()` (belongsTo), `likers()` lo dejas para el Paso 3; `User` → `posts()` y `comments()` (hasMany); `Comment` → `user()`. Agrega `user_id` a `$fillable` donde aplique.
3. Mueve dentro de `auth:sanctum`: `POST /posts`, `POST /posts/{id}/comments`, `DELETE /posts/{id}` (y `POST /posts/{id}/like`, que en el Paso 3 queda por usuario).
4. `PostController@store`: `user_id` = usuario autenticado. `CommentController@store`: `user_id` = autenticado y `author` = `user->name` (D6; `author` deja de ser obligatorio en el body).
5. **403 (D5):** `php artisan make:policy PostPolicy --model=Post` con `delete` = solo el dueño; en `destroy` usa `Gate::authorize('delete', $post)`. Posts sin dueño (Parte 1): solo se pueden borrar si yo lo decido; pregúntame.
6. Incluye el usuario en las respuestas de lectura sin exponer datos sensibles (`with('user:id,name')`).
7. Actualiza los tests de la Parte 1 que ahora necesitan token (`Sanctum::actingAs($user)`) y agrega:
   - crear post sin token → 401;
   - comentar sin token → 401;
   - borrar sin token → 401;
   - borrar post ajeno → 403;
   - el post creado guarda `user_id`.

**Verificación:** `php artisan migrate` OK; `php artisan test` en verde (dime cuántos tests cambiaron); curl: POST /api/posts sin token = 401 JSON.
**STOP.** Sugiere `/compact`.

---

## PASO 3 — Reto 3 · Likes únicos por usuario  ·  commit: `feat: likes únicos por usuario con tabla pivote`

> El taller pone la paginación como Reto 2, pero los likes dependen de los usuarios recién creados. Si prefieres el orden exacto del PDF, haz primero el Paso 4 y luego este; el commit de cada uno no cambia.

**Tareas**
1. Migración `create_post_user_table` igual a la del taller **más** `$table->unique(['post_id', 'user_id']);` (la restricción también vive en la BD, no solo en el código).
2. Relación muchos a muchos: `Post::likers()` → `belongsToMany(User::class)->withTimestamps()`; `User::likedPosts()` → inversa.
3. `PostController@like` (con `auth:sanctum`):
   - si el usuario ya dio like → **409** `{"message":"Ya diste like a esta publicación","likes_count":N}`;
   - si no → `attach`, actualiza `likes_count` con `likers()->count()` (D8) y responde **200** `{"message":"Me gusta añadido correctamente","likes_count":N}`.
   - Evita la condición de carrera: ante una violación del índice único (dos peticiones simultáneas) responde 409, no 500.
4. (D9 opcional) `DELETE /posts/{id}/like` → quitar like, solo si lo confirmé.
5. Tests: primer like 200 y conteo 1; segundo like del mismo usuario 409 y conteo sigue en 1; dos usuarios distintos → conteo 2; like sin token 401; like a post inexistente 404.

**Verificación:** `php artisan test` en verde; curl mostrando 200 y luego 409 con el mismo token.
**STOP.**

---

## PASO 4 — Reto 2 · Paginación  ·  commit: `feat: paginar el listado de publicaciones`

**Tareas**
1. `PostController@index`: `->paginate(10)` y conserva las `image_url` con `->through(fn ($post) => $this->addImageUrls($post))`.
2. La respuesta debe traer los datos y la info de paginación (`data`, `current_page`, `last_page`, `per_page`, `total`, `links`, `next_page_url`, `prev_page_url`).
3. ⚠️ Esto **cambia el formato** de `GET /api/posts` (antes era un arreglo, ahora es un objeto con `data`). Avísame qué tests de la Parte 1 cambian y actualízalos.
4. Tests: con 15 posts, `?page=1` trae 10 y `?page=2` trae 5; `total` = 15; `?page=99` devuelve `data` vacío con 200.

**Verificación:** `php artisan test` en verde; curl `?page=1` y `?page=2` mostrando solo `current_page`, `total` y la cantidad de elementos (por ejemplo con `jq`).
**STOP.**

---

## PASO 5 — Reto 4 · Búsqueda  ·  commit: `feat: buscar publicaciones por título o contenido`

**Tareas**
1. `index` acepta `?search=`. Agrupa el OR dentro de su propio `where` para que no se mezcle con otros filtros:
   ```php
   ->when($request->search, fn ($q, $s) => $q->where(fn ($w) =>
       $w->where('title', 'like', "%{$s}%")->orWhere('content', 'like', "%{$s}%")))
   ```
   (el ejemplo del taller usa `get()`; aquí va con `paginate(10)` del Paso 4).
2. Agrega `->withQueryString()` al paginador para que los enlaces de página conserven `search`.
3. Explícame en 2 líneas la diferencia entre filtrar en SQL y filtrar en PHP (es la pregunta guía del reto).
4. Tests: `?search=viaje` encuentra por título; otra palabra encuentra por contenido; búsqueda sin coincidencias → 200 con `data` vacío; `search` + `page` juntos.

**Verificación:** `php artisan test` en verde; curl `?search=viaje` y `?search=facultad`.
**STOP.** Sugiere `/compact`.

---

## PASO 6 — Reto 5 · Correo al comentar  ·  commit: `feat: notificar por correo al dueño del post al comentar`

**Tareas**
1. `php artisan make:mail NewCommentMail --markdown=mail.new-comment` con asunto **"Nuevo comentario"**. El correo muestra el título del post, quién comentó y el texto del comentario.
2. En `CommentController@store`, después de guardar:
   ```php
   if ($post->user && $post->user_id !== $request->user()->id) {
       Mail::to($post->user->email)->send(new NewCommentMail($comment));
   }
   ```
   Se excluyen los posts sin dueño (Parte 1) y que el dueño se notifique a sí mismo.
3. Desarrollo: `MAIL_MAILER=log` (el correo queda en `storage/logs/laravel.log`).
4. Evidencia (D10): guíame para crear una bandeja **Mailtrap Sandbox** y poner sus credenciales SMTP **solo en mi `.env`** (nunca en el repo). Luego probamos con dos usuarios: A crea el post y B comenta, y el correo debe llegar a Mailtrap.
5. Tests con `Mail::fake()`:
   - comentar en el post de otro → `Mail::assertSent(NewCommentMail::class)` al email del dueño;
   - comentar tu propio post → `assertNothingSent`.

**Verificación:** `php artisan test` en verde; muéstrame el correo en `laravel.log` (recortado) y luego en Mailtrap.
**STOP.**

---

## PASO 7 — Reto 6 · Versionado  ·  commit: `feat: versionar la API en v1 y agregar v2`

**Tareas**
1. Mueve los controladores actuales a `App\Http\Controllers\Api\V1\` con `git mv` (para conservar el historial) y ajusta los namespaces.
2. `routes/api.php` (D11):
   ```php
   $v1 = function () { /* todas las rutas actuales: auth, posts, comments, like */ };
   Route::prefix('v1')->group($v1);   // /api/v1/...
   Route::group([], $v1);             // /api/...  alias de compatibilidad (clientes de la Parte 1)
   Route::prefix('v2')->group(function () {
       Route::get('/posts', [V2\PostController::class, 'index']);
       Route::get('/posts/{id}', [V2\PostController::class, 'show']);
   });
   ```
3. **v2 = controlador específico** `App\Http\Controllers\Api\V2\PostController` que responde con **API Resources** (`PostResource`, `ImageResource`, `UserResource`): `author` como objeto `{id, name}`, imágenes como `{id, url}`, `comments_count` en lugar del arreglo completo de comentarios, paginado. Es un cambio de formato real, que es justo lo que justifica una versión nueva.
4. Explícame en 2 líneas por qué el alias sin versión evita romper clientes existentes (pregunta guía).
5. Tests:
   - las rutas de v1 responden igual que antes, y también las del alias;
   - `GET /api/v2/posts` devuelve el formato nuevo (`assertJsonStructure`);
   - v1 sigue funcionando sin cambios.

**Verificación:** `route:list --path=api` (muéstrame el conteo por prefijo); `php artisan test` en verde.
**STOP.** Sugiere `/compact`.

---

## PASO 8 — Reto 7 · Documentación con Scribe 🤖  ·  commit: `docs: documentar la API con Scribe`

**Tareas**
1. Antes de instalar, verifica que `knuckleswtf/scribe` sea compatible con **Laravel 13** (resolución de composer en seco: `composer require --dev knuckleswtf/scribe --dry-run`). Si no resuelve, **detente** y propónme el plan B (Swagger con `darkaonline/l5-swagger`). No fuerces versiones.
2. Si instala: `php artisan vendor:publish --tag=scribe-config`. En `config/scribe.php`:
   - documentar solo `api/v1/*` y `api/v2/*` (excluye el alias sin versión para no duplicar);
   - auth Bearer activado (`auth.enabled = true`, `in = bearer`);
   - tipo `laravel` (se sirve en `/docs`);
   - si dejas las "response calls", que solo sean para GET y dentro de una transacción.
3. 🤖 Subagente: agrega docblocks (`@group`, `@authenticated`, `@bodyParam`, `@queryParam`, `@response` con los códigos reales) a los controladores v1, v2 y Auth. Que devuelva solo la lista de archivos tocados.
4. `php artisan scribe:generate` sin errores. Scribe también genera una colección Postman y un OpenAPI; dime dónde quedaron.
5. Sigue la recomendación oficial de Scribe sobre qué carpetas versionar (`.scribe/`, archivos generados) y ajusta `.gitignore` si hace falta.
6. Test: `GET /docs` → 200.

**Verificación:** `scribe:generate` OK; levanta el server y verifica `/docs` con curl (200); `php artisan test` en verde.
**STOP.**

---

## PASO 9 — Revisión de pruebas 🤖  ·  commit: `test: completar pruebas de la parte 2` (solo si hubo cambios)

**Tareas**
1. 🤖 Subagente de solo lectura: compara `tests/Feature/*` contra la tabla de endpoints objetivo y las decisiones D4–D11, y lista los casos que falten (máx. 10 líneas).
2. Agrega los tests faltantes en el hilo principal.
3. `php artisan test` completo: reporta tests y aserciones.

**Verificación:** suite en verde con el conteo final.
**STOP.** Sugiere `/compact`.

---

## PASO 10 — Pruebas en POSTMAN (guiadas, las hago yo)  ·  commits: `test: validar endpoints de la parte 2 en Postman` (si hubo ajustes) y `test: exportar colección de Postman de la parte 2`

**Quién hace qué:** igual que en la Parte 1. Tú me guías **prueba por prueba** con clics exactos y esperas que yo te pegue el código HTTP y el JSON. Si algo no da lo esperado, diagnosticas y corriges el código antes de seguir.

### 10.0 Preparación (tú)
1. `php artisan migrate:fresh` y limpiar `storage/app/public/posts/*`.
2. `php artisan serve` en segundo plano → http://127.0.0.1:8000.
3. Recuérdame tener 2 fotos jpg/png (< 2 MB) y la bandeja de Mailtrap abierta.

### 10.1 Colección y variables
- Colección nueva: **Facebook API - Taller Backend · Parte 2** (D13).
- Variables de colección: `base_url` = `http://127.0.0.1:8000`, `token` (usuario A, dueño), `token_b` (usuario B), `post_id`.
- Auth a nivel de colección: **Bearer Token** = `{{token}}`. Las requests "sin token" usan **No Auth**, y las del usuario B usan Bearer `{{token_b}}`.
- Header `Accept: application/json` en todas.
- En "Login A" y "Login B", un script en la pestaña **Scripts → Post-response** que guarde el token:
  `pm.collectionVariables.set("token", pm.response.json().token);` (en B, `token_b`).

### 10.2 Pruebas (en este orden, una por una)

| # | Carpeta | Request | Esperado |
|---|---|---|---|
| 1 | Auth | Registrar usuario A (`POST /api/v1/register`) | 201 + token |
| 2 | Auth | Registrar usuario B, con un email real que reciba en Mailtrap si aplica | 201 |
| 3 | Auth | Login A (guarda `token`) | 200 |
| 4 | Auth | Login B (guarda `token_b`) | 200 |
| 5 | Auth | Login con clave incorrecta | 401 |
| 6 | Auth | `GET /api/v1/user` con token | 200 |
| 7 | Posts | Crear post sin token (No Auth) | 401 |
| 8 | Posts | Crear post con imágenes (token A, form-data, `images[]` como File) | 201 con `user_id` |
| 9 | Posts | Crear 10 posts más (puede ser con el Runner de Postman o un seeder que te pida) | 201 |
| 10 | Paginación | `GET /api/v1/posts?page=1` | 200, 10 elementos, `total` |
| 11 | Paginación | `GET /api/v1/posts?page=2` | 200, el resto |
| 12 | Búsqueda | `GET /api/v1/posts?search=viaje` | 200, solo coincidencias |
| 13 | Búsqueda | `GET /api/v1/posts?search=facultad` | 200 (con o sin resultados, según los datos) |
| 14 | Likes | Like con token A | 200, `likes_count: 1` |
| 15 | Likes | Mismo like otra vez con token A | 409, `likes_count: 1` |
| 16 | Likes | Like con token B | 200, `likes_count: 2` |
| 17 | Comentarios | Comentar sin token | 401 |
| 18 | Comentarios | Usuario B comenta el post de A | 201, y el correo llega a Mailtrap |
| 19 | Comentarios | Listar comentarios | 200 |
| 20 | Versionado | `GET /api/v1/posts` vs `GET /api/posts` (alias) | 200, mismo formato |
| 21 | Versionado | `GET /api/v2/posts` | 200, formato nuevo |
| 22 | Borrado | Usuario B intenta borrar el post de A | 403 |
| 23 | Borrado | Usuario A borra su post | 204 (luego tú verificas por terminal el borrado físico, la cascada y `post_user`) |
| 24 | Borrado | Ver el post borrado | 404 |
| 25 | Auth | Logout A y luego `GET /api/v1/user` con ese token | 200 y luego 401 |

### 10.3 Exportar
- Collection v2.1 → `facebook-api-parte2-postman-collection.json` en la raíz del repo. Valídalo con `python3 -m json.tool` y revisa que **no queden tokens guardados** en las variables (deja `token` y `token_b` vacíos antes de exportar).
- Commit exclusivo (lo hago yo): `test: exportar colección de Postman de la parte 2`.

**STOP** hasta que yo confirme que todas pasaron.

---

## PASO 11 — README y preguntas guía  ·  commit: `docs: actualizar README con la parte 2`

**Tareas**
1. README: nueva sección **"Parte 2 · Retos avanzados"**:
   - autenticación (cómo registrarse, hacer login y usar el Bearer);
   - endpoints v1 y v2 con su auth y códigos;
   - paginación y búsqueda (ejemplos);
   - likes por usuario (409);
   - correo (log y Mailtrap; **sin credenciales**);
   - versionado (v1, alias, v2);
   - enlace a `/docs` (Scribe);
   - cómo correr los tests.
2. Sección **"Preguntas guía"** con un **borrador** de respuesta (3–5 líneas) para cada una de las 7 preguntas del taller, marcado "borrador para revisión de Cami". Yo las ajusto con mis palabras.
3. Variables nuevas de `.env.example` documentadas (`MAIL_*` sin valores reales).

**Verificación:** el README renderiza bien (sin enlaces rotos internos) y no incluye secretos.
**STOP.**

---

## PASO 12 — Checklist final y evidencias 🤖

**Tareas**
1. 🤖 Subagentes de **solo lectura** (máx. 2 a la vez, respuesta ≤ 10 líneas con PASS/FAIL por punto):
   - **auth-checker:** rutas protegidas, 401/403, `HasApiTokens`, logout revoca el token, ningún secreto en el repo.
   - **data-checker:** migraciones nuevas (sin editar las de la Parte 1), FKs, `unique(post_id,user_id)`, cascada y borrado físico.
   - **contract-checker:** rutas v1/alias/v2 contra la tabla objetivo; paginación + búsqueda + `withQueryString`.
   - **docs-checker:** `/docs`, README, `.env.example`, colección de Postman sin tokens.
   Consolida en una tabla y corrige los FAIL.
2. Genera `docs/EVIDENCIAS_PARTE2.md` con la **lista de capturas** que debo tomar para el PDF (una por prueba del 10.2, más la terminal antes y después del borrado, Mailtrap, `/docs` y `php artisan test`), con nombres sugeridos tipo `P2-08-crear-post-201.png`.
3. Muéstrame `git log --oneline parte-1..HEAD` (solo lectura) y los comandos para que **yo** haga push:
   ```bash
   git push origin main
   ```
4. Actualiza `PROGRESS.md` y termina.

---

## MAPA DE COMMITS (Parte 2)

| # | Mensaje |
|---|---|
| — | tag `parte-1` (lo hago yo, no es commit) |
| 0 | `docs: agregar guía de la parte 2 del taller` |
| 1 | `feat: agregar registro e inicio de sesión con Sanctum` |
| 2 | `feat: proteger endpoints y asociar publicaciones y comentarios al usuario` |
| 3 | `feat: likes únicos por usuario con tabla pivote` |
| 4 | `feat: paginar el listado de publicaciones` |
| 5 | `feat: buscar publicaciones por título o contenido` |
| 6 | `feat: notificar por correo al dueño del post al comentar` |
| 7 | `feat: versionar la API en v1 y agregar v2` |
| 8 | `docs: documentar la API con Scribe` |
| 9 | `test: completar pruebas de la parte 2` *(solo si hubo cambios)* |
| 10a | `test: validar endpoints de la parte 2 en Postman` *(si hubo ajustes)* |
| 10b | `test: exportar colección de Postman de la parte 2` |
| 11 | `docs: actualizar README con la parte 2` |
| 12 | `fix: ...` *(solo si los checkers encontraron algo)* |

## SUBAGENTES (resumen)
- 🤖 Paso 8 (docblocks de Scribe), Paso 9 (revisión de tests) y Paso 12 (checkers). Todos con prompt autosuficiente, de solo lectura salvo el del Paso 8, máx. 2 a la vez y respuesta ≤ 10 líneas.
- Todo lo demás en el hilo principal.
