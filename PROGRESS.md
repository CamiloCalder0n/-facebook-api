# PROGRESS — API REST tipo Facebook

Detalle de cada paso en `docs/PASOS_CLAUDE_CODE.md`.

- [x] Paso 0 — Preflight, CLAUDE.md y PROGRESS.md
- [x] Paso 1 — Inicialización del proyecto (SQLite, storage:link, install:api, .env) · `feat: crear proyecto Laravel`
- [x] Paso 2 — Modelos y migraciones · `feat: crear modelos y migraciones para posts, comentarios e imágenes`
- [ ] Paso 3 — Relaciones Eloquent y `$fillable` · `feat: configurar relaciones entre Post, Comment y PostImage`
- [ ] Paso 4 — Controladores · `feat: implementar CRUD y lógica de likes`
- [ ] Paso 5 — Rutas API · `feat: registrar endpoints de la API REST`
- [ ] Paso 6 — Servidor y smoke test con curl
- [ ] Paso 7 — (Opcional) Tests automáticos · `test: agregar pruebas feature de la API`
- [ ] Paso 8 — Pruebas en Postman (guiadas) · `test: validar endpoints en Postman` / `test: exportar colección de Postman`
- [ ] Paso 9 — Casos de error y ajustes · `fix: corregir errores de validación y almacenamiento`
- [ ] Paso 10 — README.md · `docs: agregar README del proyecto`
- [ ] Paso 11 — Checklist final "Dejarlo funcionando"

## Notas

- Paso 0: entorno OK (PHP 8.4.1, Composer 2.8.12, Laravel 13.35.0, pdo_sqlite/fileinfo/gd activas). Proyecto y `git init -b main` ya hechos por Cami antes del Paso 1.
- Paso 1: SQLite y APP_KEY ya venían del create-project; se ajustó APP_NAME/APP_URL, storage:link, install:api (Sanctum + routes/api.php + personal_access_tokens migrada). Se sirve con `php artisan serve` (PHP 8.4), no con Apache de XAMPP (PHP 8.2.4 < 8.4.1 requerido).
- Paso 2: 3 migraciones creadas y migradas; FKs con ON DELETE CASCADE; PRAGMA foreign_keys=1 (DB_FOREIGN_KEYS default true en config/database.php).
