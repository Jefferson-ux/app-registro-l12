# 🏭 <u>Sistema de Registro de Asistencia - SaaS </u>

Plataforma SaaS multitenant para el control de asistencia y gestión de personal, construida con **Laravel 12** y **Filament 4**. Cada empresa (tenant) administra sus empleados, sucursales, horarios y registros de asistencia, mientras que el dueño de la plataforma gestiona empresas, planes y suscripciones.

---

## Tabla de contenidos

- [Características](#características)
- [Stack tecnológico](#stack-tecnológico)
- [Arquitectura](#arquitectura)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Configuración](#configuración)
- [API](#api)
- [Estructura de carpetas relevante](#estructura-de-carpetas-relevante)
- [Flujo de trabajo con Git](#flujo-de-trabajo-con-git)

---

## Características

- **Multitenancy con base de datos compartida**: todas las tablas de empresa llevan el campo `tenant_id`; la tabla `tenants` es la parte central.
- **Dos paneles Filament**:
    - **AdminPanel** (`/admin`): gestión de la plataforma (empresas, planes, suscripciones, usuarios, roles, auditoría, métricas SaaS como MRR, conversión de trial y churn).
    - **AppPanel** (`/app`): panel de la empresa cliente (empleados, sucursales, departamentos, cargos, turnos, feriados, asistencia e incidencias).
- **Roles y permisos por empresa** con Spatie Permission (modo _teams_ usando `tenant_id`).
- **Super Admin** sin tenant, con acceso total a la plataforma.
- **Registro de auditoría** de solo lectura.
- **Interfaz en español** con selector de idioma (ES/EN) en el menú de perfil.
- **API REST** autenticada con Laravel Sanctum.
- **Correo** con Laravel Mail (Mailpit en local).

## Stack tecnológico

| Capa          | Tecnología                             |
| ------------- | -------------------------------------- |
| Backend       | Laravel 12, PHP 8.2+                   |
| Paneles       | Filament 4                             |
| Permisos      | spatie/laravel-permission (teams)      |
| API           | Laravel Sanctum                        |
| Base de datos | MySQL / MariaDB                        |
| Entorno local | Laragon (Mailpit incluido)             |
| Traducciones  | laravel-lang/common (solo `es` y `en`) |

## Arquitectura

```
Landing (dashboard.blade.php)
        │
        ▼
   Login ──▸ super_admin ──▸ /admin  (AdminPanel)
        │
        └──▸ usuario de empresa ──▸ /app  (AppPanel)
```

- **Aislamiento por tenant**: el middleware `SetPermissionsTeamId` fija el tenant activo en Spatie en cada request (web y API), tomándolo de `users.tenant_id`.
- **Super Admin**: usuario con `tenant_id = null` y rol `super_admin`; un `Gate::before` en `AppServiceProvider` le otorga acceso total.
- **Restricciones por Resource**: las acciones que nunca deben existir (p. ej. editar o eliminar un `AuditLog`) se bloquean directamente en el Resource con `canCreate()`, `canEdit()`, `canDelete()`, etc.

### Modelos principales

`Tenant`, `User`, `Plan`, `Subscription`, `Branch`, `Department`, `Position`, `Employee`, `WorkSchedule`, `WorkScheduleDay`, `EmployeeSchedule`, `Holiday`, `AttendanceSession`, `AttendanceRecord`, `AttendanceIncident`, `Configuration`, `AuditLog`.

## Requisitos

- PHP >= 8.2 con extensiones habituales de Laravel
- Composer 2
- Node.js 18+ y npm
- MySQL 8 / MariaDB 10.6+
- Laragon (recomendado en Windows) o cualquier stack equivalente

## Instalación

```bash
# Clonar el repositorio
git clone https://github.com/Jefferson-ux/app-registro-l12.git

# Entrar al proyecto
cd app-registro-l12

# Instalar las dependencias propias de Laravel
composer install

# Copiar el archivo de entorno de ejmplo
cp .env.example .env

# Generar la "KEY" del proyecto
php artisan key:generate
```

Configura la base de datos en `.env` y luego:

```bash
# Ejecutar para obtener tablas y datos
php artisan migrate --seed

# Instalar dependencias de estilo (Opcional)
npm install
npm run build

# Ejecutar el servidor de
php artisan serve
```

> Para acelerar `composer install` en desarrollo, evita `--optimize-autoloader`; úsalo solo al desplegar.

## Configuración

Variables relevantes de `.env`:

```dotenv
APP_LOCALE=es
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=es_PE
APP_TIMEZONE=America/Lima

DB_CONNECTION=mysql
DB_DATABASE=nombre_de_tu_bd

MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025

# Solo desarrollo local
SUPER_ADMIN_EMAIL="admin@gmail.com"
```

Configuración de Spatie (`config/permission.php`):

```php
'teams' => true,
'team_foreign_key' => 'tenant_id',
```

> Este cambio debe hacerse **antes** de ejecutar la migración de Spatie; de lo contrario las tablas pivote no incluirán `tenant_id`.

## Estructura de carpetas relevante

```
app-registro-l12
  │
  ├── app/
  │   ├── Console/Commands/
  │   ├── Filament/
  │   │   ├── App/              # Panel de Cliente
  │   │   │   └── Resources/
  │   │   ├── Resources/        # Resources de cada panel
  │   │   └── /
  │   ├── Http/
  │   │   ├── Controllers/Api/  # AuthController, EmployeeController...
  │   │   ├── Middleware/       # SetPermissionsTeamId, SetLocale
  │   │   ├── Requests/
  │   │   └── Resources/
  │   ├── Models/
  │   ├── Policies/
  │   └── Providers/Filament/   # AdminPanelProvider, AppPanelProvider
  ├── database/
  │   ├── migrations/
  │   └── seeders/
  │       └── Production/       # Permisos, roles base y planes
  ├── lang/
  │   ├── es/                   # Traducciones propias
  │   ├── en/
  │   └── vendor/filament-panels/
  ├── public/css/dashboard.css  # Estilos de la landing
  └── resources/views/dashboard.blade.php
```

## Flujo de trabajo con Git

```
main        ← rama estable
developer   ← rama de integración
feature/*   ← ramas cortas por funcionalidad (se borran al ejecutar "merge")
```

1. Crear la rama desde `developer`: `git checkout developer && git checkout -b feature/nombre`
2. Mergear la feature a `developer` (local) y borrar la rama.
3. Abrir un Pull Request `developer` → `main`.

Recomendado: activar _branch protection_ en `main` para exigir PR.

## Licencia

Proyecto privado. Todos los derechos reservados.
