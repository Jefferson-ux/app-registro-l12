# Ejecutar el proyecto con Docker

Esta guía describe cómo iniciar la aplicación desde Windows con Docker Compose. Los comandos de Compose se ejecutan desde la carpeta `docker`.

## Requisitos

- Docker Desktop iniciado, con Docker Compose disponible.
- Node.js y npm para generar los archivos de Vite.
- Un archivo `.env` en la raíz del proyecto y otro `docker/.env` con las variables de Compose.

No compartas ni subas los archivos `.env` al repositorio.

## Preparación inicial

1. Si aún no tienes el `.env` de Laravel, desde la raíz del proyecto copia la plantilla y genera la clave:

   ```powershell
   Copy-Item .env.example .env
   composer install
   php artisan key:generate
   ```

2. Si `docker/.env` no existe, créalo desde la raíz del proyecto:

   ```powershell
   Copy-Item .env docker\.env
   ```

   Luego configura `docker/.env` con estas variables:

   ```dotenv
   APP_KEY=
   DB_DATABASE=app
   DB_USERNAME=app_user
   DB_PASSWORD=
   DB_ROOT_PASSWORD=
   MAIL_FROM_ADDRESS=
   SUPER_ADMIN_EMAIL=
   ```

   Usa contraseñas propias para `DB_PASSWORD` y `DB_ROOT_PASSWORD`; no es necesario que el usuario de la aplicación sea `root`. `MAIL_FROM_NAME` es opcional. La clave `APP_KEY` debe tener el mismo valor que la del `.env` raíz. No sobrescribas un `docker/.env` que ya contenga tu configuración.

3. Genera los archivos de frontend desde la raíz del proyecto. Docker no ejecuta Vite y `public/build` está ignorado por Git, por lo que este paso es necesario antes de iniciar la app:

   ```powershell
   npm install
   npm run build
   ```

## Iniciar la aplicación

Desde la raíz del proyecto:

```powershell
cd .\docker
docker compose up -d --build
docker compose ps
```

La primera ejecución puede tardar mientras se descargan imágenes y se construye la imagen de PHP. El servicio `app` espera a que MySQL esté saludable. Al iniciar, el entrypoint ejecuta la detección de paquetes, la actualización de Filament, crea el enlace de almacenamiento y aplica las migraciones (`php artisan migrate --force`). No hace falta ejecutar las migraciones por separado para el arranque normal.

Cuando los servicios estén arriba:

- Aplicación: <http://localhost:8000>
- Panel administrador: <http://localhost:8000/admin>
- Panel de la empresa: <http://localhost:8000/app>
- Interfaz de Mailpit: <http://localhost:8025>

El correo de la aplicación se envía al servicio `mailpit` dentro de la red de Docker, en el puerto `1025`.

## Base de datos

El Compose actual publica MySQL en el puerto `3306` del equipo. Para conectarte desde HeidiSQL, usa `127.0.0.1:3306` y los valores de base de datos, usuario y contraseña de `docker/.env`.

Si Laragon u otro servicio ya ocupa el puerto `3306`, cambia solo el puerto del equipo en `docker-compose.yml`, por ejemplo:

```yaml
ports:
  - "3307:3306"
```

En ese caso, HeidiSQL debe conectarse al puerto `3307`. La aplicación dentro de Docker debe seguir usando `DB_HOST=mysql` y `DB_PORT=3306`.

Los datos de MySQL persisten en el volumen `mysql_data`. Las variables de usuario y contraseña inicializan la base al crear el volumen por primera vez; cambiarlas después en `docker/.env` no cambia automáticamente las credenciales de una base ya inicializada.

## Comandos útiles

Ejecuta estos comandos desde `docker`:

```powershell
# Ver estado de los servicios
docker compose ps

# Seguir los logs de la aplicación
docker compose logs -f app

# Seguir los logs de MySQL
docker compose logs -f mysql

# Ejecutar un comando Artisan
docker compose exec app php artisan about

# Consultar el estado de las migraciones
docker compose exec app php artisan migrate:status

# Abrir una shell en el contenedor de PHP
docker compose exec app sh
```

Para detener los contenedores sin borrar datos:

```powershell
docker compose down
```

Para volver a iniciarlos sin reconstruir la imagen:

```powershell
docker compose up -d
```

`docker compose down -v` elimina también los volúmenes, incluida la base de datos y los archivos persistentes. Úsalo solo si quieres borrar esos datos.
