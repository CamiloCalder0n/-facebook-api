# Introduction

API REST tipo Facebook: publicaciones con imágenes, comentarios, likes por usuario, búsqueda, paginación y autenticación con tokens (Laravel Sanctum).

<aside>
    <strong>Base URL</strong>: <code>http://127.0.0.1:8000</code>
</aside>

    Documentación del taller de Arquitectura y Desarrollo Backend (UNAB).

    - **v1** (`/api/v1/...`): API completa. Las mismas rutas existen sin versión (`/api/...`) como alias de compatibilidad.
    - **v2** (`/api/v2/posts`): lectura de publicaciones con un formato nuevo (API Resources).
    - Envía siempre `Accept: application/json`. Las rutas protegidas piden `Authorization: Bearer {TOKEN}`.

