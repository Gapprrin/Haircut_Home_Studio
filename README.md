# Haircut_Home_Studio

Este repositorio cuenta con la evidencia del desarrollo del proyecto de Capstone. Cuenta con 3 fases; el desarrollo activo está en la **fase 2**.

Las instrucciones de ejecución del sistema están en el README dentro de la carpeta del proyecto Laravel (`HaircutHomeStudio`).

## Versiones del proyecto (nombres de carpetas)

En `Fase 2/Evidencias Proyecto/Evidencias de sistema/` las evidencias de sistema quedaron renombradas por número de versión:

| Carpeta | Descripción breve |
|---|---|
| `2.4_haircut` | Versión 2.4 (base PHP / evidencia previa) |
| `2.5_haircut_docker` | Versión 2.5 con Docker |
| `2.6_haircut` | Versión 2.6 |
| `2.7_haircut_docker` | **Versión actual** — Docker + Laravel |

La última actualización del proyecto está en:

`Fase 2/Evidencias Proyecto/Evidencias de sistema/2.7_haircut_docker`

El detalle técnico de cambios de la 2.7 está en el README de esa carpeta.

## Trabajo del 27 de octubre de 2026 (resumen)

En la versión **2.7** se avanzó sobre:

- Correos transaccionales con **Resend** (bienvenida, restablecer contraseña, confirmación/rechazo de hora), plantilla con colores de marca.
- Flujo de **restablecer contraseña** por correo.
- **Cerrar sesión** visible en el menú (también en PC).
- **Modales de confirmación** al eliminar o cancelar (en lugar del `confirm` del navegador).
- Hasta **2 imágenes de referencia** al reservar; el cliente y el peluquero las ven en modal.
- **Separación real por roles** (cliente / peluquero / admin): cada uno entra solo a sus pantallas.
- Botón **volver atrás** según el rol; admin y peluquero aterrizan en su pantalla de **bienvenida**.
- Ajustes de login (p. ej. entrar con nombre de usuario como `rosa`) y acceso del peluquero sin aviso engañoso al iniciar sesión.

Para el listado completo de cambios de la 2.7, ver  
`Fase 2/Evidencias Proyecto/Evidencias de sistema/2.7_haircut_docker/README.md`.
