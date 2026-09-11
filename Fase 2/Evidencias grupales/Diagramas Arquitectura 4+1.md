# Diagramas de Arquitectura 4+1 - Haircut Home Studio

## 1. Proposito y estilo arquitectonico

Haircut Home Studio es una aplicacion web desarrollada en Laravel 12 y PHP. Su arquitectura principal es **MVC (Modelo - Vista - Controlador)**, ampliada con servicios para la logica de negocio, trabajos en cola para procesos lentos y una capa de infraestructura para persistencia, archivos y servicios externos.

El patron MVC organiza la aplicacion de esta forma:

- **Modelo:** entidades Eloquent y migraciones que representan los datos de usuarios, servicios, reservas, productos y generaciones de IA.
- **Vista:** plantillas Blade, CSS y JavaScript que presentan la interfaz web y las interacciones del cliente.
- **Controlador:** recibe las solicitudes HTTP, aplica autorizacion y coordinacion, y entrega una vista o respuesta.

La capa de servicios concentra reglas que no deben quedar en las vistas ni en los controladores. Por ejemplo, `AvailabilityService` calcula horarios y solapamientos, mientras que `AiQuotaService` controla los limites de uso del simulador.

El modelo 4+1 describe la arquitectura desde cuatro perspectivas tecnicas y una perspectiva de escenarios. Los diagramas Mermaid incluidos se pueden visualizar en VS Code o copiar a https://mermaid.live. Para disenar una version grafica, se pueden reproducir en Draw.io, Lucidchart, Figma o Canva usando los mismos actores, contenedores y flechas.

---

## 2. Vista logica

La vista logica muestra las responsabilidades principales y sus relaciones. El nucleo de la aplicacion usa MVC; los controladores delegan las reglas de dominio en servicios y los modelos se comunican con MySQL mediante Eloquent.

```mermaid
flowchart LR
    Client[Cliente autenticado]
    Admin[Administrador]
    Hairdresser[Peluquero]

    subgraph Laravel[Aplicacion Laravel 12]
        Routes[routes/web.php]
        Middleware[Auth y EnsureRole]

        subgraph Controllers[Controladores]
            Home[HomeController]
            Auth[AuthController]
            Reservation[ReservationController]
            AI[AiStudioController]
            AdminCtrl[Controladores Admin]
        end

        subgraph Services[Servicios de negocio]
            Availability[AvailabilityService]
            Workflow[ReservationWorkflowService]
            Quota[AiQuotaService]
            Prompt[AiPromptService]
            Media[MediaService y PhotoSanitizer]
        end

        subgraph Models[Modelos Eloquent]
            User[Usuario]
            Category[Categoria]
            Service[Servicio]
            Booking[Reserva]
            Product[Producto]
            Generation[AiGeneration]
        end

        subgraph Views[Vistas]
            Blade[Blade]
            Assets[CSS y JavaScript Vite]
        end
    end

    DB[(MySQL MariaDB XAMPP)]
    Storage[(Storage publico y privado)]
    Gemini[Google Gemini API]

    Client --> Routes
    Admin --> Routes
    Hairdresser --> Routes
    Routes --> Middleware --> Home
    Middleware --> Auth
    Middleware --> Reservation
    Middleware --> AI
    Middleware --> AdminCtrl
    Home --> Blade
    Auth --> Blade
    Reservation --> Blade
    AI --> Blade
    AdminCtrl --> Blade
    Blade <--> Assets

    Reservation --> Availability
    Reservation --> Workflow
    Reservation --> Media
    AI --> Quota
    AI --> Prompt
    AI --> Media
    Availability --> Booking
    Workflow --> Booking
    Prompt --> Service
    Quota --> Generation

    User --> DB
    Category --> DB
    Service --> DB
    Booking --> DB
    Product --> DB
    Generation --> DB
    Media --> Storage
    AI --> Gemini
```

### Elementos para redibujar

1. Dibuja tres actores: Cliente, Administrador y Peluquero.
2. Dibuja un contenedor grande llamado `Aplicacion Laravel 12`.
3. Dentro, separa Rutas/Middleware, Controladores, Servicios, Modelos y Vistas.
4. Fuera del contenedor coloca MySQL/MariaDB, almacenamiento de archivos y Google Gemini API.
5. Usa flechas desde actores hacia rutas, rutas hacia middleware/controladores, controladores hacia servicios/vistas y modelos hacia la base de datos.

---

## 3. Vista de procesos

La vista de procesos representa como se ejecutan los flujos importantes. La reserva se valida en el servidor; la generacion con IA puede pasar a una cola para no bloquear la solicitud web.

```mermaid
sequenceDiagram
    actor Client as Cliente
    participant Browser as Navegador
    participant Reservation as ReservationController
    participant Availability as AvailabilityService
    participant DB as MySQL/MariaDB

    Client->>Browser: Elige categoria y servicio
    Note over Browser: Categoria y tarjeta cambian localmente con JavaScript
    Client->>Browser: Pulsa Siguiente
    Browser->>Reservation: GET /reservar?serv=ID&paso=fecha
    Reservation->>Availability: Obtener calendario y slots disponibles
    Availability->>DB: Consultar reservas y disponibilidad
    DB-->>Availability: Datos actuales
    Availability-->>Reservation: Dias y horarios validos
    Reservation-->>Browser: Vista Blade paso Fecha y hora
    Client->>Browser: Confirma la reserva
    Browser->>Reservation: POST /reservar
    Reservation->>DB: Transaccion y bloqueo de reservas del dia
    Reservation->>Availability: Verificar nuevamente el horario
    Availability-->>Reservation: Horario libre o error
    Reservation->>DB: Crear reserva si es valida
    Reservation-->>Browser: Redireccion a Mis reservas
```

```mermaid
sequenceDiagram
    actor Client as Cliente
    participant Browser as Navegador
    participant AI as AiStudioController
    participant Quota as AiQuotaService
    participant Queue as Cola ai,default
    participant Job as GenerateHairPreview
    participant Gemini as Google Gemini API
    participant Storage as Storage privado
    participant DB as MySQL/MariaDB

    Client->>Browser: Sube foto y elige servicio
    Browser->>AI: POST /simulador
    AI->>Quota: Validar limites por usuario, IP y globales
    Quota->>DB: Consultar intentos y cuenta sin limite
    DB-->>Quota: Estado de cuota
    Quota-->>AI: Autorizar o rechazar
    AI->>Storage: Guardar foto sanitizada
    AI->>DB: Crear AiGeneration pendiente
    AI->>Queue: Encolar GenerateHairPreview
    AI-->>Browser: Mostrar estado de procesamiento
    Queue->>Job: Ejecutar trabajo
    Job->>Gemini: Solicitud con prompt controlado
    Gemini-->>Job: Imagen generada
    Job->>Storage: Guardar resultado privado
    Job->>DB: Marcar generacion completada
    Browser->>AI: Consultar estado periodicamente
    AI-->>Browser: Mostrar comparacion y opcion de reservar
```

### Decisiones de proceso relevantes

- El cliente puede cambiar categoria y servicio sin recargar la pagina. La recarga controlada ocurre al avanzar a fecha para calcular disponibilidad real del servicio elegido.
- La reserva se comprueba dos veces: al mostrar horarios y antes de crearla. La segunda comprobacion evita dobles reservas ante solicitudes simultaneas.
- Gemini no recibe prompts escritos libremente por el cliente. El servidor crea instrucciones desde el servicio seleccionado.
- Los archivos de IA son privados. La foto de referencia de una reserva se guarda en el disco publico de Laravel bajo `storage/app/public/fotos`.
- `Admin Test Imagen` omite cuotas de IA por fines de prueba, pero conserva las protecciones contra procesos duplicados.

---

## 4. Vista de desarrollo

La vista de desarrollo organiza el codigo fuente por modulos y muestra las dependencias principales. Esta es la vista mas util para que el equipo sepa donde realizar cada cambio.

```mermaid
flowchart TB
    subgraph Project[HaircutHomeStudio]
        Routes[routes/web.php]

        subgraph Http[app/Http]
            Controllers[Controllers]
            Middleware[Middleware/EnsureRole.php]
        end

        subgraph Domain[app]
            Models[Models]
            Services[Services]
            Jobs[Jobs/GenerateHairPreview.php]
            Console[Console/Commands]
            Contracts[Contracts]
        end

        subgraph Interface[resources]
            Blade[views/*.blade.php]
            JS[js/app.js]
            CSS[css/app.css]
        end

        subgraph Data[database]
            Migrations[migrations]
            Seeders[seeders/DatabaseSeeder.php]
        end

        Config[config/ai.php]
        Public[public/img y public/build]
        Tests[tests/Feature y tests/Unit]
    end

    Routes --> Middleware
    Routes --> Controllers
    Controllers --> Services
    Controllers --> Models
    Controllers --> Blade
    Services --> Models
    Jobs --> Services
    Jobs --> Models
    Config --> Services
    Blade --> JS
    Blade --> CSS
    JS --> Public
    CSS --> Public
    Migrations --> Models
    Seeders --> Models
    Tests --> Controllers
    Tests --> Services
    Tests --> Models
```

### Modulos principales

| Modulo | Responsabilidad | Archivos relevantes |
|---|---|---|
| Rutas y acceso | Define endpoints y roles autorizados | `routes/web.php`, `app/Http/Middleware/EnsureRole.php` |
| Autenticacion | Inicio de sesion, registro y cierre de sesion | `AuthController`, vistas `resources/views/auth/` |
| Reservas | Asistente, validacion, cancelacion y reserva final | `ReservationController`, `AvailabilityService`, `resources/views/reservas/` |
| Simulador IA | Generacion, cuotas, historial y enlace a reserva | `AiStudioController`, `GenerateHairPreview`, `config/ai.php`, `resources/views/ai/` |
| Administracion | Servicios, categorias, productos, solicitudes y reportes | `app/Http/Controllers/Admin/`, `resources/views/admin/` |
| Agenda peluquero | Disponibilidad, horas y estado de solicitudes | `AvailabilityController`, `HoursController`, `SolicitudController` |
| Datos | Esquema y datos iniciales | `database/migrations/`, `database/seeders/` |
| Interfaz | Plantillas, estilos e interacciones | `resources/views/`, `resources/css/app.css`, `resources/js/app.js` |

---

## 5. Vista fisica o de despliegue

La vista fisica muestra la distribucion de los componentes en el entorno local actual. El proyecto funciona con XAMPP para PHP y MariaDB, Node/Vite para compilar los recursos y Google Gemini como servicio externo opcional.

```mermaid
flowchart LR
    Browser[Navegador web\nCliente, admin o peluquero]

    subgraph Workstation[Equipo de desarrollo Windows]
        subgraph XAMPP[XAMPP]
            PHP[PHP 8.2\nLaravel artisan serve]
            MySQL[(MariaDB/MySQL\nhaircut_studio_laravel)]
        end

        App[Proyecto Laravel\nHaircutHomeStudio]
        Queue[Worker Laravel\nqueue:work --queue=ai,default]
        Files[Disco local\npublic/storage y storage/app/private/ai]
        Node[Node.js y Vite\nnpm run dev o npm run build]
    end

    Gemini[Google Gemini API\nSolo con AI_PROVIDER=gemini]
    PhpMyAdmin[phpMyAdmin\nhttp://localhost/phpmyadmin]

    Browser -->|HTTP 127.0.0.1:8000| PHP
    PHP <--> App
    App <--> MySQL
    App <--> Files
    App --> Queue
    Queue <--> Gemini
    Node --> App
    PhpMyAdmin <--> MySQL
```

### Notas de despliegue

- La base de desarrollo es `haircut_studio_laravel`. La base legacy `haircut_studio` se conserva como respaldo y no debe recibir comandos destructivos como `migrate:fresh`.
- El servidor web Laravel y el worker de IA se ejecutan en terminales separadas cuando `AI_PROCESS_SYNC=false`.
- La clave `GEMINI_API_KEY` pertenece solamente al archivo `.env`, nunca al repositorio, vistas o JavaScript.
- En produccion se recomienda usar un servidor web real, HTTPS, un worker supervisado y una tarea programada que ejecute `php artisan schedule:run` cada minuto.

---

## 6. Vista de escenarios (+1)

Los escenarios comprueban que la arquitectura responde a los casos de uso de mayor valor y riesgo.

```mermaid
flowchart TD
    Start([Cliente autenticado]) --> Select[Selecciona categoria y servicio]
    Select --> Continue[Pulsa Siguiente]
    Continue --> Availability{AvailabilityService\nencuentra horario?}
    Availability -->|No| SelectDate[Mostrar dias u horas no disponibles]
    SelectDate --> Availability
    Availability -->|Si| Reference[Adjunta foto opcional\no usa simulacion IA]
    Reference --> Place[Selecciona salon o domicilio]
    Place --> Review[Revisa resumen]
    Review --> Submit[Envia reserva]
    Submit --> Validate{Servidor valida\ndatos y disponibilidad?}
    Validate -->|No| Correct[Informar error y volver al flujo]
    Correct --> Select
    Validate -->|Si| Created[(Reserva creada)]

    AIStart([Cliente autenticado]) --> Upload[Sube fotografia]
    Upload --> SelectAI[Selecciona servicio permitido]
    SelectAI --> Limit{Cuota disponible\no cuenta sin limite?}
    Limit -->|No| Denied[Mostrar limite alcanzado]
    Limit -->|Si| Generate[Crear generacion y encolar trabajo]
    Generate --> Result{Gemini responde?}
    Result -->|Si| PrivateResult[(Resultado privado)]
    Result -->|No| Failed[Registrar fallo sin exponer clave]
    PrivateResult --> BookLink[Reservar el mismo servicio]
    BookLink --> Submit
```

### Casos de uso que el equipo puede presentar

1. **Reserva de servicio:** un cliente elige categoria, servicio, fecha, hora, imagen opcional y lugar; el sistema crea una reserva solo si la disponibilidad sigue vigente.
2. **Prevencion de choque de horario:** dos clientes intentan reservar el mismo bloque; la transaccion y la segunda validacion permiten crear solo una reserva.
3. **Simulacion de imagen:** un cliente sube una foto, elige un corte o color y recibe una imagen generada de forma asincrona y privada.
4. **Control de cuotas IA:** el sistema limita el uso por usuario, IP y limites globales; la cuenta de prueba tiene excepcion controlada mediante `ai_sin_limite`.
5. **Gestion administrativa:** un administrador agrega o modifica servicios; los cambios se reflejan tanto en el asistente de reservas como en las opciones del simulador al actualizar la pagina.
6. **Gestion del peluquero:** el peluquero actualiza disponibilidad, revisa solicitudes y controla su agenda diaria.

---

## 7. Recomendacion para la entrega

Para una presentacion o informe, exporta cada uno de los cinco diagramas como imagen PNG o PDF y conservan estos titulos:

1. Vista logica (MVC y servicios).
2. Vista de procesos (reserva e IA asincrona).
3. Vista de desarrollo (organizacion de modulos Laravel).
4. Vista fisica/de despliegue (XAMPP, Laravel, MariaDB, archivos y Gemini).
5. Vista de escenarios (+1) (reserva, disponibilidad, IA y roles).

En cada diagrama agrega debajo una leyenda breve: rectangulos para componentes, cilindros para almacenamiento, figuras de persona para actores y flechas para dependencias o flujo de informacion. Mantener los nombres de clases y carpetas de este documento permite relacionar directamente la evidencia con el codigo fuente entregado.