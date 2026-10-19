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
- [x] Paso 3 — Reto 3 · Likes únicos por usuario · `feat: likes únicos por usuario con tabla pivote`
- [x] Paso 4 — Reto 2 · Paginación · `feat: paginar el listado de publicaciones`
- [x] Paso 5 — Reto 4 · Búsqueda · `feat: buscar publicaciones por título o contenido`
- [x] Paso 6 — Reto 5 · Correo al comentar · `feat: notificar por correo al dueño del post al comentar`
- [x] Paso 7 — Reto 6 · Versionado · `feat: versionar la API en v1 y agregar v2`
- [x] Paso 8 — Reto 7 · Documentación con Scribe · `docs: documentar la API con Scribe`
- [x] Paso 9 — Revisión de pruebas · `test: completar pruebas de la parte 2` (si aplica)
- [x] Paso 10 — Pruebas en Postman (guiadas, las hace Cami) · `test: exportar colección de Postman de la parte 2`
- [x] Paso 11 — README y preguntas guía · `docs: actualizar README con la parte 2`
- [x] Paso 12 — Checklist final y evidencias

### Notas Parte 2

- Paso 0: entorno OK (PHP 8.4.1, Sanctum v4.3.3 ya instalado, `personal_access_tokens` migrada, 15/15). Decisiones D1–D13 con defaults; orden de la guía; D9 fuera; alias (a); posts sin dueño no se borran.
- Paso 1: HasApiTokens en User; AuthController (register 201, login 200/401, logout revoca el token actual, me); rutas /register, /login públicas y /logout, /user con auth:sanctum; AuthApiTest (6). 21/21. No se corrió la Recomendación del Reto 1 (Sanctum ya instalado; vendor:publish duplicaría la migración).
- Paso 2: migraciones nuevas add_user_id_to_posts/comments (nullable, nullOnDelete; las de la Parte 1 no se editan porque ya están aplicadas y versionadas). Relaciones user/posts/comments; store/like/comments/destroy con auth:sanctum; PostPolicy (403 a post ajeno y a posts sin dueño); lecturas con user:id,name. Tests cambiados: 7 de PostApiTest y 4 de CommentApiTest (necesitan token; "comentar sin author 422" pasa a "sin content 422"). Nuevos: 6 + 2. 29/29.
- Paso 3: post_user con unique(post_id,user_id) y cascada; Post::likers / User::likedPosts; like 200 / 409 (también ante UniqueConstraintViolationException por carrera); likes_count = conteo real del pivote. LikeApiTest (6). 35/35.
- Paso 4: index con paginate(10)->through(addImageUrls). Cambia el formato de GET /api/posts (arreglo → objeto con data); test de la Parte 1 actualizado: test_listar_posts (ahora lee data). PaginationApiTest (4). 39/39.
- Paso 5: ?search= con where agrupado (title OR content) + paginate(10)->withQueryString(). SearchApiTest (4). 43/43.
- Paso 6: NewCommentMail (markdown mail.new-comment, asunto "Nuevo comentario": título del post, quién comentó y el texto). Se envía al dueño solo si existe y no es quien comenta. Verificado con MAIL_MAILER=log (laravel.log). CommentMailTest (4). 47/47. Pendiente: evidencia en Mailtrap (credenciales solo en el .env de Cami, en el Paso 10).
- Paso 7: controladores movidos con git mv a Api/V1; rutas en closure $v1 montada en /api/v1 y en /api (alias); v2 = Api/V2/PostController con PostResource/ImageResource/UserResource (author {id,name}, images {id,url}, comments_count, paginado con links/meta). 24 rutas: 11 v1 + 11 alias + 2 v2. VersioningApiTest (7). 54/54.
- Revisión 1 (subagente Opus sobre Pasos 1–6): todo PASS. Ajuste: si el envío de correo falla, el comentario igual responde 201 y el error se reporta (antes daría 500 con el comentario ya guardado). Test nuevo. 55/55.
- Paso 8: Scribe 5.11 (--dry-run resolvió con Laravel 13). config: solo api/v1/* y api/v2/* (el alias se omite para no duplicar), auth Bearer, tipo laravel (/docs), response calls solo GET (Scribe no las usó: hay @response en todo). Docblocks por subagente Opus (4 controladores, solo comentarios). Generado: .scribe/, resources/views/scribe, public/vendor/scribe, storage/app/private/scribe/{collection.json,openapi.yaml} (se versionan para que /docs, /docs.postman y /docs.openapi funcionen al clonar; Scribe dice que commitear .scribe es opcional y preserva ediciones). DocsTest (2). 57/57.
- Paso 9: subagente Opus (solo lectura) comparó tests vs endpoints y D4–D11: faltaban 8 casos. Agregados: logout revoca solo el token actual, logout sin token 401, login sin datos 422, registro inválido 422, carrera de like → 409 (verificado por mutación: sin el catch da 500), DELETE inexistente 404, comentarios de post inexistente 404, likes_count = post_user en v1/v2, rutas v1 de comentarios y borrado. 67/67, 299 aserciones.
- Paso 11 (adelantado antes del Paso 10, que depende de Cami en Postman): README con sección "Parte 2 · Retos avanzados" (modelo, auth con curl, tabla de endpoints, paginación/búsqueda, likes 409, correo log/Mailtrap sin credenciales, versionado y aviso honesto sobre el alias, Scribe /docs, tests, Postman) y "Preguntas guía" (borrador para revisión de Cami). .env.example con comentario de MAIL_* sin valores reales.
- Paso 12: 4 checkers Opus de solo lectura (máx. 2 a la vez) sobre el árbol ef6e25d. auth-checker 6/6 PASS, data-checker 6/6 PASS, contract-checker 6/6 PASS (11 v1 + 11 alias + 2 v2), docs-checker 5/6 PASS + 1 FAIL leve (README decía "67 pruebas en tests/Feature"; son 66 + 1 Unit) → corregido. Comentario de cascada en destroy ahora menciona post_user. docs/EVIDENCIAS_PARTE2.md con las 32 capturas sugeridas. 67/67.
- Pendiente de Cami: tag parte-1, correr el script de commits, Paso 10 (Postman + Mailtrap) y su commit `test: exportar colección de Postman de la parte 2`, y el push.
- Paso 10: Cami hizo a mano las pruebas 1–5 (registros, logins, clave mala). Por pedido de Cami, Claude generó la colección completa (25 requests en 7 carpetas, variables base_url/token/token_b/post_id, auth Bearer a nivel de colección, scripts que guardan tokens y post_id, y un test por request) → facebook-api-parte2-postman-collection.json (sin tokens guardados). Validada con Newman 6.2.3 sobre BD limpia: 25 requests + 11 internas, 65 aserciones, 0 fallos; borrado verificado por terminal (0 archivos, 0 filas en post_images/comments/post_user). Cami la importa y la corre con el Collection Runner para las capturas.

## Taller 3 · Despliegue (Docker + PostgreSQL en Render)

- [x] Paso 0 — Preflight · `docs: agregar guía del taller 3`
- [x] Paso 1 — Laravel listo para PostgreSQL + A5 · `config: adaptar la configuración de base de datos a PostgreSQL` y `fix: búsqueda sin distinguir mayúsculas en PostgreSQL`
- [x] Paso 2 — Ajustes de producción (A2 + A4) · `fix: preparar la aplicación para producción en Render`
- [x] Paso 3 — Dockerfile, entrypoint y .dockerignore · `config: configurar Dockerfile, entrypoint y soporte PostgreSQL`
- [x] Paso 4 — Docker Compose + PostgreSQL local · `config: agregar docker-compose para pruebas locales con PostgreSQL`
- [ ] Paso 5 — Push a GitHub (Cami)
- [ ] Paso 6 — PostgreSQL en Render (Cami, guiado)
- [ ] Paso 7 — Web Service y variables de entorno (Cami, guiado)
- [ ] Paso 8 — Postman contra producción (Cami, guiado)
- [ ] Paso 9 — README · `docs: documentar el despliegue con Docker y Render`
- [ ] Paso 10 — Checklist final y evidencias · `docs: agregar lista de evidencias del taller 3`

### Notas Taller 3

- Paso 0: git limpio, PHP 8.4.1, 67/67 (299 aserciones). Mac arm64. Docker Desktop instalado después: Docker 29.8.2, Compose v5.6.0, engine aarch64 (CLI en ~/.docker/bin; terminales abiertas antes de instalar no lo ven hasta reabrirlas). Tag parte-2 creado y pusheado por Cami. Cuenta de Render creada. D1–D7 con defaults; región Oregon.
- Paso 1: default pgsql; bloque pgsql con url DATABASE_URL→DB_URL (D6) y defaults forge/forge del taller (charset utf8, sslmode prefer, search_path public ya estaban, vía env con el mismo default). whereLike/orWhereLike en V1 y V2 (existe en Query\Builder; PostgresGrammar usa ILIKE). Test test_busqueda_no_distingue_mayusculas (v1 y v2): en SQLite solo documenta; la prueba real en pgsql es el Paso 4. 68/68.
- Paso 2: composer require knuckleswtf/scribe:^5.11 (la misma 5.11.0): pasa de require-dev a require; composer.lock solo mueve paquetes de packages-dev a packages (mismas versiones, verificado). scribe:generate OK (solo cambió fecha/ids → revertido). trustProxies(at: '*') en bootstrap/app.php. 68/68.
- Paso 3: Dockerfile igual al taller salvo FROM php:8.4-fpm (A1); build OK sin quitar extensiones (mbstring compiló). Imagen: PHP 8.4.26, pdo_pgsql/pgsql/mbstring/gd/zip/bcmath; sin .env, sin database.sqlite, storage/app/public vacío, sin phpunit (--no-dev) y con Scribe (A2). docker-entrypoint.sh igual al taller, LF, modo 100755 en git. .dockerignore (A3). Imagen local arm64; Render compila amd64.
- Paso 4: docker-compose.yml igual al taller (aviso 'version obsolete', inofensivo). Logs: config/route/view cache, 10 migraciones DONE en pgsql, storage:link, servidor en 10000. Smoke en :8080: register 201, POST /api/posts con imagen 201, GET 200 (total 1), search=VIAJE 200 encuentra 'Viaje a Santander', imagen 200, /docs 200. psql: posts=1, users=1; LIKE '%VIAJE%' da 0 e ILIKE 1 (prueba real de A5). down sin -v (volumen facebook-api_pgdata conservado). W1 confirmado: quedaron config.php y routes-v7.php en bootstrap/cache; el primer optimize:clear falló a medias (cache:clear con la config del contenedor) y el segundo limpió todo. Hallazgo extra del montaje: storage:link del contenedor dejó public/storage → /var/www/storage/app/public (roto en el Mac); se arregla con rm public/storage && php artisan storage:link. Imagen de prueba borrada. 68/68.

- Paso 5: push OK (origin/main = 6aa441c).
- Paso 6: BD `facebook-db-prod` Available · PostgreSQL 18 · Oregon · Free. **Vence el 14 nov 2026 (W4).**
- Paso 7: Web Service Live en https://laravel-facebook-api-prod-wyhn.onrender.com (sufijo -wyhn puesto por Render). /up, /api/posts y /docs dan 200; login malo 401 (BD conectada); paginación con https (A4 OK); PHP 8.4.26. Falta: APP_URL con -wyhn y rotar la clave de la BD que salió en un pantallazo.
- Paso 8: Postman "Render · Producción": register 201, login 200, POST /api/v1/posts 201, GET /api/posts 200. Verificado: image_url https con -wyhn y responde 200 image/png; search=CARTAGENA total 1 (A5 en pgsql de Render). Nota: Registrar A no guarda token (lo guarda Login A); HEIC da 422 (solo jpeg/png/jpg/gif). Test "2 imágenes" 2/3 por subir 1 sola.
- Paso 9: README con la sección "Taller 3 · Despliegue con Docker y Render" + commits del taller 3. grep de secretos vacío. 68/68.
