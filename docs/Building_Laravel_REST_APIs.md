# De Laravel + Blade a API REST: El Salto al Backend Moderno

**Masterclass preparatoria para la construcción de una API tipo Facebook (Laravel 12+)**
Archivo origen: `Building_Laravel_REST_APIs.pptx` (15 diapositivas)

**Objetivos de la sesión:** Comprender REST · Dominar HTTP · Implementar en Laravel · Probar en Postman

> **Guía del docente (5 min).**
> **Premisa:** Hasta hoy, Laravel construía y pintaba la casa (Blade). Desde hoy, Laravel solo entrega los ladrillos (JSON) y dejamos que otros pinten la casa.
> **Pregunta de interacción:** ¿Cuántos de aquí han consumido una API pública antes para ver el clima, mapas o películas?

---

## Índice

1. [Evolución de la arquitectura: Blade vs. JSON](#2-evolución-de-la-arquitectura-blade-vs-json)
2. [¿Qué es REST?](#3-qué-es-rest-hablando-el-idioma-de-la-web)
3. [Estructura de un mensaje HTTP](#4-estructura-de-un-mensaje-http-la-conversación)
4. [Métodos HTTP](#5-métodos-http-los-verbos-de-la-api)
5. [Content-Type](#6-content-type-cómo-enviamos-los-datos)
6. [Códigos de estado (éxito y errores)](#7-el-diccionario-del-servidor-éxito-y-errores-12)
7. [Arquitectura de una petición en Laravel](#9-arquitectura-de-una-petición-en-laravel)
8. [Pasos 1 a 3: rutas, modelos y controlador](#10-paso-1-y-2-rutas-y-modelos-el-mapa-y-los-cimientos)
9. [Postman](#12-postman-tu-laboratorio-de-pruebas)
10. [El desafío y el checklist](#14-el-desafío-api-tipo-facebook)

---

## 2. Evolución de la Arquitectura: Blade vs. JSON

| | **Arquitectura monolítica (lo que conoces)** | **API REST (desacoplado)** |
|---|---|---|
| Flujo | Navegador → Ruta `web.php` → Controlador → Vista `.blade.php` → HTML renderizado | Servidor Laravel (API) → `{JSON}` → App iOS/Android, Navegador Web (React/Vue), Smartwatch |
| Idea | El servidor procesa la lógica **y** renderiza la interfaz. Estrecho acoplamiento. | Interfaz de programación. El servidor solo expone **recursos**; el cliente decide cómo dibujarlos. |

> **Guía del docente (8 min).** Enfatiza que el backend ahora es agnóstico: al servidor no le importa el dispositivo cliente, solo entrega datos.
> **Pregunta:** Si quiero lanzar una app nativa en iOS y Android mañana, ¿por qué un monolito con Blade me daría problemas?

---

## 3. ¿Qué es REST? Hablando el idioma de la Web

- **REST:** estilo arquitectónico basado en los estándares nativos de la web.
- **Recurso (el sustantivo):** cualquier entidad física o lógica de tu base de datos (Post, User, Comment).
- **Endpoint:** la URL específica de contacto para interactuar con ese recurso.

Ejemplo de endpoints sobre el recurso `{ Post }`:

```
/api/posts
/api/users
/api/comments
```

> **El principio clave**
> - La **URL** identifica **QUÉ** quieres (el recurso / sustantivo).
> - El **método HTTP** identifica **QUÉ HACER** con él (la acción / verbo).

> **Guía del docente (7 min).** Aclara que REST no es un software, es un conjunto de reglas. La regla más violada es poner verbos en las URLs (ej. `/api/obtener-posts`).
> **Pregunta:** Usando esta lógica estricta, ¿cuál sería un endpoint correcto en REST para ver los comentarios específicos del post con ID 5?

---

## 4. Estructura de un Mensaje HTTP (La Conversación)

**Request (cliente → servidor)**

```http
POST /api/posts HTTP/1.1          ← Start Line (método + path)

Accept: application/json          ← Headers (metadatos)
Authorization: Bearer xyz...

{                                 ← Body (datos enviados)
  "title": "Hola Mundo"
}
```

**Response (servidor → cliente)**

```http
HTTP/1.1 201 Created              ← Status Line (código de resultado)

Content-Type: application/json    ← Headers (metadatos)

{                                 ← Body (datos devueltos)
  "id": 1,
  "title": "Hola Mundo"
}
```

> **Guía del docente (10 min).** Usa la analogía de una carta postal: el *Path* es la dirección, los *Headers* son los sellos/prioridad, el *Body* es la carta adentro, y el *Status Code* es el acuse de recibo.
> **Pregunta:** Si envío una petición GET para leer datos, ¿tiene sentido semántico incluir un enorme Body con información?

---

## 5. Métodos HTTP: Los Verbos de la API

| Método | Acción principal | Ejemplo en taller Facebook |
|---|---|---|
| `GET` | Leer o consultar información. | Listar publicaciones y consultar un post. |
| `POST` | Crear un nuevo recurso o enviar datos. | Crear un post, comentar y dar like. |
| `PUT` | Actualizar completamente un recurso. | Reemplaza toda la información de un registro. |
| `PATCH` | Actualizar solo algunos campos. | Modifica parcialmente un recurso. |
| `DELETE` | Eliminar un recurso. | Eliminar un post. |

> **Guía del docente (5 min).** Explica por qué "Dar Like" es un POST en nuestro diseño (estamos creando un nuevo registro de interacción, no solo actualizando un número).
> **Pregunta:** Si quiero actualizar solo mi foto de perfil, pero mantener mi nombre intacto, ¿qué método es semánticamente más correcto usar?

---

## 6. Content-Type: ¿Cómo enviamos los datos?

### `application/json`

```json
{
  "author": "Carlos",
  "content": "¡Brillante!"
}
```

- El estándar de oro. Ligero y basado en texto.
- Ideal para strings, números y booleanos.
- Exige el header HTTP: `Accept: application/json`.

### `multipart/form-data`

Formulario con campos `title`, `content` y un archivo adjunto (ej. `vacaciones.jpg`).

- El contenedor pesado. Divide el payload por *boundaries*.
- Indispensable cuando la petición incluye archivos físicos (imágenes, PDFs).
- No se puede enviar un archivo binario dentro de un JSON puro.

> **Guía del docente (5 min).** **Advierte con firmeza:** este es el error #1 al usar Postman. Intentar enviar un archivo físico escribiendo su ruta local en un JSON raw nunca funcionará.
> **Pregunta:** ¿Qué pasaría si intentamos enviar una foto de 5 MB codificada directamente como texto dentro de un archivo JSON?

---

## 7. El Diccionario del Servidor: Éxito y Errores (1/2)

### Respuestas exitosas (2xx)

| Código | Nombre | Significado |
|---|---|---|
| **200** | OK | La operación fue exitosa y se devuelve información (ej. listar posts). |
| **201** | Created | El recurso fue creado correctamente en la base de datos. |
| **204** | No Content | Operación exitosa, pero no hay cuerpo de respuesta (ej. eliminar un post). |

### Errores de petición (4xx)

| Código | Nombre | Significado |
|---|---|---|
| **400** | Bad Request | La solicitud enviada es incorrecta o falta información sintáctica. |
| **422** | Unprocessable Entity | **Vital en Laravel:** los datos llegaron bien, pero no pasaron la validación (ej. título vacío, el archivo no es imagen). |

> **Guía del docente (7 min).** **Énfasis dramático:** devolver un JSON que dice `{'error': 'fallo'}` pero con un código HTTP 200 OK es una mala práctica severa. El código dicta cómo reacciona el cliente.
> **Pregunta:** Si creamos un Post exitosamente, ¿por qué es mejor devolver un 201 en lugar de un 200?

---

## 8. El Diccionario del Servidor: Seguridad y Servidor (2/2)

| Código | Nombre | Significado |
|---|---|---|
| **401** | Unauthorized | No sé quién eres. Falta el token de sesión o API key. |
| **403** | Forbidden | Sé quién eres, pero no tienes permiso (ej. borrar el post de otro usuario). |
| **404** | Not Found | La URL o el recurso solicitado no existe. |
| **405** | Method Not Allowed | La ruta existe, pero usaste el verbo equivocado (ej. GET a una ruta POST). |
| **409** | Conflict | Conflicto de estado (ej. dar like a algo que ya tiene tu like). |
| **429** | Too Many Requests | Límite de peticiones excedido (anti-DDoS). |
| **500** | Internal Server Error | Excepción fatal en el código, error de sintaxis, o base de datos caída. |

> **Guía del docente (8 min).** Explica con humor: el 500 significa que el problema eres tú, no el cliente. Diferencia claramente entre el 401 (identidad) y el 403 (permisos).
> **Pregunta:** Si envío una petición POST a `/api/posts/1` (configurada solo para GET), ¿qué código me devuelve Laravel?

---

## 9. Arquitectura de una Petición en Laravel

```
Cliente (Postman) → Ruta (api.php) → Controller (PHP) → Modelo (Eloquent) → Base de Datos
                                          │
                                          ▼
                                  Respuesta JSON (200, 201, 404)
```

- Laravel añade automáticamente el prefijo `/api` al enrutador.
- **Aplicación stateless:** no hay middleware CSRF por defecto.
- La función `response()->json()` transforma "mágicamente" las colecciones de Eloquent a texto puro.

> **Guía del docente (7 min).** Aquí conectamos la teoría con Laravel. Recuerda que en `web.php` las rutas tienen protección de sesión y Blade, pero en `api.php` trabajamos limpios, sin estado.
> **Pregunta:** ¿Por qué no necesitamos verificar el token CSRF en las peticiones que llegan a `api.php`?

---

## 10. Paso 1 y 2: Rutas y Modelos (El Mapa y Los Cimientos)

**El Mapa: `routes/api.php`**

```php
Route::get('/posts', [PostController::class, 'index']);
Route::post('/posts/{id}/like', [PostController::class, 'like']);
```

`{id}` es un **Path Parameter**.

**Los Cimientos: `App\Models\Post`**

```php
public function images() {
    return $this->hasMany(PostImage::class);
}

public function comments() {
    return $this->hasMany(Comment::class);
}
```

Relación: un post tiene múltiples fotos y comentarios.

> **Guía del docente (5 min).** Muestra cómo la ruta del `like` incluye el parámetro `{id}` en la URL, cumpliendo el principio REST de identificar el recurso.
> **Pregunta:** Si eliminamos el modelo Post, ¿qué debería pasar con sus PostImages asociados en la base de datos?

---

## 11. Paso 3: El Controlador (Validar, Crear y Responder)

```php
public function store(Request $request) {
    $request->validate([
        'title'    => 'required|string',
        'images.*' => 'image|mimes:jpeg,png'
    ]);

    $post = Post::create([...]);

    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $file) {
            $path = $file->store('posts', 'public');
            // Crear PostImage...
        }
    }

    return response()->json($post->load('images'), 201);
}
```

Anotaciones de la diapositiva:

- **`validate`:** si falla, aborta la ejecución y retorna un JSON 422 automáticamente. *¡Magia sin ifs!*
- **`store('posts', 'public')`:** guarda la imagen física. Requisito vital: haber ejecutado `php artisan storage:link`.
- **`response()->json(..., 201)`:** convierte el objeto Eloquent en JSON e inyecta el estado 201 Created.

> **Guía del docente (8 min).** Este es el núcleo del taller. En una API, si la validación falla, Laravel no redirecciona atrás como en Blade, lanza una excepción JSON.
> **Pregunta:** ¿Qué error HTTP recibiríamos si un usuario intenta subir un PDF en el campo de imagen?

---

## 12. Postman: Tu Laboratorio de Pruebas

- **Colecciones:** tu espacio ordenado. Guarda todas las peticiones aquí para exportarlas.
- **Selector y URL:** obligatorio usar la variable `{{base_url}}` en lugar de `localhost`.
- **Pestaña Headers:** añadir siempre `Accept: application/json`.
- **Pestaña Body:** donde inyectamos los datos del POST o PUT.

> **Guía del docente (8 min).** Postman es un entorno de validación profesional. Obliga a los estudiantes a configurar `{{base_url}}` = `http://127.0.0.1:8000` desde el minuto uno.
> **Pregunta:** Si no configuramos el header `Accept: application/json`, ¿qué intentará devolvernos Laravel si hay un error de validación?

---

## 13. Enviando Datos en Postman (JSON vs. Archivos)

| | **Comentar / solo texto** | **Crear post / texto + fotos** |
|---|---|---|
| Pestaña Body | **Body → raw → JSON** | **Body → form-data** |
| Contenido | `{ "author": "Carlos", "content": "¡Qué buenas fotos!" }` | `title` (Text) = "Mi viaje" · `images[]` (**File**) = archivo(s) |
| Uso | Envíos simples sin archivos adjuntos. | El array de imágenes exige corchetes `[]` en la key. **¡No olvides cambiar el tipo de Text a File en el menú oculto!** |

> **Guía del docente (8 min).** Este es el punto de fallo #1 del taller. Enséñales físicamente dónde está el botón minúsculo en Postman para cambiar el input a 'File'.
> **Pregunta:** ¿Por qué no usamos raw JSON para enviar las fotos directamente?

---

## 14. El Desafío: API Tipo Facebook

### Modelo de datos

```
posts (id, title, content, likes_count, timestamps)
   │ 1
   ├──── N ──▶ post_images (id, post_id, image_path, timestamps)
   └──── N ──▶ comments    (id, post_id, author, content, timestamps)
```

### Endpoints a construir

| Método | Ruta | Descripción |
|---|---|---|
| `POST` | `/api/posts` | Crear publicación con fotos (multipart) |
| `GET` | `/api/posts` | Listar el feed completo |
| `POST` | `/api/posts/{id}/comments` | Agregar comentario |
| `POST` | `/api/posts/{id}/like` | Incrementar contador |
| `DELETE` | `/api/posts/{id}` | Borrar publicación en cascada |

> **Guía del docente (7 min).** Recomienda el orden lógico: 1. Modelos/Migraciones, 2. Rutas/Controladores, 3. Pruebas secuenciales (Crear → Leer → Comentar → Borrar).
> **Pregunta:** Al eliminar un post, ¿cómo evitamos que las imágenes y comentarios queden huérfanos en la base de datos?

---

## 15. Checklist: Dejarlo Funcionando

- [ ] Servidor local encendido en terminal (`php artisan serve`).
- [ ] Base de datos configurada y migraciones ejecutadas.
- [ ] Enlace simbólico de almacenamiento creado (`php artisan storage:link`).
- [ ] Colección de Postman creada y variable `{{base_url}}` definida.
- [ ] **Regla de oro:** commits progresivos documentados.

```bash
git commit -m 'feat: implementar creación de publicaciones con imágenes'
```

*Evidencia de avance incremental para la evaluación.*

> **Guía del docente (5 min).** Repasa que en la evaluación importa tanto el código como el historial de Git y la colección de Postman exportada. ¡Es hora del código!
> **Pregunta final:** ¿Quién está listo para romper el servidor local y arreglarlo de nuevo?

---

*Tiempo total estimado según las guías del docente: 5 + 8 + 7 + 10 + 5 + 5 + 7 + 8 + 7 + 5 + 8 + 8 + 8 + 7 + 5 = 103 min.*
