# Taller 3 · Arquitectura de Despliegue y Cloud Databases

## Contenerización con Docker y Despliegue con PostgreSQL en Render

*Docker Engine · Docker Compose · PostgreSQL Cloud · Variables de Entorno · Migraciones Automáticas*

| | |
|---|---|
| **Docente** | Fabian Enrique Suarez Carvajal |
| **Institución** | Universidad Autónoma de Bucaramanga |
| **Curso** | Arquitectura y Desarrollo Backend |

Archivo origen: `Taller_Despliegue_API_REST.pdf` (11 páginas escaneadas como imagen; transcrito leyendo cada página). Las líneas de código que el PDF muestra cortadas por la barra de desplazamiento se marcan con `[…]`.

---

## 1. Introducción y Objetivos Detallados

En las fases previas de este curso diseñaste, estructuraste y probaste localmente una API RESTful de publicaciones inspirada en redes sociales utilizando Laravel. Aprendiste a manejar migraciones, relaciones Eloquent complejas, subida de múltiples archivos y validaciones avanzadas. Sin embargo, en la industria del desarrollo de software profesional, un sistema no se considera completo hasta que está ejecutándose en un entorno de producción accesible a través de internet.

Uno de los retos más grandes al desplegar aplicaciones es garantizar que el entorno de desarrollo local (tu computadora) sea idéntico al entorno de producción (el servidor en la nube). Para solucionar esto, utilizaremos **Docker**, una tecnología que empaqueta nuestra aplicación junto con su propio servidor web (Nginx) y motor de lenguaje (PHP) en un contenedor aislado. Adicionalmente, desacoplaremos la persistencia de datos utilizando una base de datos relacional robusta y escalable en la nube: **PostgreSQL gestionada en Render**.

> **Objetivo General del Taller**
> Preparar el proyecto Laravel para operar con bases de datos relacionales robustas, contenerizar la aplicación utilizando un `Dockerfile` optimizado, configurar pruebas locales con **Docker Compose**, y ejecutar un despliegue completo en la nube conectando la API a una base de datos **PostgreSQL gratuita alojada en Render**.

### Competencias Clave a Desarrollar

- Comprender los fundamentos de la contenerización y el aislamiento de procesos mediante imágenes y contenedores Docker.
- Instalar y configurar el motor de Docker y Docker Compose en sistemas operativos Windows, macOS o Linux.
- Adaptar los archivos de configuración de Laravel para interactuar nativamente con controladores de bases de datos relacionales avanzados (PostgreSQL).
- Construir un `Dockerfile` de producción personalizado que instale extensiones críticas del sistema como `pdo_pgsql`.
- Diseñar scripts de automatización de arranque (entrypoints) para ejecutar migraciones en la nube de manera desatendida.
- Provisionar, configurar y conectar una base de datos PostgreSQL administrada en la nube mediante variables de entorno seguras.
- Validar y certificar el correcto funcionamiento de los endpoints públicos expuestos en producción utilizando Postman.

---

## 2. Arquitectura de Despliegue con PostgreSQL

Para entender cómo se comunicarán los componentes en producción, es vital analizar el siguiente diagrama de arquitectura:

```
┌──────────────────────────────────────────────────────────────┐
│                  NUBE DE PRODUCCIÓN (RENDER)                 │
│                                                              │
│   ┌──────────────────────────┐    ┌───────────────────────┐  │
│   │  Web Service (Contenedor)│    │  PostgreSQL Database  │  │
│   │  ┌────────────────────┐  │    │  (Servicio Gestionado)│  │
│   │  │ Nginx + PHP 8.2    │  │    │                       │  │
│   │  │ (FPM)              │  │    │                       │  │
│   │  │ Laravel API Backend│  │    │                       │  │
│   │  └────────────────────┘  │    │                       │  │
│   └────────────┬─────────────┘    └───────────▲───────────┘  │
│                │                              │              │
│                └──── Conexión Segura (TCP/SSL) ┘             │
│                                                              │
└──────────────────────────────▲───────────────────────────────┘
                               │ HTTPS (Puerto 443)
                     ┌─────────┴─────────┐
                     │ Cliente / Postman │
                     └───────────────────┘
```

A diferencia de usar SQLite (que almacena los datos en un archivo local dentro del contenedor y se perdería ante cada reinicio en la nube), el uso de **PostgreSQL en Render** garantiza que la información de usuarios, publicaciones, comentarios y likes persista de forma segura y profesional en un servidor de bases de datos dedicado.

---

## 3. Adaptación del Proyecto Laravel para PostgreSQL

Antes de empaquetar la aplicación en Docker, debemos verificar que Laravel esté preparado para comunicarse con PostgreSQL en lugar de SQLite o MySQL.

### Paso 1 · Revisar la configuración en `config/database.php`

Abre el archivo `config/database.php` en tu proyecto y asegúrate de que la conexión por defecto esté configurada para leerse desde las variables de entorno:

```php
'default' => env('DB_CONNECTION', 'pgsql'),
```

Verifica también que el bloque de configuración para `pgsql` contenga los parámetros estándar que utilizará Laravel para mapear las credenciales provenientes del servidor:

```php
'pgsql' => [
    'driver'         => 'pgsql',
    'url'            => env('DATABASE_URL'),
    'host'           => env('DB_HOST', '127.0.0.1'),
    'port'           => env('DB_PORT', '5432'),
    'database'       => env('DB_DATABASE', 'forge'),
    'username'       => env('DB_USERNAME', 'forge'),
    'password'       => env('DB_PASSWORD', ''),
    'charset'        => 'utf8',
    'prefix'         => '',
    'prefix_indexes' => true,
    'search_path'    => 'public',
    'sslmode'        => 'prefer',
],
```

---

## 4. Instalación de Docker y Docker Compose

Si ya posees Docker instalado y verificado, puedes avanzar a la Sección 5. Si necesitas instalarlo, sigue las directrices según tu sistema operativo:

### Guía de Instalación Rápida

- **Windows / macOS:** Descarga e instala **Docker Desktop** desde el sitio oficial [docker.com](https://www.docker.com). En Windows, asegúrate de tener activada la característica WSL 2 (Windows Subsystem for Linux).
- **Linux (Ubuntu / Debian):** Ejecuta el siguiente bloque de comandos en tu terminal para configurar los repositorios oficiales e instalar Docker Engine junto con Docker Compose:

```bash
sudo apt-get update
sudo apt-get install -y ca-certificates curl gnupg lsb-release
sudo mkdir -p /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc[…]
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg][…]
sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
```

> **🔍 Verificación de la instalación**
> Ejecuta estos comandos en tu consola para confirmar que todo funciona correctamente:
>
> ```bash
> docker --version
> docker compose version
> ```

---

## 5. Creación del Dockerfile y Script de Arranque

Para que nuestra imagen de PHP en Docker pueda comunicarse sin problemas con PostgreSQL, es indispensable instalar la extensión nativa `pdo_pgsql`.

### Paso 1 · Crear el `Dockerfile` optimizado

Crea un archivo llamado `Dockerfile` en la raíz de tu proyecto Laravel con el siguiente contenido:

```dockerfile
# Usar la imagen oficial de PHP con FPM
FROM php:8.2-fpm

# Instalar dependencias del sistema operativo y librerías de desarrollo para Postgre[…]
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip

# Limpiar caché de paquetes
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensiones de PHP necesarias para Laravel y PostgreSQL
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip

# Copiar el gestor de dependencias Composer desde su imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar el directorio de trabajo dentro del contenedor
WORKDIR /var/www

# Copiar el código fuente del proyecto
COPY . /var/www

# Instalar dependencias de producción de Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Asignar permisos correctos a las carpetas de almacenamiento y caché
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Copiar script de inicialización y otorgar permisos de ejecución
COPY docker-entrypoint.sh /var/www/docker-entrypoint.sh
RUN chmod +x /var/www/docker-entrypoint.sh

# Exponer el puerto predeterminado que utilizará Render
EXPOSE 10000

# Ejecutar el script de entrada al iniciar el contenedor
CMD ["/var/www/docker-entrypoint.sh"]
```

### Paso 2 · Crear el script de entrada `docker-entrypoint.sh`

Este archivo es fundamental para entornos en la nube, ya que permite limpiar cachés, generar enlaces simbólicos de almacenamiento y **ejecutar automáticamente las migraciones en la base de datos** cada vez que el contenedor arranca.

Crea el archivo `docker-entrypoint.sh` en la raíz del proyecto:

```sh
#!/bin/sh
set -e

echo "=== Limpiando y optimizando cachés de Laravel ==="
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== Ejecutando migraciones de base de datos en PostgreSQL ==="
php artisan migrate --force

echo "=== Creando enlace simbólico del storage público ==="
php artisan storage:link || true

echo "=== Iniciando servidor PHP integrado en puerto ${PORT:-10000} ==="
exec php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
```

### Paso 3 · Configurar `docker-compose.yml` para pruebas locales

Si deseas probar el funcionamiento completo conectando tu aplicación Laravel con una base de datos PostgreSQL de forma local antes de subirla a la nube, crea el archivo `docker-compose.yml` en la raíz:

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    image: facebook-api-app
    container_name: facebook_app
    restart: unless-stopped
    ports:
      - "8080:10000"
    environment:
      - PORT=10000
      - DB_CONNECTION=pgsql
      - DB_HOST=db
      - DB_PORT=5432
      - DB_DATABASE=facebook_db
      - DB_USERNAME=postgres
      - DB_PASSWORD=secret
    volumes:
      - ./:/var/www
    depends_on:
      - db
    networks:
      - net

  db:
    image: postgres:15-alpine
    container_name: facebook_db
    restart: unless-stopped
    environment:
      POSTGRES_DB: facebook_db
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: secret
    ports:
      - "5432:5432"
    volumes:
      - pgdata:/var/lib/postgresql/data
    networks:
      - net

volumes:
  pgdata:

networks:
  net:
    driver: bridge
```

Para levantar este entorno local de prueba, ejecuta en tu terminal:

```bash
docker compose up -d --build
```

---

## 6. Aprovisionamiento de PostgreSQL en Render

Ahora configuraremos la base de datos oficial en la plataforma gratuita de Render para que reciba las conexiones de producción.

1. Ingresa a tu cuenta en [Render Dashboard](https://dashboard.render.com).
2. Haz clic en el botón superior **New +**.
3. Selecciona la opción **PostgreSQL**.
4. Completa los datos del servicio de base de datos:
   - **Name:** `facebook-db-prod`
   - **Database:** `facebook_api` (puedes dejar el valor por defecto).
   - **User:** `fb_user` (puedes dejar el valor por defecto).
   - **Region:** Selecciona la región geográfica que prefieras (ej. *Oregon (US West)* o *Ohio (US East)*).
   - **Instance Type:** Selecciona el plan **Free**.
5. Haz clic en el botón inferior **Create Database**.

> **🔑 Datos de Conexión Vitales**
> Una vez creada la base de datos, Render te redirigirá a los detalles del servicio. Debes copiar y guardar temporalmente los siguientes datos de acceso, ya que los necesitarás en la siguiente sección:
>
> - **Internal Database URL** (para conexiones internas seguras dentro de Render).
> - **Host** (servidor de base de datos).
> - **Port** (generalmente `5432`).
> - **Database Name** (nombre de la BD).
> - **User** (nombre de usuario).
> - **Password** (contraseña segura generada por Render).

---

## 7. Despliegue del Web Service y Vinculación de Variables

Con la base de datos lista, procederemos a desplegar el código fuente de nuestra API vinculándolo con PostgreSQL.

1. Asegúrate de versionar y subir todos tus cambios más recientes a tu repositorio en GitHub:

   ```bash
   git add .
   git commit -m "config: configurar Dockerfile, entrypoint y soporte PostgreSQL"
   git push origin main
   ```

2. En el Dashboard de Render, haz clic en el botón **New +** y selecciona **Web Service**.
3. Selecciona la opción de conectar un repositorio Git y elige tu repositorio del proyecto.
4. Configura los parámetros del Web Service:
   - **Name:** `laravel-facebook-api-prod` (debe ser un nombre único globalmente).
   - **Environment:** `Docker` (Render detectará automáticamente el archivo Dockerfile).
   - **Region:** Selecciona **exactamente la misma región** donde creaste tu base de datos PostgreSQL en el paso anterior.
   - **Branch:** `main` (o la rama principal de tu repositorio).
   - **Instance Type:** Selecciona el plan **Free**.
5. Baja hasta la sección **Environment Variables** y haz clic en **Add Environment Variable** para registrar cada una de las siguientes claves de configuración:

| Key (Variable de Entorno) | Value (Valor a Configurar) | Descripción / Detalle |
|---|---|---|
| `APP_NAME` | FacebookAPI | Nombre identificativo del sistema. |
| `APP_ENV` | production | Indica a Laravel que opera en modo producción. |
| `APP_DEBUG` | false | Desactiva la traza de errores detallada por seguridad. |
| `APP_KEY` | `base64:...` | **Obligatorio:** Ejecuta localmente `php artisan key:generate --show` y pega aquí la llave generada. |
| `APP_URL` | `https://tu-servicio.onrender.com` | La URL pública que te asignará Render. |
| `DB_CONNECTION` | `pgsql` | Define que el motor de base de datos es PostgreSQL. |
| `DB_HOST` | Pega el valor del **Internal Host** provisto por Render en tu DB. | Dirección del servidor PostgreSQL en la nube de Render. |
| `DB_PORT` | `5432` | Puerto estándar de PostgreSQL. |
| `DB_DATABASE` | Nombre de la BD en Render (ej. `facebook_api`). | Nombre del esquema de base de datos asignado. |
| `DB_USERNAME` | Usuario de la BD en Render (ej. `fb_user`). | Credencial de usuario autorizado. |
| `DB_PASSWORD` | Contraseña provista por Render. | Clave de acceso secreta a la base de datos. |

6. Haz clic en el botón inferior **Create Web Service**.

> **⏳ Monitoreo del Despliegue**
> Render iniciará el proceso de compilación descargando la imagen de PHP, instalando las extensiones de PostgreSQL y levantando el servidor. Puedes revisar los registros (logs) en tiempo real desde la consola del navegador. Una vez finalice con éxito, el estado cambiará a un indicador verde de **Live**.

---

## 8. Pruebas y Validación Funcional en Postman

Para comprobar que tu API está respondiendo correctamente desde la nube y almacenando los datos en PostgreSQL:

1. Copia la URL pública HTTPS de tu Web Service en Render (por ejemplo: `https://laravel-facebook-api-prod.onrender.com`).
2. Abre **Postman** y actualiza la variable de entorno `{{base_url}}` con esta nueva dirección URL.
3. Realiza una petición de prueba utilizando el método **POST** a `{{base_url}}/api/posts` enviando texto e imágenes en el cuerpo (form-data).
4. Verifica que la respuesta devuelva un código HTTP **201 Created** y que al consultar mediante **GET** a `{{base_url}}/api/posts` obtengas el listado completo persistido en tu base de datos PostgreSQL en la nube.

> **💡 Comportamiento del Plan Gratuito**
> Recuerda que en el plan gratuito de Render, los servicios entran en modo suspensión tras 15 minutos sin recibir peticiones. La primera consulta que realices tras un periodo de inactividad puede tardar unos 30 a 50 segundos mientras el contenedor vuelve a encenderse; las peticiones posteriores responderán de inmediato.

---

## 9. Solución de Errores Frecuentes (Troubleshooting)

**❌ Error: PDOException: could not find driver (pgsql)**
- **Causa:** El contenedor Docker se construyó sin las extensiones necesarias para conectar con PostgreSQL.
- **Solución:** Asegúrate de que tu `Dockerfile` incluya la instrucción `RUN docker-php-ext-install pdo_pgsql pgsql` y realiza un nuevo deploy en Render.

**❌ Error de conexión a la Base de Datos (Connection Refused / Timed Out)**
- **Causa:** Las credenciales de `DB_HOST`, usuario o contraseña en las Environment Variables de Render no coinciden con los datos de tu PostgreSQL.
- **Solución:** Copia los datos exactos del panel de tu base de datos en Render y asegúrate de usar el *Internal Host* si la app y la base de datos están en la misma región de Render.

**❌ Error 500 | Server Error en Render**
- **Causa:** Falta configurar la variable `APP_KEY` en el panel de Render.
- **Solución:** Genera una clave con `php artisan key:generate --show` en tu entorno local y agrégala a las variables de entorno de Render.

---

## 10. Checklist Final · Entrega del Taller 3

| ✔ | Criterio de Evaluación | Evidencia Requerida |
|---|---|---|
| ☐ | Configuración del proyecto Laravel adaptada a PostgreSQL | Revisión del archivo `config/database.php` |
| ☐ | `Dockerfile` configurado correctamente con la extensión `pdo_pgsql` | Código del archivo Dockerfile en el repositorio |
| ☐ | Script `docker-entrypoint.sh` creado para automatizar migraciones | Script ejecutable en la raíz del proyecto |
| ☐ | Base de datos PostgreSQL creada y activa en Render (Plan Free) | Captura del Dashboard de Render |
| ☐ | Variables de entorno correctamente vinculadas en el Web Service de Render | Captura de la sección Environment en Render |
| ☐ | Despliegue exitoso (Estado Live) y migraciones ejecutadas automáticamente | URL pública de Render activa |
| ☐ | Pruebas funcionales de la API en producción usando Postman | Captura de pantalla de Postman con respuestas 200/201 OK |

*🎓 ¡Felicitaciones! Has completado con éxito el ciclo de desarrollo backend profesional y despliegue cloud con contenedores y bases de datos relacionales.*
