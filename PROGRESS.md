# PROGRESS — API REST tipo Facebook

Detalle de cada paso en `docs/PASOS_CLAUDE_CODE.md`.

- [x] Paso 0 — Preflight, CLAUDE.md y PROGRESS.md
- [x] Paso 1 — Inicialización del proyecto (SQLite, storage:link, install:api, .env) · `feat: crear proyecto Laravel`
- [x] Paso 2 — Modelos y migraciones · `feat: crear modelos y migraciones para posts, comentarios e imágenes`
- [x] Paso 3 — Relaciones Eloquent y `$fillable` · `feat: configurar relaciones entre Post, Comment y PostImage`
- [x] Paso 4 — Controladores · `feat: implementar CRUD y lógica de likes`
- [x] Paso 5 — Rutas API · `feat: registrar endpoints de la API REST`
- [x] Paso 6 — Servidor y smoke test con curl
- [x] Paso 7 — (Opcional) Tests automáticos · `test: agregar pruebas feature de la API`
- [x] Paso 8 — Pruebas en Postman (guiadas) · `test: validar endpoints en Postman` / `test: exportar colección de Postman`
- [x] Paso 9 — Casos de error y ajustes · `fix: corregir errores de validación y almacenamiento`
- [x] Paso 10 — README.md · `docs: agregar README del proyecto`
- [x] Paso 11 — Checklist final "Dejarlo funcionando"

## Notas

- Paso 0: entorno OK (PHP 8.4.1, Composer 2.8.12, Laravel 13.35.0, pdo_sqlite/fileinfo/gd activas). Proyecto y `git init -b main` ya hechos por Cami antes del Paso 1.
- Paso 1: SQLite y APP_KEY ya venían del create-project; se ajustó APP_NAME/APP_URL, storage:link, install:api (Sanctum + routes/api.php + personal_access_tokens migrada). Se sirve con `php artisan serve` (PHP 8.4), no con Apache de XAMPP (PHP 8.2.4 < 8.4.1 requerido).
- Paso 2: 3 migraciones creadas y migradas; FKs con ON DELETE CASCADE; PRAGMA foreign_keys=1 (DB_FOREIGN_KEYS default true en config/database.php).
- Paso 3: $fillable y relaciones (HasMany/BelongsTo) en Post, PostImage y Comment; verificado con tinker, incluida la cascada.
- Paso 4: PostController (index, show, store, like, destroy + helper addImageUrls) y CommentController (index, store) según el taller; php -l OK.
- Paso 5: 7 rutas en routes/api.php; se quitó la ruta de ejemplo /api/user (Sanctum) que agregó install:api.
- Paso 6: smoke test con curl OK (7/7 + 404/422/405). El post salió con id=2 porque SQLite AUTOINCREMENT no reutiliza el id 1 del test de tinker; en el Paso 8 se hace migrate:fresh.
- Decisión: se mantiene `shouldRenderJsonWhen` (api/*) que trae Laravel 13 en bootstrap/app.php; ningún punto del checklist del taller depende del redirect sin Accept. Documentar en README (Paso 10).
- Paso 7: tests/Feature/PostApiTest.php (9) y CommentApiTest.php (4); php artisan test 15/15, 88 assertions.
- Paso 8: Postman (no Thunder: CLI y Save To Workspace requieren suscripción). 13 requests (7 éxito + 6 en "Casos De Error"), todas OK; DELETE verificado por terminal (0 archivos, 0 filas). Export v2.1 en facebook-api-postman-collection.json; se fijó base_url=http://127.0.0.1:8000 (Postman exportaba vacío) y key "title" sin espacio en "422 - Sin header Accept". Sin cambios de código → no aplica commit "test: validar endpoints en Postman".
- Paso 9: sin cambios de código (no aplica commit fix). 7 rutas sin verbos ("like" es sustantivo: el recurso "me gusta"); ningún error responde 200; tests 15/15. Nota para README: en Laravel 10/11, sin `Accept: application/json` un error de validación redirige (302); Laravel 13 trae `shouldRenderJsonWhen` para `api/*` en bootstrap/app.php y responde 422 JSON igual (verificado en Postman con "422 - Sin header Accept"). Se mantiene; el header Accept se envía siempre por buena práctica.
- Paso 10: README.md (autor Juan Camilo Calderon, individual). Comandos de instalación verificados en frío en un clon temporal: migrate OK, GET /api/posts 200, POST sin title 422.
- Paso 11: checklist final 12/12 con evidencia real (curl contra el serve de Cami, DELETE borró archivos y filas del post de prueba). Falta solo crear el repo en GitHub y hacer push (lo hace Cami).

## Parte 2 · Retos avanzados

Detalle en `docs/PASOS_CLAUDE_CODE_PARTE2.md`.

- [x] Paso 0 — Preflight y puesta al día · `docs: agregar guía de la parte 2 del taller`
- [x] Paso 1 — Reto 1a · Registro, login y tokens con Sanctum · `feat: agregar registro e inicio de sesión con Sanctum`
- [x] Paso 2 — Reto 1b · Proteger endpoints y asociar el usuario · `feat: proteger endpoints y asociar publicaciones y comentarios al usuario`
- [ ] Paso 3 — Reto 3 · Likes únicos por usuario · `feat: likes únicos por usuario con tabla pivote`
- [ ] Paso 4 — Reto 2 · Paginación · `feat: paginar el listado de publicaciones`
- [ ] Paso 5 — Reto 4 · Búsqueda · `feat: buscar publicaciones por título o contenido`
- [ ] Paso 6 — Reto 5 · Correo al comentar · `feat: notificar por correo al dueño del post al comentar`
- [ ] Paso 7 — Reto 6 · Versionado · `feat: versionar la API en v1 y agregar v2`
- [ ] Paso 8 — Reto 7 · Documentación con Scribe · `docs: documentar la API con Scribe`
- [ ] Paso 9 — Revisión de pruebas · `test: completar pruebas de la parte 2` (si aplica)
- [ ] Paso 10 — Pruebas en Postman (guiadas, las hace Cami) · `test: exportar colección de Postman de la parte 2`
- [ ] Paso 11 — README y preguntas guía · `docs: actualizar README con la parte 2`
- [ ] Paso 12 — Checklist final y evidencias

### Notas Parte 2

- Paso 0: entorno OK (PHP 8.4.1, Sanctum v4.3.3 ya instalado, `personal_access_tokens` migrada, 15/15). Decisiones D1–D13 con defaults; orden de la guía; D9 fuera; alias (a); posts sin dueño no se borran.
- Paso 1: HasApiTokens en User; AuthController (register 201, login 200/401, logout revoca el token actual, me); rutas /register, /login públicas y /logout, /user con auth:sanctum; AuthApiTest (6). 21/21. No se corrió la Recomendación del Reto 1 (Sanctum ya instalado; vendor:publish duplicaría la migración).
- Paso 2: migraciones nuevas add_user_id_to_posts/comments (nullable, nullOnDelete; las de la Parte 1 no se editan porque ya están aplicadas y versionadas). Relaciones user/posts/comments; store/like/comments/destroy con auth:sanctum; PostPolicy (403 a post ajeno y a posts sin dueño); lecturas con user:id,name. Tests cambiados: 7 de PostApiTest y 4 de CommentApiTest (necesitan token; "comentar sin author 422" pasa a "sin content 422"). Nuevos: 6 + 2. 29/29.
