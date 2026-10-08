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
- No se usa Sanctum ni autenticación.
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
