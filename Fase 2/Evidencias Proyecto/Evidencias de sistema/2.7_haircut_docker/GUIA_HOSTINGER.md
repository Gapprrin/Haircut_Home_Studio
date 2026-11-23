# Publicar HairCut Home Studio 2.7 en Hostinger

Guía para **Ubuntu**, usuario **root**, dominio **haircuthomestudio.tech** y **base nueva**. No importa usuarios, reservas, contraseñas ni fotografías privadas del computador.

## Antes de empezar

- Usa solamente **2.7_haircut_docker** de Evidencias de sistema.
- El despliegue usa [compose.production.yaml](compose.production.yaml), no el Compose local.
- Necesitas la **IP pública del VPS**, visible en Hostinger. `srv214538` es el nombre del servidor, no reemplaza esa IP.
- Necesitas una clave privada válida de Gemini. Si alguna clave se compartió por chat o Git, revócala y crea otra.
- Los bloques **UBUNTU** se ejecutan en la terminal de Hostinger. Los bloques **WINDOWS** se ejecutan en PowerShell de tu computador.
- Ejecuta cada bloque en orden. Si falla, detente y revisa el error antes de continuar.
- No necesitas instalar PHP, Node, Apache, MySQL ni Redis directamente en Ubuntu: van dentro de Docker.

El servidor tendrá cuatro servicios: Laravel, MySQL, Redis y Caddy (HTTPS). Solo Caddy publica los puertos 80 y 443. Las fotos, disponibilidad, tokens de contraseña y demás archivos de la aplicación quedan en un volumen persistente.

## 1. HOSTINGER: apuntar el dominio al VPS

En la zona DNS que administra `haircuthomestudio.tech`:

| Tipo | Nombre | Valor |
|---|---|---|
| A | `@` | IP pública del VPS |

Reemplaza el registro A de aparcamiento si ya existe; no dejes dos IP distintas para `@`. Si hay un registro AAAA, debe apuntar a la IPv6 correcta del VPS; si no usarás IPv6, elimina solo ese AAAA del sitio. No borres registros MX, TXT ni los de correo.

Si los nameservers están fuera de Hostinger, modifica el DNS en ese proveedor. La propagación puede demorar. Esta guía publica el dominio **sin www**; no agregues www hasta configurar también ese nombre en Caddy.

Si activaste el firewall de red del VPS en Hostinger, permite TCP **22, 80 y 443**. No abras 3306, 6379, 9000 ni 8000.

## 2. UBUNTU: instalar Docker

Primero confirma que estás en Ubuntu y eres root:

```bash
whoami
cat /etc/os-release
```

Debes ver `root` y Ubuntu. Luego:

```bash
apt update
apt install -y ca-certificates curl openssl nano ufw dnsutils

install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
chmod a+r /etc/apt/keyrings/docker.asc

cat > /etc/apt/sources.list.d/docker.sources <<EOF
Types: deb
URIs: https://download.docker.com/linux/ubuntu
Suites: $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}")
Components: stable
Architectures: $(dpkg --print-architecture)
Signed-By: /etc/apt/keyrings/docker.asc
EOF

apt update
apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
systemctl enable --now docker
docker --version
docker compose version
```

No uses los comandos de desinstalación o limpieza de Docker en un servidor que ya tenga aplicaciones o datos.

### Firewall de Ubuntu

Estos comandos suponen que SSH usa el puerto 22, como en la instalación inicial. Si cambiaste ese puerto, permite primero el puerto correcto para no perder acceso.

```bash
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable
ufw status
```

Docker puede saltarse reglas de UFW para puertos publicados: por eso el perfil de producción no publica MySQL, Redis ni Laravel directamente.

## 3. WINDOWS: preparar el paquete de la versión 2.7

Si ya recibiste `haircut-2.7-hostinger.tar.gz` preparado para esta guía, puedes usarlo directamente. Para generarlo o regenerarlo con cambios nuevos:

```powershell
cd "C:\Users\CaNi517\OneDrive - HP Inc\Desktop\Portafolio de titulo\Fase 2\Haircut_Home_Studio\Fase 2\Evidencias Proyecto\Evidencias de sistema\2.7_haircut_docker"

powershell -NoProfile -ExecutionPolicy Bypass -File ".\deploy\package.ps1"
```

El paquete queda en **Descargas**. [El generador](deploy/package.ps1) copia únicamente código, imágenes públicas, migraciones y archivos de despliegue de la 2.7. Excluye dependencias, bases SQL/SQLite, archivos de configuración privados, fotos subidas, simulaciones, sesiones y cachés. Docker instalará las dependencias y compilará los estilos en el servidor.

No comprimas todo el repositorio y no subas la carpeta 2.5. No necesitas subir una exportación de phpMyAdmin: elegiste una base nueva.

### Subir el paquete

En **PowerShell de Windows**, no en la terminal de Hostinger:

```powershell
$VpsIp = Read-Host "IP publica del VPS, tomada desde Hostinger"
scp "$env:USERPROFILE\Downloads\haircut-2.7-hostinger.tar.gz" "root@${VpsIp}:/root/"
```

Comprueba la huella SSH con la información de Hostinger antes de aceptar la primera conexión. Escribe la contraseña del VPS cuando la solicite; no se muestra en pantalla. No la compartas por chat.

Si `scp` no está disponible, habilita el Cliente OpenSSH de Windows o usa un cliente SFTP: IP del VPS, puerto 22, usuario root, destino `/root/`.

## 4. UBUNTU: extraer e instalar el comando de administración

Vuelve a la terminal de Hostinger:

```bash
ls -lh /root/haircut-2.7-hostinger.tar.gz
mkdir -p /opt/haircut
tar -xzf /root/haircut-2.7-hostinger.tar.gz -C /opt/haircut
cd /opt/haircut
ls -la

sed -i 's/\r$//' deploy/hhs
install -m 0755 deploy/hhs /usr/local/bin/hhs
```

Debes tener [el perfil de producción](compose.production.yaml), [la plantilla de configuración](.env.production.example), [la carpeta deploy](deploy/) y la aplicación `HaircutHomeStudio/`.

`hhs` es un acceso directo a Docker Compose con los archivos correctos. Funciona también después de cerrar y volver a abrir la terminal.

## 5. UBUNTU: crear la configuración privada

Solo en la **primera instalación**, genera tres valores:

```bash
printf 'APP_KEY=base64:%s\n' "$(openssl rand -base64 32)"
printf 'DB_PASSWORD=%s\n' "$(openssl rand -hex 24)"
printf 'MYSQL_ROOT_PASSWORD=%s\n' "$(openssl rand -hex 24)"
```

Guárdalos en un gestor de contraseñas. No son las contraseñas de los usuarios de la web. No publiques los resultados.

Ahora:

```bash
cd /opt/haircut
umask 077
test -f .env.production || cp .env.production.example .env.production
chmod 600 .env.production
nano .env.production
```

En nano completa:

- `APP_DOMAIN=haircuthomestudio.tech`
- `APP_KEY=`: pega el valor generado, incluyendo `base64:`.
- `DB_PASSWORD=` y `MYSQL_ROOT_PASSWORD=`: pega los dos valores diferentes generados.
- `GEMINI_API_KEY=`: pega tu clave privada válida.
- `GEMINI_IMAGE_MODEL=`: conserva el modelo configurado si tu cuenta tiene acceso; puedes usar el identificador de imagen que ya tenías funcionando.
- Por ahora puedes dejar `MESSAGING_DRIVER=log`. **Esto no envía correos reales**; el paso 9 explica cómo activar Resend.

Para guardar: **Ctrl + O**, **Enter**, **Ctrl + X**.

No dejes valores vacíos para las primeras cinco variables. No pegues aquí el archivo privado del computador ni claves en el código. No ejecutes `docker compose config` sin `--quiet`, porque su salida puede incluir contraseñas y claves.

```bash
hhs config --quiet
```

Si no muestra errores, la configuración se pudo leer. Esto comprueba la configuración de Compose, no la validez de la clave de Gemini.

**En futuras actualizaciones conserva este archivo y sus valores.** No regeneres APP_KEY. Cambiar una contraseña de MySQL aquí no cambia automáticamente la contraseña dentro de una base ya creada.

## 6. UBUNTU: construir e iniciar la aplicación sin publicarla todavía

```bash
hhs up -d --build --wait --wait-timeout 300 hhs-app
hhs ps
```

La primera compilación descarga imágenes, instala dependencias y compila PHP/CSS/JavaScript. Puede tardar varios minutos. No cierres la terminal mientras compila.

Este comando inicia MySQL, Redis y Laravel, **pero no Caddy**, por lo que todavía no expone la web a Internet. El arranque ejecuta las migraciones, sin cargar cuentas ni reservas demo.

Los servicios deben quedar en ejecución y saludables. Si falla:

```bash
hhs logs --tail=100 hhs-app hhs-db redis
```

No continúes con errores de migración, conexión o permisos.

## 7. UBUNTU: crear tus cuentas y los catálogos

Solo después de que termine el paso anterior:

```bash
hhs exec -u www-data hhs-app php artisan studio:install
```

Responderás estas preguntas:

1. Nombre, correo y contraseña del administrador.
2. Confirmación de esa contraseña.
3. Nombre, correo y contraseña del peluquero.
4. Confirmación de esa contraseña.

Usa correos distintos y contraseñas de **al menos 12 caracteres**. Las contraseñas se escriben ocultas. No uses las credenciales de demostración ni `admin.imagen@haircut.cl`.

Se crean:

- Los roles y estados que necesita el esquema **2.7**.
- Los **17 servicios** del tarifario.
- Los **15 productos** del catálogo de esa versión.
- Tus dos cuentas propias.
- **Cero reservas y cero simulaciones de prueba.**

La disponibilidad usa los valores iniciales de la 2.7, no el archivo local de descansos. El peluquero podrá configurarla desde la web.

Este comando se niega a modificar una base con datos. No vuelvas a ejecutarlo en las actualizaciones y no uses `db:seed` general: ese seeder carga las cuentas demo.

Puedes comprobar los conteos iniciales, sin mostrar contraseñas:

```bash
hhs exec hhs-app php artisan tinker --execute="dump(DB::table('USUARIO')->count(), DB::table('RESERVA')->count(), DB::table('SERVICIO')->count());"
```

Inmediatamente después de instalar deben ser **2, 0 y 17**.

## 8. UBUNTU: publicar el sitio y obtener HTTPS

Verifica el DNS:

```bash
dig +short A haircuthomestudio.tech
dig +short AAAA haircuthomestudio.tech
```

La respuesta A debe ser la IP pública de este VPS. Un AAAA existente también debe corresponder al VPS. Si apuntan a otro lugar, corrige DNS y espera la propagación antes de seguir.

Cuando esté correcto:

```bash
hhs up -d caddy
hhs logs --tail=60 caddy
curl -I https://haircuthomestudio.tech/up
```

Caddy solicita y renueva el certificado automáticamente; no necesitas comprar un SSL ni instalar Certbot. El último comando debe responder **HTTP 200** con certificado válido. No uses `curl -k` para ocultar un error de certificado.

Abre:

**https://haircuthomestudio.tech**

Entra con el **correo completo** y contraseña que elegiste, no con los atajos `admin` o `ricardo` de desarrollo.

## 9. RESEND + UBUNTU: activar los correos reales

Este paso es necesario para demostrar bienvenida, recuperación de contraseña y notificaciones por correo.

1. En Resend, agrega el dominio `haircuthomestudio.tech`.
2. En su zona DNS agrega exactamente los registros de verificación, DKIM/SPF y los de recepción de rebotes que indique Resend. No sustituyas los registros de otro dominio ni elimines los MX del buzón principal.
3. Espera a que Resend marque el dominio como **Verified**.
4. Crea una API key con permiso de envío y cópiala únicamente al archivo privado del VPS.

En Ubuntu:

```bash
cd /opt/haircut
nano .env.production
```

Completa estas variables con tu clave real:

```dotenv
MESSAGING_DRIVER=http
MESSAGING_API_KEY=TU_CLAVE_PRIVADA_DE_RESEND
MESSAGING_FROM=reservas@haircuthomestudio.tech
```

El perfil elimina el desvío de mensajes a un destinatario de pruebas. No uses `onboarding@resend.dev` para enviar a todos tus clientes: necesitas el dominio verificado.

Guarda y recrea el contenedor para cargar las variables:

```bash
hhs up -d --force-recreate --wait --wait-timeout 300 hhs-app
```

Después registra una cuenta cliente con un correo tuyo y comprueba la bienvenida. Prueba también el restablecimiento de contraseña y revisa los eventos de envío en Resend.

**Con `MESSAGING_DRIVER=log` o una clave vacía no puedes dar por probado el envío real.** Revisa también spam y la cuota de Resend.

Para recibir respuestas en `reservas@haircuthomestudio.tech` necesitas crear o configurar ese buzón aparte; verificar el dominio para enviar no crea un buzón.

## 10. Pruebas antes de la presentación

Hazlas desde otro computador o el teléfono con datos móviles:

1. Abre el dominio y comprueba HTTPS, imágenes, estilos y menú móvil.
2. Administrador: revisa los 17 servicios y los 15 productos. Los precios se cargan desde el catálogo del proyecto: confírmalos con el negocio.
3. **Revisa las fotos de los productos.** La 2.7 tiene referencias a archivos `fotos/...` que no están incluidos en el almacenamiento público versionado. Este despliegue no copia archivos privados ni inventa esas fotos. Si faltan, súbelas desde Administración → Catálogo antes de presentar.
4. Peluquero: configura horario, meses visibles y días libres. Guarda y recarga para comprobar persistencia.
5. Cliente: regístrate, selecciona servicio, fecha y hora, adjunta referencias y confirma una reserva.
6. Peluquero: abre las fotos, confirma o rechaza y verifica el correo correspondiente.
7. Cliente: sube una fotografía propia con consentimiento al simulador y solicita una generación nueva. Las llamadas reales consumen la cuota de Gemini.
8. Comprueba el historial y la referencia IA al reservar.
9. Prueba cerrar sesión y que cada rol acceda solo a sus pantallas.
10. Prueba recuperar una contraseña por correo real.

El perfil deja Gemini en segundo plano: Supervisor inicia el worker automáticamente. También inicia el programador de Laravel para la limpieza de imágenes vencidas. No necesitas dejar `artisan serve`, un worker ni una terminal SSH abiertos.

Las cuotas normales siguen activas. No se publica la cuenta de imágenes ilimitadas.

## 11. Diagnóstico

```bash
hhs ps
hhs logs --tail=100 hhs-app
hhs logs --tail=100 caddy
hhs exec hhs-app php artisan migrate:status
hhs exec hhs-app php artisan schedule:list
```

| Problema | Qué revisar |
|---|---|
| No carga el dominio | DNS A/AAAA, puertos 80/443 en ambos firewalls, registros de Caddy. |
| Error de HTTPS | DNS correcto y dominio sin www; no desactives la validación del certificado. |
| Error 500 | Registros de Laravel; no actives APP_DEBUG públicamente. |
| Error 502 | Salud y registros de `hhs-app`; puede estar iniciando o migrando. |
| Simulación pendiente | Registros del worker, estado de Redis y configuración de la cola. |
| Gemini rechaza la generación | Clave, acceso al modelo, cuota/facturación y error de proveedor. |
| No llegan correos | Driver http, clave de Resend, dominio verificado, destinatario y límites. |
| No se guarda un archivo | Permisos del volumen y límite del archivo; no uses chmod 777. |

No compartas contraseñas, archivos privados, tokens de recuperación ni salidas completas de configuración al pedir ayuda.

## 12. Copia de seguridad antes de actualizar o presentar

Estos comandos hacen una pausa breve del sitio para obtener base y almacenamiento consistentes. La base queda ejecutándose para exportarla. Elige un momento sin reservas ni generaciones en curso:

```bash
BACKUP="/root/haircut-backup-$(date +%Y%m%d-%H%M%S)"
install -m 0700 -d "$BACKUP"

hhs stop caddy hhs-app

if hhs exec -T hhs-db sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" exec mysqldump --single-transaction --no-tablespaces -u"$MYSQL_USER" "$MYSQL_DATABASE"' > "$BACKUP/database.sql" \
  && hhs run --rm -T --no-deps --entrypoint tar hhs-app -czf - -C /app/storage . > "$BACKUP/storage.tar.gz"; then
    install -m 0600 /opt/haircut/.env.production "$BACKUP/env.production"
    chmod 600 "$BACKUP/database.sql" "$BACKUP/storage.tar.gz"
    echo "Respaldo creado en $BACKUP"
else
    echo "ERROR: respaldo incompleto. No actualices hasta resolverlo."
fi

hhs up -d --wait --wait-timeout 300 hhs-app caddy
```

Descarga esa carpeta de respaldo por SFTP a un lugar privado fuera del VPS. Contiene datos personales y secretos: no la subas a Git ni la dejes dentro de la web. Los respaldos semanales del proveedor no reemplazan comprobar que puedes recuperar base y fotos.

## 13. Actualizaciones posteriores

Antes de cada actualización, haz el respaldo anterior y conserva una copia del paquete que ya funcionaba.

1. Regenera el paquete desde la **2.7** actualizada y súbelo igual que en el paso 3.
2. No incluyas una configuración privada nueva ni una base demo.
3. En Ubuntu:

```bash
hhs stop caddy hhs-app
tar -xzf /root/haircut-2.7-hostinger.tar.gz -C /opt/haircut
hhs up -d --build --wait --wait-timeout 300 hhs-app caddy
curl -I https://haircuthomestudio.tech/up
```

Los volúmenes mantienen MySQL, las fotos, la disponibilidad, las colas y los certificados. El arranque aplica las migraciones pendientes sin volver a crear los usuarios.

No vuelvas a ejecutar `studio:install`. Si cambiaste solo una clave del archivo privado, basta recrear `hhs-app`; reconstruir la imagen no es necesario para ese cambio.

**Nunca ejecutes en producción:**

- `docker compose down -v` o `hhs down -v`.
- `docker volume prune`.
- `php artisan migrate:fresh`.
- `php artisan db:seed` general.
- Comandos para borrar la base o el almacenamiento y “empezar de cero”.

No cambies el nombre del proyecto Compose ni las opciones iniciales de MySQL en una instalación con datos sin preparar antes una migración.

## Alcance de las verificaciones

La preparación incluye comprobación de Compose, compilación del frontend e instalación con el esquema 2.7 en una base SQL aislada. El VPS, su DNS, el certificado y las claves de tus cuentas se verifican con los pasos anteriores: la guía no implica que alguien haya accedido automáticamente a Hostinger.

La suite heredada contiene pruebas del esquema anterior; para el instalador y HTTPS se añadió [ProductionDeploymentTest](HaircutHomeStudio/tests/Feature/ProductionDeploymentTest.php). Ese esquema usa un trigger MySQL y no se valida completo con SQLite en memoria sin adaptaciones. No ejecutes pruebas de reconstrucción de base apuntando a producción.

Fuente de instalación de Docker: [documentación oficial para Ubuntu](https://docs.docker.com/engine/install/ubuntu/).
