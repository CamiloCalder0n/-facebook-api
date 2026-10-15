# PASOS PARA CLAUDE CODE — TALLER 3 · Docker + PostgreSQL en Render

> Documento aparte de las Partes 1 y 2. Copia este archivo y `Taller_3_Despliegue_API_REST.md` en `docs/` del repo `facebook-api` y dile a Claude Code:
> **"Lee docs/PASOS_CLAUDE_CODE_TALLER3.md y empieza por el Paso 0."**
> Fuente: `docs/Taller_3_Despliegue_API_REST.md`. **Alcance: solo lo que pide el taller.** Los únicos cambios que no vienen en el PDF son los de la sección "AJUSTES OBLIGATORIOS", porque sin ellos el despliegue falla con este proyecto. Si algo de aquí contradice al taller, avísame y no decidas solo.

---

## REGLAS DE TRABAJO (las mismas de las Partes 1 y 2)

1. **Un paso a la vez.** Al terminar cada paso: resumen de máx. 5 líneas, la verificación, y **DETENTE** hasta que te diga "siguiente".
2. **NO ejecutes `git commit`, `git push` ni `git tag`.** Los hago yo. Tú me das el mensaje de commit sugerido.
3. **No saltes nada y no inventes.** Si no puedes verificar algo, dilo. Si falta un dato (URL de Render, región, credenciales), pregúntame.
4. **Secretos fuera del repo y del chat.** Nunca escribas en archivos versionados ni me pidas pegar aquí: `APP_KEY`, la contraseña de la BD de Render, la Internal Database URL ni tokens. Esos valores van **solo** en el panel de Render. Revisa `git diff` antes de cada commit sugerido.
5. **La suite sigue en verde.** `php artisan test` (67 tests, SQLite en memoria) debe pasar al final de cada paso que toque código.
6. **Economía de tokens:** no releas lo que acabas de escribir; salidas recortadas (`| tail -n 40`); subagentes solo donde dice 🤖 (máx. 2 a la vez, respuesta ≤ 10 líneas). Sugiéreme `/compact` al cerrar cada sección grande.
7. **Idioma:** español colombiano casual y directo. Código y commits como el taller.
8. **Ignora `AGENTS.md`** (instrucciones de Laravel Boost, no son del taller).

---

## CONTEXTO DEL REPO (estado al terminar la Parte 2, verificado)

- Repo: https://github.com/CamiloCalder0n/-facebook-api (rama `main`, tag `parte-1`). Laravel **13.17**, `composer.json` exige **PHP ^8.3**; en local se usa PHP 8.4 y SQLite.
- API v1 (`/api/v1/...`), alias sin versión (`/api/...`) y v2 (`/api/v2/posts`). Auth con **Sanctum**: `POST /api/posts` **requiere token Bearer** (hay que hacer register/login primero).
- `config/database.php`: `'default' => env('DB_CONNECTION', 'sqlite')` y los bloques usan `'url' => env('DB_URL')`.
- `bootstrap/app.php`: tiene `shouldRenderJsonWhen` para `api/*`; **no** tiene `trustProxies`.
- **Scribe está en `require-dev`** y `config/scribe.php` importa clases de `Knuckles\Scribe\...`.
- La búsqueda usa `where('title', 'like', ...)` y `orWhere('content', 'like', ...)`.
- El like ya captura `UniqueConstraintViolationException`, que funciona igual en PostgreSQL. El correo al comentar está dentro de `try/catch`, así que un fallo de correo no rompe el comentario.
- `phpunit.xml` usa SQLite en memoria y `MAIL_MAILER=array`, así que los tests no dependen de PostgreSQL.
- 67 tests en verde; colección de Postman Parte 2 con 65/65.

---

## AJUSTES OBLIGATORIOS (sin ellos el taller, copiado tal cual, falla en este proyecto)

| # | Problema si se copia el PDF tal cual | Ajuste |
|---|---|---|
| A1 | El Dockerfile usa `php:8.2-fpm`, pero `composer.json` exige PHP ^8.3 (Laravel 13), así que `composer install` falla en el build | `FROM php:8.4-fpm` (la misma versión mayor que usas en local) |
| A2 | `composer install --no-dev` deja fuera Scribe; `config/scribe.php` usa clases de Scribe, así que `php artisan config:cache` del entrypoint revienta y, con `set -e`, el contenedor nunca arranca | Mover Scribe a `require` (`composer require knuckleswtf/scribe`); de paso, `/docs` funciona en producción |
| A3 | `COPY . /var/www` copiaría tu `.env`, `vendor/`, `database.sqlite` e imágenes de prueba en un build local | `.dockerignore`: `.env`, `.env.*` (menos `.env.example`), `.git`, `vendor`, `node_modules`, `database/*.sqlite`, `storage/logs/*`, `storage/app/public/*`, `public/storage`, `bootstrap/cache/*.php` |
| A4 | Render termina el HTTPS en su proxy; sin confiar en él, Laravel genera los `image_url` con `http://` | En `bootstrap/app.php`: `$middleware->trustProxies(at: '*');` |
| A5 | En PostgreSQL `LIKE` **distingue mayúsculas** (en SQLite no): `?search=viaje` dejaría de encontrar "Viaje" | Usar `whereLike()` / `orWhereLike()` (sin distinguir mayúsculas: en pgsql usa `ILIKE`), dentro del mismo `where` agrupado |

### Avisos (no se cambian, pero se documentan en el README)

- **W1 · El `docker-compose.yml` monta tu carpeta (`./:/var/www`).** El `config:cache` del entrypoint escribe `bootstrap/cache/config.php` **en tu proyecto**, con `DB_HOST=db`. Después de usar Compose hay que correr `php artisan optimize:clear` en local, o `php artisan serve` y los tests quedan apuntando a la BD del contenedor.
- **W2 · Disco efímero en Render Free.** Las imágenes subidas se guardan en el disco del contenedor y **se pierden** al redesplegar o cuando el servicio se duerme y despierta. Los datos de PostgreSQL sí persisten. Resolverlo requiere almacenamiento externo (S3/Cloudinary), que el taller no pide. Las capturas de imágenes se toman justo después de subirlas.
- **W3 · Diagrama vs. código del taller.** El diagrama habla de "Nginx + PHP-FPM", pero el entrypoint del propio taller sirve con `php artisan serve`. Se sigue el código del taller y el README lo aclara.
- **W4 · Base de datos Free de Render.** Tiene fecha de vencimiento (Render la muestra en el panel de la BD). Revisa que no venza antes de la calificación.
- **W5 · Commit del taller.** El taller usa el tipo `config:` en su commit; se respeta tal cual.

---

## DECISIONES (confírmalas conmigo en el Paso 0; propón estos defaults)

| # | Decisión | Default |
|---|---|---|
| D1 | Congelar la Parte 2 antes de empezar | Yo creo el tag `parte-2` sobre el último commit actual |
| D2 | Scribe en producción (A2) | Moverlo a `require`, así `/docs` funciona en Render |
| D3 | Correo en producción | `MAIL_MAILER=log` (sin credenciales; el comentario igual responde 201). Mailtrap en producción solo si lo pido |
| D4 | Nombres en Render | BD `facebook-db-prod` · Web Service `laravel-facebook-api-prod` (si ese nombre ya existe, se agrega un sufijo, p. ej. `-cami`) |
| D5 | Región | La misma para la BD y el Web Service (p. ej. Oregon, US West) |
| D6 | `url` de pgsql | `'url' => env('DATABASE_URL', env('DB_URL'))`: cumple el `DATABASE_URL` del taller sin romper el `DB_URL` de Laravel 13. Igual nos conectamos con las variables separadas de la tabla del taller |
| D7 | Postman en producción | Lo que pide el taller (register/login → POST `/api/posts` 201 → GET `/api/posts` 200) con un Environment nuevo **"Render · Producción"**. Correr la colección completa de la Parte 2 contra Render solo si lo pido (una vez, con la BD limpia, porque los emails de registro son fijos) |

---

## PASO 0 — Preflight  ·  commit: `docs: agregar guía del taller 3`

**Tareas**
1. Lee `CLAUDE.md`, `PROGRESS.md`, `docs/Taller_3_Despliegue_API_REST.md` y esta guía.
2. Verifica y repórtame en una tabla:
   - `git status` limpio;
   - `php -v`;
   - `php artisan test` (67 en verde);
   - `docker --version` y `docker compose version` (sección 4 del taller; en Mac es **Docker Desktop**). Si falta, guíame para instalarlo y espera.
3. Recuérdame (lo hago yo):
   ```bash
   git tag -a parte-2 -m "Entrega Parte 2: retos avanzados"
   git push origin parte-2
   ```
4. Pregúntame si ya tengo cuenta en **Render** (idealmente entrando con GitHub para que vea el repo) y confirma conmigo D1–D7.
5. `CLAUDE.md`: agrega la sección **"Taller 3 · Despliegue"** con las decisiones, los ajustes A1–A5 y los avisos W1–W5. `PROGRESS.md`: checklist de los pasos 1–10 de esta guía.

**Verificación:** tabla de entorno; 67/67; Docker responde.
**STOP.**

---

## PASO 1 — Laravel listo para PostgreSQL (sección 3 del taller + A5)  ·  commits: `config: adaptar la configuración de base de datos a PostgreSQL` y `fix: búsqueda sin distinguir mayúsculas en PostgreSQL`

**Tareas**
1. `config/database.php`:
   - `'default' => env('DB_CONNECTION', 'pgsql')`, como pide el taller. Tu `.env` local sigue en `sqlite` y `phpunit.xml` también, así que lo local no cambia.
   - Bloque `pgsql`: compáralo con el del taller y deja `driver`, `url` (D6), `host`, `port` `5432`, `database`, `username`, `password`, `charset` `utf8`, `prefix`, `prefix_indexes`, `search_path` `public`, `sslmode` `prefer`. Muéstrame el diff.
2. **A5:** en `Api\V1\PostController@index` cambia la búsqueda a:
   ```php
   ->when($request->search, fn ($q, $s) => $q->where(fn ($w) =>
       $w->whereLike('title', "%{$s}%")->orWhereLike('content', "%{$s}%")))
   ```
   Antes de cambiarlo, confirma con `grep -rn "function whereLike" vendor/laravel/framework/src` que el método existe. Revisa si el controlador V2 también busca y aplica lo mismo.
3. Agrega 1 test: buscar en minúsculas encuentra un título con mayúscula (`Viaje`).
4. `php artisan test` en verde.

**Verificación:** diff de `config/database.php`; 68/68 (o el conteo nuevo).
**STOP.**

---

## PASO 2 — Ajustes previos para producción (A2 + A4)  ·  commit: `fix: preparar la aplicación para producción en Render`

**Tareas**
1. **A2:** mueve Scribe de `require-dev` a `require` (`composer require knuckleswtf/scribe` con la misma versión mayor ya instalada). Verifica que `composer.json` y `composer.lock` cambiaron y que `php artisan scribe:generate` sigue funcionando.
2. **A4:** en `bootstrap/app.php`, dentro de `withMiddleware`: `$middleware->trustProxies(at: '*');`. Explícame en 2 líneas por qué (HTTPS terminado en el proxy de Render, y que `image_url` salga con `https://`).
3. `php artisan test` en verde.

**Verificación:** `composer show knuckleswtf/scribe`; tests en verde.
**STOP.**

---

## PASO 3 — Dockerfile, entrypoint y .dockerignore (sección 5 del taller, Pasos 1 y 2)  ·  commit: `config: configurar Dockerfile, entrypoint y soporte PostgreSQL` (mensaje literal del taller)

**Tareas**
1. `Dockerfile` en la raíz: **igual al del taller**, con un único cambio: `FROM php:8.4-fpm` (A1).
   - Si el build falla por una extensión que la imagen oficial ya trae compilada (por ejemplo `mbstring`), quita **solo esa** de `docker-php-ext-install`, explícame el error y déjalo anotado en `PROGRESS.md`.
2. `docker-entrypoint.sh` en la raíz: **igual al del taller**. Con saltos de línea **LF** y permiso de ejecución también en git: `git update-index --chmod=+x docker-entrypoint.sh` (dime que lo haga yo si prefieres).
3. `.dockerignore` (A3) con la lista de la tabla de ajustes.
4. Build local de verificación: `docker build -t facebook-api-app .` (muéstrame solo las últimas ~15 líneas). En Mac con Apple Silicon la imagen local sale `arm64` y Render compila la suya en `amd64`; es normal.
5. Comprueba que la imagen **no** contiene `.env`: `docker run --rm --entrypoint sh facebook-api-app -c "ls -a /var/www | grep -c '^\.env$'"` → `0`.

**Verificación:** build OK; `.env` no está en la imagen; `php -m` dentro de la imagen muestra `pdo_pgsql` y `pgsql`.
**STOP.**

---

## PASO 4 — Prueba local con Docker Compose + PostgreSQL (sección 5, Paso 3)  ·  commit: `config: agregar docker-compose para pruebas locales con PostgreSQL`

**Tareas**
1. `docker-compose.yml` en la raíz: **igual al del taller**. La línea `version: '3.8'` genera un aviso de "obsolete" en Compose v2; es inofensivo y se deja como el taller.
2. `docker compose up -d --build` y luego `docker compose logs app | tail -n 40`: debe verse "=== Ejecutando migraciones de base de datos en PostgreSQL ===" con las migraciones en *DONE* y el servidor escuchando en 10000.
3. Smoke test contra `http://localhost:8080` con curl (mostrando solo código HTTP y una parte del cuerpo):
   1. `POST /api/v1/register` → 201 (usa un email de prueba y **no imprimas el token completo**);
   2. `POST /api/posts` con el token y 1 imagen → 201;
   3. `GET /api/posts` → 200 con el post;
   4. `GET /api/posts?search=VIAJE` → encuentra (prueba de A5 en PostgreSQL).
4. Confirma que los datos están en PostgreSQL: `docker compose exec db psql -U postgres -d facebook_db -c "select count(*) from posts;"`.
5. `docker compose down` (sin `-v`, para conservar el volumen) y **W1**: `php artisan optimize:clear` en local y `php artisan test` en verde.

**Verificación:** tabla del smoke test, conteo en PostgreSQL y tests locales en verde después de limpiar cachés.
**STOP.** Sugiere `/compact`.

---

## PASO 5 — Subir a GitHub (sección 7.1 del taller, lo hago yo)

Recuérdame:
```bash
git push origin main
```
y verifica conmigo (`git log --oneline origin/main -5`) que los commits de los pasos 1–4 están en GitHub, porque Render despliega desde ahí.
**STOP.**

---

## PASO 6 — PostgreSQL en Render (sección 6 del taller, lo hago yo, tú me guías)

Guíame clic por clic:
1. https://dashboard.render.com → **New +** → **PostgreSQL**.
2. Name `facebook-db-prod`; Database `facebook_api`; User `fb_user`; Region (D5); Instance Type **Free** → **Create Database**.
3. En la página de la BD, ubicar (**sin pegarlos en el chat**): Internal Database URL, Hostname interno, Port, Database, Username, Password. Recuérdame que los copie a un lugar seguro temporal.
4. 📸 Evidencia: captura del Dashboard con la BD en estado **Available**, **sin** mostrar la contraseña ni la URL completa.

**STOP** hasta que yo confirme que la BD está activa.

---

## PASO 7 — Web Service y variables de entorno (sección 7 del taller, lo hago yo, tú me guías)

1. **APP_KEY:** pídeme que yo corra `php artisan key:generate --show` en mi terminal. Tú **no** la generes ni la leas, y la llave no se pega en el chat ni en el repo.
2. **New +** → **Web Service** → conectar GitHub → repo `-facebook-api`.
3. Name `laravel-facebook-api-prod` (D4); Environment/Language **Docker**; Region **la misma de la BD**; Branch `main`; Instance Type **Free**.
4. **Environment Variables**, exactamente la tabla del taller:
   `APP_NAME=FacebookAPI`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY=<la mía>`, `APP_URL=https://<servicio>.onrender.com`, `DB_CONNECTION=pgsql`, `DB_HOST=<Internal Host>`, `DB_PORT=5432`, `DB_DATABASE=facebook_api`, `DB_USERNAME=fb_user`, `DB_PASSWORD=<la de Render>`.
   - Más `MAIL_MAILER=log` (D3).
   - Si al crear el servicio la URL final es distinta, actualizo `APP_URL` y redespliego.
   - Opcional para depurar: `LOG_CHANNEL=stderr`, así los errores de Laravel salen en los logs de Render. Pregúntame.
5. **Create Web Service** y seguir los **Logs**: build → "=== Ejecutando migraciones…" → migraciones *DONE* → servidor en el puerto → estado **Live**. Si falla, diagnostica con la sección 9 del taller (driver pgsql, conexión, `APP_KEY`) y con A1–A4.
6. 📸 Evidencias:
   - sección **Environment** con los valores secretos **ocultos** (con el "ojo" cerrado);
   - Web Service en **Live**;
   - logs mostrando las migraciones automáticas.

**Verificación:** `curl -s -o /dev/null -w "%{http_code}" https://<servicio>.onrender.com/up` → 200. La primera petición puede tardar 30–50 s si el servicio estaba dormido.
**STOP.**

---

## PASO 8 — Pruebas en Postman contra producción (sección 8 del taller, guiadas, las hago yo)

1. En Postman: **Environment** nuevo "Render · Producción" con `base_url = https://<servicio>.onrender.com`; seleccionarlo arriba a la derecha. El token del login se guarda en una variable, que **se vacía antes de exportar o capturar**.
2. Pruebas, una por una, con `Accept: application/json`:
   1. `POST {{base_url}}/api/register` → **201** (o `/api/v1/register`);
   2. `POST {{base_url}}/api/login` → **200** y guardar el token;
   3. `POST {{base_url}}/api/posts`, form-data con `title`, `content` e `images[]` (File), Bearer token → **201**. Abrir enseguida el `image_url` en el navegador (W2) y verificar que empieza por `https://` (A4);
   4. `GET {{base_url}}/api/posts` → **200** con el post persistido en PostgreSQL;
   5. Persistencia: en Render, reiniciar el servicio (menú **Manual Deploy**: "Restart service" o "Deploy latest commit"; también sirve esperar a que se duerma) y repetir el `GET`. El post **sigue ahí** (PostgreSQL); su imagen puede dar 404 (W2), y eso se documenta.
3. (D7, opcional) Correr la colección de la Parte 2 contra Render con el Runner o con Newman (`--env-var base_url=https://...`), solo si lo pido.
4. 📸 Evidencias: capturas de Postman con **201** (crear) y **200** (listar), mostrando la URL de Render. Sin tokens visibles.

**STOP** hasta que yo confirme.

---

## PASO 9 — README  ·  commit: `docs: documentar el despliegue con Docker y Render`

Sección **"Taller 3 · Despliegue"** en el README:
- arquitectura (diagrama del taller, aclarando W3: `php artisan serve` dentro del contenedor y PHP 8.4 por A1);
- cómo correr con Docker Compose y el aviso W1;
- variables de entorno de Render (**solo los nombres**, sin valores);
- URL pública de producción;
- troubleshooting (sección 9 del taller + A2 y A4);
- limitaciones del plan Free (suspensión de 15 min, W2 y W4).

**Verificación:** el README no contiene secretos (`grep -nE "base64:|DB_PASSWORD=.+|postgres(ql)?://" README.md` vacío).
**STOP.**

---

## PASO 10 — Checklist final del taller y evidencias 🤖

1. 🤖 Subagentes de **solo lectura** (≤ 10 líneas, PASS/FAIL por punto):
   - **docker-checker:** Dockerfile (pdo_pgsql, PHP 8.4), entrypoint (migrate `--force`, ejecutable, LF), `.dockerignore`, `docker-compose.yml` igual al taller.
   - **config-checker:** `config/database.php` (default pgsql, bloque pgsql), `trustProxies`, `whereLike`, Scribe en `require`.
   - **secrets-checker:** nada de `.env`, `APP_KEY`, contraseñas, URLs de BD ni tokens en archivos versionados o en el historial de los commits nuevos (`git log -p parte-2..HEAD`).
2. Tabla con los **7 criterios de la sección 10 del taller** (criterio | evidencia | estado) y crea `docs/EVIDENCIAS_TALLER3.md` con la lista de capturas que debo tener, con nombres tipo `T3-01-render-db-available.png`:
   - config/database.php;
   - Dockerfile en GitHub;
   - entrypoint;
   - BD en Render;
   - Environment (secretos ocultos);
   - Live con la URL;
   - logs de migraciones;
   - Postman 201 y 200 contra Render;
   - imagen por `image_url` https;
   - persistencia después del restart;
   - `php artisan test`.
3. `git log --oneline parte-2..HEAD` (solo lectura) y recuérdame `git push origin main`. Actualiza `PROGRESS.md` y termina.

---

## MAPA DE COMMITS (Taller 3)

| # | Mensaje |
|---|---|
| — | tag `parte-2` (lo hago yo) |
| 0 | `docs: agregar guía del taller 3` |
| 1a | `config: adaptar la configuración de base de datos a PostgreSQL` |
| 1b | `fix: búsqueda sin distinguir mayúsculas en PostgreSQL` |
| 2 | `fix: preparar la aplicación para producción en Render` |
| 3 | `config: configurar Dockerfile, entrypoint y soporte PostgreSQL` *(literal del taller)* |
| 4 | `config: agregar docker-compose para pruebas locales con PostgreSQL` |
| 9 | `docs: documentar el despliegue con Docker y Render` |
| 10 | `docs: agregar lista de evidencias del taller 3` *(y `fix: ...` solo si los checkers encuentran algo)* |
