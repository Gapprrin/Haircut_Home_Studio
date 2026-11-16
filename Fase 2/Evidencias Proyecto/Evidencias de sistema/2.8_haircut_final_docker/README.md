# HairCut Home Studio (2.7)

App web de reservas y gestión para peluquería. Stack: **Laravel 12**, **MySQL**, **Redis** y **Docker**.

## Estructura

- `compose.yaml` — contenedores de app, base de datos y Redis
- `HaircutHomeStudio/` — código de la aplicación

Local: `http://localhost:8000`

## Arranque

```bash
docker compose up -d --build
```

Configuración en `.env` (no versionar secretos ni API keys).

## Esta versión

- Roles (cliente, peluquero, admin) con menús y rutas propias
- Reservas por pasos, disponibilidad y panel operativo
- Simulador de estilo con IA (cupos configurables)
- Correos de marca (bienvenida, restablecer clave, solicitudes)
- Seguridad: contraseñas fuertes, hash en BD, CSRF, cabeceras HTTP, validación de correo/nombre y avisos en español
- Navegación: “atrás” del sistema no deja al staff en zonas ajenas a su rol

Detalle de uso y desarrollo: `HaircutHomeStudio/README.md`.
