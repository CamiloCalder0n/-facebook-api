# Evidencias · Taller 3 (Despliegue)

Capturas para la entrega del Taller 3. Guárdalas directo en una carpeta (por ejemplo `Escritorio/evidencias-taller3`) con `Cmd + Shift + 5` → Opciones → Guardar en → Otra ubicación.

**Regla de oro:** en ninguna captura puede verse un secreto. Eso incluye la `APP_KEY`, la contraseña de la BD, la Internal/External Database URL y los tokens completos. En Render los valores secretos se ven como `••••`: no les des al ojo 👁 antes de capturar. Para capturar una sola ventana usa `Cmd + Shift + 4` y luego `espacio`.

URL de producción: https://laravel-facebook-api-prod-wyhn.onrender.com

## Código (repositorio)

| # | Archivo | Qué capturar | Criterio del taller |
|---|---|---|---|
| 01 | `T3-01-config-database.png` | `config/database.php` en GitHub: `default` en `pgsql` y el bloque `pgsql` | 1 · Laravel adaptado a PostgreSQL |
| 02 | `T3-02-dockerfile-github.png` | `Dockerfile` en GitHub: `FROM php:8.4-fpm` y `docker-php-ext-install pdo_pgsql pgsql` | 2 · Dockerfile con `pdo_pgsql` |
| 03 | `T3-03-entrypoint.png` | `docker-entrypoint.sh` en GitHub con `migrate --force` | 3 · Entrypoint que automatiza migraciones |

## Render

| # | Archivo | Qué capturar | Criterio del taller |
|---|---|---|---|
| 04 | `T3-04-render-db-available.png` | BD `facebook-db-prod`: Status **Available**, PostgreSQL 18, Oregon, Free. **Sin** la sección Connections | 4 · PostgreSQL activa en Render |
| 05 | `T3-05-render-environment.png` | Web Service → **Environment** con las 13 variables y los secretos ocultos (`••••`) | 5 · Variables vinculadas |
| 06 | `T3-06-render-live.png` | Deploy con **Deploy succeeded · Live** y la URL pública | 6 · Despliegue Live |
| 07 | `T3-07-render-logs-migraciones.png` | **Logs** del deploy: migraciones en `DONE` y el servidor en el puerto 10000 | 6 · Migraciones automáticas |

## Postman contra producción (Environment "Render · Producción")

| # | Archivo | Prueba | Esperado | Criterio del taller |
|---|---|---|---|---|
| 08 | `T3-08-postman-register-201.png` | `POST /api/v1/register` | 201 | 7 · Pruebas en producción |
| 09 | `T3-09-postman-crear-post-201.png` | `POST /api/v1/posts` con imagen (token de Login A) | 201 | 7 |
| 10 | `T3-10-postman-listar-200.png` | `GET /api/posts` | 200 con el post | 7 |
| 11 | `T3-11-imagen-https.png` | Abrir en el navegador la `image_url` de la respuesta | La imagen, servida por `https://...onrender.com/storage/...` | 7 |

## Extras

| # | Archivo | Qué capturar |
|---|---|---|
| 12 | `T3-12-persistencia-restart.png` | En Render: **Manual Deploy → Restart service** o **Deploy latest commit**. Cuando vuelva a Live, `GET /api/posts` en Postman sigue mostrando el post: los datos viven en PostgreSQL. La imagen puede dar 404 porque el disco del plan Free es efímero (ver el README) |
| 13 | `T3-13-php-artisan-test.png` | Terminal con `php artisan test` en verde (68 pruebas) |
| 14 | `T3-14-docs-produccion.png` | `/docs` abierto en la URL de producción (Scribe) |

## Antes de entregar

- [ ] Rotar la `APP_KEY` y la contraseña de la BD en Render. Las dos aparecieron en capturas durante el despliegue.
- [ ] Correr **Auth → Logout** en Postman para revocar el token que se vio en una captura.
- [ ] Revisar que ninguna captura de esta lista muestre secretos.
- [ ] La BD Free vence el **14 de noviembre de 2026**: confirmar que la calificación sea antes de esa fecha o recrearla.
