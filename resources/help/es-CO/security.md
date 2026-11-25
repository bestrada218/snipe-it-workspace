# Seguridad

El doble factor de autenticación, las secciones protegidas y quién puede hacer qué.

## Doble factor de autenticación (2FA)

El doble factor pide, además de la contraseña, un **código de 6 dígitos** que cambia cada 30 segundos y que genera una aplicación en tu celular: Google Authenticator, Microsoft Authenticator, 1Password u otra.

### Activarlo en tu cuenta

La primera vez que se te pida:

1. Instala una aplicación de autenticación en el celular.
2. Escanea el **código QR** que muestra la pantalla. Si no puedes escanearlo, escribe la clave que aparece debajo.
3. Escribe el código de 6 dígitos que muestra la aplicación y confirma.

Desde ese momento, cada vez que se pida, abre la aplicación y escribe el código del momento.

> Si cambias o pierdes el celular, pide a un administrador que **restablezca tu doble factor** desde tu perfil de usuario; la próxima vez lo vuelves a activar con el celular nuevo.

### Exigirlo al iniciar sesión

En **Configuraciones → Seguridad**, **Activar la verificación de dos factores**, un administrador elige:

- **Desactivado**: no se pide al iniciar sesión.
- **Selectivo**: solo a quienes lo activen en su cuenta.
- **Requerido para todos los usuarios**: todos deben usarlo.

Se recomienda exigirlo, al menos, a los administradores.

## Secciones que siempre piden confirmación

Las secciones [Copia en la nube](help:backups#copia-en-la-nube) y [Correo](help:email-and-summary) guardan llaves y contraseñas del sistema. Por eso:

- Solo pueden entrar **superadministradores**.
- **Cada vez que se entra**, se pide la **contraseña** y un **código de doble factor**, aunque hayas entrado hace un minuto. Recargar la página o volver a ella también lo pide.
- **El doble factor es obligatorio** aquí: si tu cuenta no lo tiene, la misma pantalla te pide activarlo antes de dejarte pasar.
- Las llaves se guardan **cifradas** y nunca se vuelven a mostrar.

## Permisos

Lo que cada persona puede ver y hacer depende de sus **grupos** y permisos:

- Los **superadministradores** pueden todo, incluidas las Configuraciones.
- Los **grupos** definen permisos por módulo (ver, crear, editar, asignar, borrar).
- Un grupo puede quedar [limitado a una ubicación](help:locations-and-inventory#grupos-limitados-a-una-ubicacion).
- La búsqueda y los listados solo muestran lo que cada persona tiene permitido ver.
