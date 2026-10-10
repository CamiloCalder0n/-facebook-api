# Taller Adicional · Retos Avanzados de API REST con Laravel

**Autenticación · Paginación · Likes · Búsqueda · Versionado**

Archivo origen: `Taller_Parte_2_API_REST_1.pdf` (6 páginas, PDF sin capa de texto; transcrito leyendo cada página).

---

## 1. Introducción

Este taller complementa la API básica del proyecto de publicaciones. Después de construir la versión principal, puedes profundizar en funcionalidades que hacen que una API sea más profesional, segura y escalable.

> **Objetivo**
> Implementar mejoras avanzadas en la API para manejar autenticación, paginación, likes por usuario, búsqueda y versionado de rutas.

---

## 2. Retos a resolver

### Reto 1 · Autenticación con Laravel Sanctum

Actualmente cualquier cliente puede crear, comentar y eliminar publicaciones sin identificar al usuario. La mejora consiste en proteger ciertos endpoints para que solo usuarios autenticados puedan realizarlos.

1. Instala **Laravel Sanctum** en el proyecto.
2. Configura el middleware de autenticación en la API.
3. Crea un sistema de registro e inicio de sesión para usuarios.
4. Protege rutas como:
   - `POST /api/posts`
   - `POST /api/posts/{id}/comments`
   - `DELETE /api/posts/{id}`
5. Asocia cada publicación o comentario con el usuario autenticado.

> **Pregunta guía:** ¿Cómo se puede diferenciar entre un usuario autenticado y un usuario anónimo en una API REST?

**Recomendación**

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

**Objetivo de aprendizaje:** Comprender qué significa proteger una API y cómo se usan tokens para validar identidad.

---

### Reto 2 · Paginación de publicaciones

Cuando la cantidad de publicaciones crezca, listar todo en una sola respuesta no es eficiente. Se requiere paginar los resultados para que el cliente obtenga bloques de datos pequeños.

1. Modifica el endpoint `GET /api/posts`.
2. Usa `->paginate(10)` en la consulta.
3. Devuelve información de paginación junto con los datos.
4. Prueba diferentes parámetros como:
   - `?page=1`
   - `?page=2`

```php
$posts = Post::with(['images', 'comments'])
    ->latest()
    ->paginate(10);
```

> **Pregunta guía:** ¿Qué ventaja tiene devolver resultados paginados en lugar de todos los registros a la vez?

**Objetivo de aprendizaje:** Entender cómo mejorar el rendimiento de una API y la experiencia de consumo desde frontends o apps móviles.

---

### Reto 3 · Sistema de likes más realista

En la versión básica, el like simplemente incrementa un contador. El siguiente paso es hacer que cada usuario solo pueda dar un like por publicación.

1. Crea una tabla pivote para guardar qué usuario dio like a qué post.
2. Relaciona **User** y **Post** con una relación muchos a muchos.
3. Evita que el mismo usuario dé like más de una vez.
4. Devuelve una respuesta con la cantidad real de likes.

**Ejemplo de diseño**

```php
Schema::create('post_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->timestamps();
});
```

> **Pregunta guía:** ¿Qué problema tiene un contador global si varios usuarios interactúan a la vez?

**Objetivo de aprendizaje:** Aprender a modelar relaciones más complejas y manejar restricciones de negocio reales.

---

### Reto 4 · Búsqueda por título

La API necesita una forma de buscar publicaciones por palabras clave, por ejemplo buscar el texto de un título o contenido.

1. Modifica el endpoint `GET /api/posts`.
2. Añade un query param llamado `search`.
3. Filtra publicaciones donde el título o contenido contenga el valor buscado.
4. Prueba consultas como:
   - `GET /api/posts?search=viaje`
   - `GET /api/posts?search=facultad`

```php
$posts = Post::with(['images', 'comments'])
    ->when($request->search, function ($query, $search) {
        $query->where('title', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%");
    })
    ->latest()
    ->get();
```

> **Pregunta guía:** ¿Cómo afecta la búsqueda al flujo de la API y qué diferencias hay entre filtrar en SQL y hacerlo en el backend manualmente?

**Objetivo de aprendizaje:** Comprender el uso de filtros y búsquedas dinámicas en endpoints REST.

---

### Reto 5 · Notificaciones por correo al comentar

Cuando alguien comenta en una publicación, el dueño del post puede recibir un correo informativo.

1. Configura el envío de correos con Laravel Mail.
2. Genera un Mailable con el asunto "Nuevo comentario".
3. Dispara el correo cuando se guarde un comentario.
4. Prueba con un usuario de prueba y un email válido.

```php
Mail::to($post->user->email)->send(new NewCommentMail($comment));
```

> **Pregunta guía:** ¿Qué ventajas tiene automatizar notificaciones por correo en una red social o plataforma de contenido?

**Objetivo de aprendizaje:** Introducción a Mailables, eventos y automatización de procesos dentro de Laravel.

---

### Reto 6 · Versionado de la API

Un sistema real casi siempre evoluciona con el tiempo. Por eso se recomienda poner una versión en la ruta de la API para evitar romper clientes existentes.

1. Crea una ruta como `/api/v1/posts`.
2. Mueve los endpoints actuales a un grupo versionado.
3. Configura rutas nuevas en un servicio o controlador específico.
4. Prueba que una versión vieja siga funcionando mientras la nueva se desarrolla.

```php
Route::prefix('v1')->group(function () {
    Route::get('/posts', [PostController::class, 'index']);
});
```

> **Pregunta guía:** ¿Por qué es importante versionar una API cuando se agregan cambios significativos?

**Objetivo de aprendizaje:** Comprender cómo mantener compatibilidad en sistemas de producción.

---

### Reto 7 · Documentación automática con Swagger o Scribe

Las APIs bien diseñadas deben documentarse para que otros desarrolladores sepan cómo usarlas.

1. Instala una librería de documentación de APIs.
2. Documenta cada ruta, método, body y respuesta.
3. Genera una página donde los clientes consulten la API.

Herramientas recomendadas:

- **Swagger**
- **Scribe**

> **Pregunta guía:** ¿Por qué es más fácil mantener una API cuando la documentación está presente?

**Objetivo de aprendizaje:** Valorar la calidad de la documentación como parte esencial del desarrollo backend.

---

## Resumen de retos

| Reto | Nivel | Tiempo estimado |
|---|---|---|
| Autenticación con Sanctum | Avanzado | 2-3 sesiones |
| Paginación | Medio | 1 sesión |
| Likes por usuario | Avanzado | 2 sesiones |
| Búsqueda por título | Medio | 1 sesión |
| Notificaciones por email | Avanzado | 2 sesiones |
| Versionado | Medio | 1 sesión |
| Documentación Swagger/Scribe | Medio | 1-2 sesiones |

*🎓 ¡Los retos avanzados son la mejor forma de crecer como desarrollador backend!*
