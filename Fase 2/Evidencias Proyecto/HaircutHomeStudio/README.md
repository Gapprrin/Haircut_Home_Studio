# Haircut Home Studio - Laravel

Migración del proyecto PHP ubicado en `../haircut` a Laravel 12. El proyecto anterior permanece intacto como respaldo y referencia.

## Estado de la migración

Se trasladaron:

- Portada, collage, productos y reseñas.
- Inicio de sesión, registro, cierre de sesión y roles.
- Reservas, calendario, bloques horarios, fotos y cancelación.
- Regla de solapamiento entre la última hora de un color largo y un corte de una hora.
- Panel del peluquero, disponibilidad, solicitudes, agenda y reportes CSV/PDF.
- Administración de categorías, servicios y productos.
- CSS, imágenes, iconos y comportamiento JavaScript del proyecto original.
- Esquema SQL y datos iniciales mediante migraciones y seeders.

## Requisitos

- PHP 8.2 o superior. En este equipo: `C:\xampp\php\php.exe`.
- Node.js y npm. En este equipo: `C:\Program Files\nodejs\npm.cmd`.
- SQLite, incluido con la instalación actual de PHP, o MySQL de XAMPP.

## Primera ejecución

Desde esta carpeta, en PowerShell:

```powershell
& 'C:\xampp\php\php.exe' artisan migrate:fresh --seed
& 'C:\xampp\php\php.exe' artisan storage:link
$env:Path = 'C:\Program Files\nodejs;' + $env:Path
& 'C:\Program Files\nodejs\npm.cmd' install
& 'C:\Program Files\nodejs\npm.cmd' run build
& 'C:\xampp\php\php.exe' artisan serve
```

La aplicación queda disponible normalmente en `http://127.0.0.1:8000`.

No es necesario ejecutar `storage:link` otra vez si responde que el enlace ya existe.

## Base de datos

Por defecto, `.env` usa `database/database.sqlite`. Esto permite trabajar sin tocar la base MySQL del proyecto PHP.

Para usar MySQL/XAMPP, primero crea una base nueva, por ejemplo `haircut_studio_laravel`, y cambia `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=haircut_studio_laravel
DB_USERNAME=root
DB_PASSWORD=
```

Después ejecuta:

```powershell
& 'C:\xampp\php\php.exe' artisan migrate:fresh --seed
```

> No ejecutes `migrate:fresh` apuntando a la base original `haircut_studio`: ese comando elimina todas las tablas antes de reconstruirlas. El seeder replica los datos versionados en `haircut/database/haircut_studio.sql`, pero no puede recuperar cambios locales que no estén en ese archivo.

## Cuentas de demostración

| Rol | Usuario | Contraseña |
|---|---|---|
| Administrador | `admin` o `admin@admin.cl` | `admin123` |
| Peluquero | `ricardo` o `ricardo@me.com` | `kako123` |
| Cliente | `ana` o `ana@haircut.cl` | `ana123` |

Los hashes bcrypt originales se conservaron, por lo que estas credenciales siguen siendo compatibles.

## Dónde quedó cada cosa

| Antes, PHP | Ahora, Laravel |
|---|---|
| `index.php` | `app/Http/Controllers/HomeController.php` y `resources/views/home.blade.php` |
| `auth/login.php` | `app/Http/Controllers/AuthController.php` y `resources/views/auth/login.blade.php` |
| `usuario/*.php` | `app/Http/Controllers/ReservationController.php` y `resources/views/reservas/` |
| `admin/servicios.php` | `app/Http/Controllers/Admin/ServiceController.php` y `resources/views/admin/servicios/` |
| `admin/catalogo.php` | `app/Http/Controllers/Admin/CatalogController.php` y `resources/views/admin/catalogo/` |
| `admin/disponibilidad.php` | `app/Http/Controllers/Admin/AvailabilityController.php` y `resources/views/admin/disponibilidad.blade.php` |
| `admin/solicitudes.php` | `app/Http/Controllers/Admin/SolicitudController.php` y `resources/views/admin/solicitudes.blade.php` |
| `admin/horas.php` | `app/Http/Controllers/Admin/HoursController.php` y `resources/views/admin/horas.blade.php` |
| `admin/dashboard.php` | `app/Http/Controllers/Admin/DashboardController.php` y `resources/views/admin/dashboard.blade.php` |
| `admin/reporte-export.php` | `app/Http/Controllers/Admin/ReportController.php` |
| `includes/funciones.php` | Modelos de `app/Models/` y servicios de `app/Services/` |
| `includes/header.php` y `footer.php` | `resources/views/layouts/app.blade.php` |
| `style.css` | `resources/css/app.css` |
| JavaScript incrustado | `resources/js/app.js` |
| `img/` | `public/img/` |
| `uploads/fotos/` | `storage/app/public/fotos/`, visible mediante `public/storage` |
| `database/haircut_studio.sql` | `database/migrations/` y `database/seeders/DatabaseSeeder.php` |

Las rutas HTTP están centralizadas en `routes/web.php`. `app/Http/Middleware/EnsureRole.php` restringe cada panel a `cliente`, `admin` o `peluquero`.

## Cómo trabajar el proyecto

### Cambiar contenido o diseño

- Edita Blade en `resources/views/` para cambiar HTML o textos.
- Edita `resources/css/app.css` para estilos.
- Edita `resources/js/app.js` para interacciones.
- Durante desarrollo ejecuta `npm run dev`; para una compilación final usa `npm run build`.

### Cambiar lógica de reservas

- `app/Services/AvailabilityService.php`: días, meses visibles, horarios, duración y solapamientos.
- `app/Http/Controllers/ReservationController.php`: flujo del cliente y creación de reservas.
- `app/Services/ReservationWorkflowService.php`: transición automática a `realizada`.
- `app/Models/Reserva.php`: relaciones y regla de cancelación hasta diez minutos antes.

### Cambiar tablas o datos iniciales

- Crea una migración nueva; no edites una migración ya aplicada en un ambiente compartido.
- Usa `database/seeders/DatabaseSeeder.php` solo para datos iniciales o demostrativos.
- En desarrollo puedes reconstruir SQLite con `artisan migrate:fresh --seed`.

### Archivos subidos

Laravel guarda imágenes nuevas en `storage/app/public/fotos`. En las vistas se accede a ellas mediante `/storage/fotos/...`. El enlace se crea con `artisan storage:link`.

## Comandos habituales

```powershell
# Ejecutar pruebas
& 'C:\xampp\php\php.exe' artisan test

# Formatear PHP
& 'C:\xampp\php\php.exe' vendor\bin\pint

# Limpiar cachés después de cambiar configuración o vistas
& 'C:\xampp\php\php.exe' artisan optimize:clear

# Compilar CSS y JavaScript
$env:Path = 'C:\Program Files\nodejs;' + $env:Path
& 'C:\Program Files\nodejs\npm.cmd' run build
```

Las pruebas cubren esquema y relaciones, disponibilidad, solapamientos, autenticación con hashes antiguos, separación de roles, reservas duplicadas, confirmación de solicitudes, renderizado de páginas y exportación CSV/PDF.