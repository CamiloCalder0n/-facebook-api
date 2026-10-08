# PASOS PARA CLAUDE CODE — API REST tipo Facebook (Laravel)

> Pega este archivo en la raíz de la carpeta de trabajo y dile a Claude Code:
> **"Lee PASOS_CLAUDE_CODE.md y empieza por el Paso 0."**
> Fuentes: `Building_Laravel_REST_APIs.md` (teoría/indicaciones) y `Taller_API_REST.md` (taller). Si algo de aquí contradice al taller, avísame y no decidas solo.

---

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

---

## CONTEXTO RESUMIDO

- Materia: Arquitectura y Desarrollo Backend (UNAB), docente Fabian Enrique Suarez Carvajal.
- Objetivo: API REST simplificada tipo Facebook: posts con múltiples imágenes, comentarios y likes. Se valida con Postman.
- Principios REST a respetar (del Building): la URL es el sustantivo (recurso), el método HTTP es el verbo; **nada de verbos en URLs**; API stateless (sin CSRF); códigos HTTP correctos (nunca `200` con `{"error": ...}`); `422` para validación; `201` al crear; `204` al borrar sin cuerpo; `404` si no existe.
- Evaluación: pesan igual el **código**, el **historial de commits** (incremental y descriptivo) y la **colección de Postman exportada**.

### Modelo de datos
```
posts (id, title, content, likes_count default 0, timestamps)
  ├─1:N─ post_images (id, post_id FK cascade, image_path, timestamps)
  └─1:N─ comments    (id, post_id FK cascade, author, content, timestamps)
```

### Endpoints (todos bajo /api)
| Método | Ruta | Content-Type | Código éxito |
|---|---|---|---|
| POST | /api/posts | multipart/form-data (title, content, images[]) | 201 |
| GET | /api/posts | json | 200 |
| GET | /api/posts/{id} | json | 200 |
| POST | /api/posts/{id}/comments | json (author, content) | 201 |
| GET | /api/posts/{id}/comments | json | 200 |
| POST | /api/posts/{id}/like | json | 200 |
| DELETE | /api/posts/{id} | json | 204 |

---

## PASO 0 — Preflight, CLAUDE.md y PROGRESS.md (sin commit de código aún)

**Tareas**
1. Verifica el entorno y repórtamelo en una tabla corta: `php -v` (≥ 8.2 si Laravel 12; mínimo 8.1 según taller), `composer -V` (2.x), `git --version`, `node -v` (opcional), extensión `sqlite3`/`pdo_sqlite` habilitada (`php -m | grep -i sqlite`), extensión `fileinfo` y `gd` (para validar imágenes).
2. Si falta algo, **detente y dime cómo instalarlo** para mi sistema operativo (pregúntame cuál uso si no lo sabes).
3. Confirma conmigo estas decisiones (propón los defaults, yo respondo ok o cambio):
   - Nombre del proyecto: `facebook-api`
   - BD: **SQLite** (recomendado por el taller)
   - Laravel: última estable instalada por `composer create-project` (el Building habla de 12+, el taller de 10/11; el flujo es el mismo, solo cambia que en 11+ hay que correr `install:api`)
   - Tests automáticos: **PHPUnit** (el default de Laravel) salvo que prefiera Pest
   - Carpeta del repo: la actual vs. subcarpeta `facebook-api/`
4. Una vez confirmado, crea (en la carpeta del proyecto, cuando exista, o en la raíz si ya estás dentro) estos dos archivos:
   - **`CLAUDE.md`** con: las REGLAS DE TRABAJO de arriba (copiadas tal cual), el modelo de datos, la tabla de endpoints y la convención de commits.
   - **`PROGRESS.md`** con checklist de los pasos 1–11 (todos sin marcar). Lo actualizas al terminar cada paso.

**Verificación:** los dos archivos existen; entorno OK.
**Commit:** ninguno todavía (se incluyen en el commit del Paso 1).
**STOP.**

---

## PASO 1 — Inicialización del proyecto  ·  commit: `feat: crear proyecto Laravel`

**Tareas**
1. `composer create-project laravel/laravel facebook-api` (o en la carpeta actual si así lo acordamos).
2. `git init` + rama `main` (si no existe repo) y confirma que `.gitignore` incluye `.env`, `vendor/`, `database/*.sqlite`, `/public/storage`, `/storage/*.key`.
3. Configura **SQLite**: en `.env` → `DB_CONNECTION=sqlite` (comenta/elimina `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` si molestan); crea `database/database.sqlite` vacío (`touch`). Aclárame si tu versión ya lo trae listo.
4. `php artisan key:generate` (si no está) y `php artisan storage:link`.
5. **Laravel 11+ no trae `routes/api.php`**: ejecuta `php artisan install:api` (acepta correr las migraciones que proponga). Explícame en 2 líneas qué agregó (Sanctum, `routes/api.php`, prefijo `/api`, tabla `personal_access_tokens`). No uses Sanctum en este taller.
6. Ajusta `.env` `APP_NAME="Facebook API"` y `APP_URL=http://127.0.0.1:8000`.

**Verificación:** `php artisan about | head -n 30`; `ls -la public/storage` (symlink); existe `routes/api.php`; `git status` limpio de secretos.
**Commit sugerido:** `feat: crear proyecto Laravel` (incluye CLAUDE.md y PROGRESS.md).
**STOP.**

---

## PASO 2 — Modelos y migraciones  ·  commit: `feat: crear modelos y migraciones para posts, comentarios e imágenes`

**Tareas**
1. Genera (en este orden, para que las migraciones queden ordenadas por timestamp y la FK funcione):
   ```bash
   php artisan make:model Post -mc
   php artisan make:model PostImage -m
   php artisan make:model Comment -mc
   ```
2. Edita las migraciones:
   - `posts`: `id`, `string title`, `text content`, `unsignedInteger likes_count default(0)`, `timestamps`.
   - `post_images`: `id`, `foreignId post_id constrained()->onDelete('cascade')`, `string image_path`, `timestamps`.
   - `comments`: `id`, `foreignId post_id constrained()->onDelete('cascade')`, `string author`, `text content`, `timestamps`.
3. `php artisan migrate` (si `install:api` ya migró, solo corre las nuevas).
4. **SQLite y cascada:** confirma que las claves foráneas están activas (Laravel 11+ las activa por defecto con `foreign_key_constraints` en `config/database.php`). Si no, actívalo; el borrado en cascada depende de esto.

**Verificación:** `php artisan migrate:status`; `php artisan db:table posts` (o `sqlite3` si está) mostrando columnas. Confirma que existen las 3 tablas.
**STOP.**

---

## PASO 3 — Relaciones Eloquent y `$fillable`  ·  commit: `feat: configurar relaciones entre Post, Comment y PostImage`

**Tareas**
- `Post`: `$fillable = ['title','content','likes_count']`; `images()` → `hasMany(PostImage::class)`; `comments()` → `hasMany(Comment::class)`.
- `PostImage`: `$fillable = ['post_id','image_path']`; `post()` → `belongsTo(Post::class)`.
- `Comment`: `$fillable = ['post_id','author','content']`; `post()` → `belongsTo(Post::class)`.
- Agrega `use` correctos y tipos de retorno opcionales (`HasMany`, `BelongsTo`) si la versión lo permite.

**Verificación:** `php artisan tinker --execute="echo App\Models\Post::create(['title'=>'t','content'=>'c'])->images()->count();"` debe imprimir `0`; luego borra ese registro de prueba (`Post::query()->delete()`). Sin errores.
**STOP.**

---

## PASO 4 — Controladores  ·  commit: `feat: implementar CRUD y lógica de likes`

**Tareas** (usa estos controladores; respeta los códigos HTTP)

`app/Http/Controllers/PostController.php`
- `index()`: `Post::with(['images','comments'])->latest()->get()`; a cada imagen agregarle `image_url = asset('storage/'.$img->image_path)`; `200`.
- `show($id)`: `findOrFail`, mismo `image_url`; `200`.
- `store(Request $request)`: validar
  ```php
  'title'    => 'required|string|max:255',
  'content'  => 'required|string',
  'images'   => 'nullable|array',
  'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
  ```
  crear post; por cada archivo `->store('posts','public')` y crear `PostImage`; responder `$post->load('images')` con **201** (incluye `image_url` también aquí para ser consistente).
- `like($id)`: `findOrFail`, `increment('likes_count')`, responder `{message, likes_count}` con **200** (usa `refresh()` o `fresh()` para devolver el valor real).
- `destroy($id)`: `findOrFail`; borrar archivos físicos con `Storage::disk('public')->delete($image->image_path)`; `$post->delete()` (la BD borra imágenes y comentarios por cascada); responder `response()->json(null, 204)` (o `response()->noContent()`).

`app/Http/Controllers/CommentController.php`
- `index($postId)`: `findOrFail` y devolver `comments()->latest()->get()` con **200**.
- `store(Request $request, $postId)`: validar `author` (`required|string|max:100`) y `content` (`required|string`); crear vía `$post->comments()->create(...)`; **201**.

Para evitar repetir la lógica de `image_url`, puedes crear un helper privado en `PostController` (no sobre-ingenierices: sin Resources/Services salvo que yo lo pida).

**Verificación:** `php -l` en ambos archivos y `php artisan route:list` aún sin las rutas (se agregan en el Paso 5). Sin errores de sintaxis.
**STOP.**

---

## PASO 5 — Rutas API  ·  commit: `feat: registrar endpoints de la API REST`

**Tareas** — `routes/api.php`:
```php
Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{id}', [PostController::class, 'show']);
Route::post('/posts', [PostController::class, 'store']);
Route::delete('/posts/{id}', [PostController::class, 'destroy']);
Route::post('/posts/{id}/like', [PostController::class, 'like']);

Route::get('/posts/{id}/comments', [CommentController::class, 'index']);
Route::post('/posts/{id}/comments', [CommentController::class, 'store']);
```
Con sus `use` de controladores. Recuérdame (1 línea) por qué aquí no hay CSRF (stateless) y por qué el prefijo `/api` es automático.

**Verificación:** `php artisan route:list --path=api` debe mostrar exactamente 7 rutas de posts/comments (más las propias de Sanctum si las hay).
**STOP.**

---

## PASO 6 — Levantar servidor y smoke test con curl  ·  (sin commit propio, o junto al Paso 7)

**Tareas**
1. Levanta `php artisan serve` **en segundo plano** y confirma que responde en `http://127.0.0.1:8000`.
2. Smoke test con `curl` (siempre con `-H "Accept: application/json"`), en este orden y mostrando solo código HTTP + cuerpo recortado:
   1. POST /api/posts (multipart con 2 imágenes pequeñas de prueba generadas temporalmente, p. ej. con PHP/GD o `printf` de un PNG mínimo) → **201**
   2. GET /api/posts → **200**, con `image_url`
   3. GET /api/posts/1 → **200**
   4. POST /api/posts/1/comments con JSON → **201**
   5. GET /api/posts/1/comments → **200**
   6. POST /api/posts/1/like → **200**, `likes_count: 1`
   7. DELETE /api/posts/1 → **204**, y verifica que las imágenes físicas desaparecieron de `storage/app/public/posts/` y que no quedan filas huérfanas en `post_images`/`comments`.
3. Limpia archivos y datos de prueba al terminar y detén el servidor.

**Verificación:** tabla con los 7 resultados (esperado vs. real). Si algo falla, corrígelo y repórtalo.
**STOP.**

---

## PASO 7 — (OPCIONAL, solo si Cami lo pide) Tests automáticos 🤖 (candidato a subagente)  ·  commit: `test: agregar pruebas feature de la API`

> Si lo delegas a un subagente, el prompt debe incluir: modelos, rutas, validaciones y códigos esperados (resumen de arriba); y pedirle que devuelva solo "qué archivos creó + resultado de `php artisan test`".

**Tareas** — `tests/Feature/PostApiTest.php` (y `CommentApiTest.php` si prefieres separar), usando `RefreshDatabase` y `Storage::fake('public')` + `UploadedFile::fake()->image('a.jpg')`:
- crear post con 2 imágenes → 201 y archivos guardados
- crear post sin título → 422
- crear post con PDF como imagen → 422
- listar → 200 y estructura con `images` y `comments`
- ver post inexistente → 404
- comentar OK → 201; comentar sin `author` → 422
- like → 200 y contador +1
- borrar → 204, archivos eliminados y comentarios/imágenes borrados en BD
- método incorrecto (p. ej. `GET /api/posts/1/like`) → 405

**Verificación:** `php artisan test` en verde (reporta número de tests/asserts).
**STOP.**

---

## PASO 8 — Pruebas en POSTMAN (guiadas, las hace Cami)  ·  commits: `test: validar endpoints en Postman` (si hubo ajustes) y `test: exportar colección de Postman`

**Quién hace qué:** Claude Code NO genera ni importa la colección (el taller dice crearla **manualmente** para aprender). Claude Code es el **guía**: me dice exactamente qué clickear y qué escribir, **una prueba a la vez**, y espera que yo le pegue el **código HTTP + JSON** que me dio Postman. Si el resultado no es el esperado, diagnostica y corrige el código antes de seguir. Nunca avanza a la siguiente prueba sin mi confirmación.

### 8.0 Preparación (Claude Code lo hace y me avisa)
1. `php artisan migrate:fresh` (BD limpia para que el primer post sea `id = 1`) y borrar `storage/app/public/posts/*` si hay restos.
2. Dejar `php artisan serve` corriendo en segundo plano → `http://127.0.0.1:8000`.
3. Recordarme que tenga a mano **2 fotos** (jpg/png < 2 MB) y **1 archivo PDF** (para la prueba de error 422).
4. Si el `id` del post creado no es 1, decirme que use el `id` real en las URLs siguientes.

### 8.1 Crear la colección y la variable
1. Abrir Postman → panel izquierdo **Collections** → **New Collection** → nombre exacto: **Facebook API - Taller Backend** → **Create**.
2. Crear la variable `base_url` con valor `http://127.0.0.1:8000` (pestaña **Variables** de la colección, o un Environment "Local" que luego **debo seleccionar** arriba a la derecha; si no, `{{base_url}}` no se resuelve).
3. Regla: **todas** las URLs usan `{{base_url}}/api/...`, nunca `localhost` escrito a mano.
4. Para cada request: dentro de la colección → **Add request** → nombre, método y URL → pestaña **Headers** → agregar siempre `Accept: application/json` (sin esto, un error de validación devolvería redirección/HTML en vez del JSON 422).
5. Guardar cada request (**Save**) dentro de la colección.

### 8.2 Las pruebas (en este orden; una por una, no ejecutar todo junto)

**Prueba 1 · Crear publicación con imágenes** → esperado **201 Created**
- Nombre: `Crear publicación con imágenes` · **POST** · `{{base_url}}/api/posts`
- Headers: `Accept: application/json` (NO poner `Content-Type` a mano: Postman lo pone solo, con boundary, en form-data)
- Body → **form-data**:
  - `title` (Text) = `Mi primera publicación`
  - `content` (Text) = `Hola a todos, comparto fotos de mi viaje.`
  - `images[]` (**File**) = foto 1
  - `images[]` (**File**) = foto 2
- ⚠️ La key lleva corchetes `[]`. En la celda de la key, pasar el cursor y cambiar el tipo de **Text a File** en el menú desplegable pequeño (error #1 del taller). **No** se puede mandar un archivo escribiendo su ruta en un JSON raw.
- Revisar: 201; JSON con `id`, `title`, `content`, `images` (con `image_path` e `image_url`). **Abrir una `image_url` en el navegador** para confirmar que la imagen se ve (prueba que `storage:link` funciona).

**Prueba 2 · Listar publicaciones** → esperado **200 OK**
- `Listar publicaciones` · **GET** · `{{base_url}}/api/posts` · Header `Accept: application/json` · sin body.
- Revisar: array de posts con `images` (con `image_url` completo) y `comments`.

**Prueba 2b · Ver una publicación** → esperado **200 OK**
- `Ver publicación` · **GET** · `{{base_url}}/api/posts/1` · mismo header.

**Prueba 3 · Agregar comentario** → esperado **201 Created**
- `Agregar comentario` · **POST** · `{{base_url}}/api/posts/1/comments`
- Headers: `Accept: application/json`, `Content-Type: application/json`
- Body → **raw** → selector **JSON**:
  ```json
  {"author": "Carlos Gómez", "content": "¡Qué buenas fotos!"}
  ```
- Explicación: POST porque se crea un recurso nuevo asociado a un post existente; el servidor valida `author` y `content`.

**Prueba 3b · Listar comentarios** → esperado **200 OK**
- `Listar comentarios` · **GET** · `{{base_url}}/api/posts/1/comments`.

**Prueba 4 · Dar Me gusta** → esperado **200 OK**
- `Dar like a publicación` · **POST** · `{{base_url}}/api/posts/1/like` · Body: ninguno.
- Revisar: `likes_count` incrementado (pulsar **Send** dos veces → sube a 2). Explicación: es POST porque crea una interacción; aunque no mande body, el servidor actualiza el contador.

**Casos de error (carpeta opcional "Casos de error" dentro de la colección)**
- **422 título vacío:** POST `/api/posts`, form-data con `title` vacío y `content` lleno → 422 con JSON de errores (por eso el header `Accept`).
- **422 archivo no imagen:** POST `/api/posts` con `images[]` (File) = el **PDF** → 422.
- **422 comentario sin author:** POST `/api/posts/1/comments` con `{"content":"hola"}` → 422.
- **404:** GET `{{base_url}}/api/posts/9999` → 404.
- **405:** GET `{{base_url}}/api/posts/1/like` (la ruta existe pero solo acepta POST) → 405 Method Not Allowed.
- **Sin header Accept:** repetir el 422 de título vacío quitando `Accept` → observar que NO llega JSON 422 (redirección/HTML). Útil para la sustentación.

**Prueba 5 · Eliminar publicación** → esperado **204 No Content** (de las últimas)
- `Eliminar publicación` · **DELETE** · `{{base_url}}/api/posts/1` · sin body.
- Revisar: 204 y **cuerpo vacío**. Después Claude Code verifica por terminal que se borraron las imágenes de `storage/app/public/posts/` y que no quedan filas en `post_images` ni `comments`. Luego repetir `GET /api/posts/1` → debe dar **404**.

### 8.3 Organización final de la colección (como pide el taller)
**Facebook API - Taller Backend**
- Crear publicación con imágenes
- Listar publicaciones
- Agregar comentario
- Dar like a publicación
- Eliminar publicación

(+ extras: Ver publicación, Listar comentarios, carpeta Casos de error)

### 8.4 Exportar y versionar (lo hago yo, Claude Code me guía)
1. En Postman, abrir la colección → **Export** → formato **Collection v2.1** → guardar como `facebook-api-postman-collection.json`.
2. Copiarlo a la raíz del repo.
3. Claude Code verifica: `python3 -m json.tool facebook-api-postman-collection.json > /dev/null` (JSON válido), que las URLs usan `{{base_url}}`, y me avisa si quedaron rutas locales de mis archivos en los `form-data` (campo `src`).
4. **Commits (los hago yo):** `test: validar endpoints en Postman` (solo si hubo ajustes de código derivados) y uno **exclusivo** para la colección:
   ```bash
   git add facebook-api-postman-collection.json
   git commit -m "test: exportar colección de Postman"
   ```
5. Recordarme guardar **capturas** como evidencia del checklist (`php artisan serve`, cada respuesta 201/200/204, el GET con `image_url`).

**STOP** hasta que yo confirme que todas las pruebas pasaron.

---

## PASO 9 — Casos de error y ajustes  ·  commit: `fix: corregir errores de validación y almacenamiento`

> Solo hay commit si hay cambios. Si todo ya pasa, dímelo y salta al Paso 10.

**Tareas**
1. Con los resultados de Postman (y tests si se hicieron), corrige lo que falle (validaciones, rutas, URLs de imágenes, cascada, límites de tamaño).
2. Documenta en una nota (para el README) el comportamiento si **no** se manda `Accept: application/json`: en errores de validación Laravel intenta **redirigir** (302) en lugar de devolver JSON 422. **No lo cambies por defecto**; ofréceme la alternativa opcional (`shouldRenderJsonWhen` en `bootstrap/app.php` para `api/*`) y yo decido.
3. Revisa que ningún endpoint use verbos en la URL y que ningún error devuelva `200`.

**Verificación:** casos de error de Postman en verde (y `php artisan test` si se hicieron tests).
**STOP.**

---

## PASO 10 — README.md  ·  commit: `docs: agregar README del proyecto`

El checklist del taller exige **repositorio en GitHub con README.md**.

**Tareas** — README en español con:
1. Título, descripción, autor (**pregúntame mi nombre completo y el grupo si aplica**), materia, docente.
2. Requisitos y **instalación paso a paso** (clonar, `composer install`, `.env` desde `.env.example`, `key:generate`, SQLite, `migrate`, `storage:link`, `serve`).
3. Tabla de endpoints (método, ruta, content-type, body, código éxito, errores posibles: 404/405/422).
4. Ejemplos de request/response (JSON) de cada endpoint.
5. Modelo de datos (diagrama ER en ASCII o Mermaid).
6. Cómo probar: Postman (`{{base_url}}`, importar la colección, tip de `images[]` como File) y `php artisan test`.
7. Decisiones de diseño breves: por qué "like" es POST (crea una interacción), por qué 201/204/422, por qué no hay CSRF, cascada por FK + borrado físico de archivos, nota del header `Accept`.
8. Sección "Evidencia de proceso": convención de commits usada.
9. (Opcional, si lo pido) breve sección de respuestas a las preguntas de discusión del Building.

**Verificación:** los comandos del README funcionan en frío (idealmente simula en una copia temporal: `composer install` no hace falta repetirlo, pero sí `migrate:fresh` y `serve`).
**STOP.**

---

## PASO 11 — Checklist final "Dejarlo funcionando"

Recorre el checklist del taller y marca con evidencia real (comando + resultado):

| ✔ | Verificación | Evidencia que me debes mostrar |
|---|---|---|
| ☐ | Laravel corriendo en http://127.0.0.1:8000 | salida de `php artisan serve` / curl |
| ☐ | Enlace simbólico de storage | `ls -la public/storage` |
| ☐ | Tablas posts, post_images, comments migradas | `php artisan migrate:status` |
| ☐ | POST /api/posts → 201 con imágenes guardadas | respuesta |
| ☐ | GET /api/posts con `image_url` completo | respuesta |
| ☐ | POST comments → 201 | respuesta |
| ☐ | POST like → 200 y `likes_count` incrementado | respuesta |
| ☐ | DELETE → 204 y borra imágenes físicas | `ls storage/app/public/posts` |
| ☐ | Colección Postman completa y exportada (.json en el repo) | archivo + `git ls-files` |
| ☐ | README.md presente | `git ls-files README.md` |
| ☐ | Historial de commits progresivo y descriptivo | `git log --oneline` (solo lectura) |
| ☐ | `.env` y `database.sqlite` NO versionados | `git ls-files \| grep -E "\.env$\|sqlite"` vacío |

Cierra con: lista de commits sugeridos que me faltaron (si alguno), y los comandos para que **yo** suba a GitHub:
```bash
git remote add origin <URL_DEL_REPO>
git push -u origin main
```
(no los ejecutes tú). Actualiza `PROGRESS.md` y termina.

---

## MAPA DE COMMITS (orden esperado)

| # | Mensaje |
|---|---|
| 1 | `feat: crear proyecto Laravel` |
| 2 | `feat: crear modelos y migraciones para posts, comentarios e imágenes` |
| 3 | `feat: configurar relaciones entre Post, Comment y PostImage` |
| 4 | `feat: implementar CRUD y lógica de likes` |
| 5 | `feat: registrar endpoints de la API REST` |
| 6 | `feat: implementar creación de publicaciones con imágenes` *(si se separa el store del resto; opcional)* |
| 7 | `test: agregar pruebas feature de la API` *(opcional)* |
| 8a | `test: validar endpoints en Postman` *(si hubo ajustes)* |
| 8b | `test: exportar colección de Postman` |
| 9 | `fix: corregir errores de validación y almacenamiento` *(solo si hubo cambios)* |
| 10 | `docs: agregar README del proyecto` |

## SUBAGENTES (resumen)
- 🤖 Solo el Paso 7 (tests opcionales) es candidato a subagente. El Paso 8 (Postman) es guiado en el hilo principal.
- Todo lo demás se hace en el hilo principal. Máx. 1 subagente a la vez; resumen de vuelta ≤ 10 líneas.
