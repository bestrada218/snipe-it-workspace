# Correo y resumen diario

Cómo configurar el correo con el que el sistema envía todo y cómo recibir cada mañana un resumen de lo que está pendiente.

## Para qué sirve

El sistema envía correos para muchas cosas: alertas, aceptaciones de equipos, recuperación de contraseñas, avisos de backups y el **resumen diario**. Para enviarlos necesita una cuenta de correo desde la cual salen los mensajes.

Antes había que configurarla en un archivo del servidor. Ahora se hace desde **Configuraciones → Correo** ([abrir](route:settings.mail.index)), con una guía para cada servicio.

## Quién puede configurarlo

- Solo **superadministradores**.
- **Cada vez que se entra** se pide la contraseña y un código de doble factor, porque la pantalla guarda la contraseña de la cuenta de correo (ver [Seguridad](help:security)).
- La contraseña se guarda **cifrada** y nunca se vuelve a mostrar.

## Configurar el servidor de correo

1. En **Servicio de correo** elige el que usas: Google Workspace o Gmail, Microsoft 365 u Outlook, SendGrid, Amazon SES, Mailgun u otro. **El servidor y el puerto se llenan solos.**
2. Abre **Paso a paso: cómo conseguir cada dato**. Ahí está, para ese servicio, dónde se crea la contraseña o llave y qué va en cada campo.
3. Llena **Usuario**, **Contraseña** y **Correo remitente**. Cada campo explica qué poner.
4. Marca **Enviar los correos con esta configuración** y guarda.
5. En **Probar el envío**, escribe tu correo y presiona **Enviar prueba**. Debe llegar en unos minutos; si no, revisa el spam.

### Qué va en cada campo

| Campo | Qué poner |
| --- | --- |
| **Servidor (SMTP)** | La dirección del servidor de envío, por ejemplo smtp.gmail.com. Se llena al elegir el servicio. |
| **Puerto** | 587 casi siempre. 465 solo si el proveedor lo pide. |
| **Usuario** | Normalmente el correo completo. En SendGrid es la palabra `apikey`. |
| **Contraseña** | La **contraseña de aplicación** o la llave del proveedor, casi nunca la contraseña normal. |
| **Correo remitente** | Desde qué correo salen los mensajes. Debe ser el de la cuenta o uno verificado; si no, los mensajes se rechazan o llegan a spam. |
| **Nombre del remitente** | El nombre que ve quien recibe, por ejemplo Inventario TI. |
| **Responder a** | Opcional: a dónde llegan las respuestas. |

### ¿Qué servicio elegir?

- **Google Workspace o Gmail**: lo más fácil si la empresa ya usa Google. Necesita una contraseña de aplicación.
- **Microsoft 365**: funciona, pero Microsoft está retirando este método; si da error de autenticación, usa otro servicio.
- **SendGrid, Amazon SES o Mailgun**: servicios hechos para que los sistemas envíen correos. Llegan mejor a la bandeja de entrada y no dependen del buzón de una persona.

> Si apagas **Enviar los correos con esta configuración**, el sistema vuelve a usar la configuración del archivo `.env` del servidor, si la hay.

## El resumen diario

Un correo al día con lo que necesita atención:

- **Envíos** que llegan hoy o están atrasados y falta confirmar.
- **Reemplazos obligatorios** vencidos o que vencen en los próximos 15 días.
- **Devoluciones sin avance** (ver [Devoluciones](help:returns#devoluciones-sin-avance)).
- **Problemas con los backups**: no hay uno reciente en el servidor o falló la última copia a la nube.

Cada tema muestra cuántos hay y los primeros, con enlace directo. **Solo se envía si hay algo pendiente**: si todo está al día, no llega nada.

### Activarlo

En la misma pantalla de Correo, sección **Resumen diario**:

1. Marca **Enviar el resumen diario**.
2. En **Quién lo recibe** escribe los correos, separados por coma o uno por línea.
3. Elige la **hora de envío** (hora del servidor).
4. Guarda. Con **Enviar ahora** recibes el de hoy en ese momento, para ver cómo llega.

La pantalla muestra cuándo se envió el último resumen o, si no había pendientes, que ese día no se envió.

## Avisos de backups por correo

Todos los días a las 8:00 el sistema revisa que cada destino de backups (el servidor y las copias en la nube) tenga un backup de menos de un día. Si alguno falla, envía un aviso por correo a la dirección configurada para avisos de backups.
