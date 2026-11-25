# Copias de seguridad

Cuándo se hacen los backups, dónde quedan, cómo se restauran y cómo enviar una copia cifrada a la nube.

## Backups automáticos

Cada **6 horas** (1:30, 7:30, 13:30 y 19:30) se hace un backup completo:

- la **base de datos** con todo el inventario, personas, historial, envíos, devoluciones y aprobaciones;
- los **archivos subidos**: comprobantes, fotos, documentos e imágenes.

Hacer el backup no detiene la aplicación ni corrompe nada: la base se copia como una «foto» de un solo instante, y lo que se esté guardando en ese segundo queda en ese backup o en el siguiente. La gente puede seguir trabajando mientras tanto.

### Cuánto tiempo se guardan

Se conservan **todos los backups de los últimos 7 días**; después, uno por día durante 16 días más, y luego se van borrando los más viejos.

## Ver, descargar y crear backups

En [Configuraciones → Copias de seguridad](route:settings.backups.index) (administradores):

- La lista muestra cada backup con su fecha y tamaño. Puedes **descargarlo** o **borrarlo**.
- **Generar copia de seguridad** hace uno en ese momento, por ejemplo antes de un cambio grande.
- **Cargar copia de seguridad** permite restaurar uno descargado antes o traído de otro servidor.

## Restaurar un backup

1. En Copias de seguridad, elige el backup y presiona **Restaurar**.
2. Confirma. **Restaurar reemplaza todos los datos actuales** por los del backup.

Antes de borrar nada, el sistema **revisa el backup completo**: que tenga la base de datos, que no esté dañado y que la contraseña de cifrado sea la correcta. Si algo falla, no toca los datos actuales. Además, **hace un backup de lo que hay justo antes de restaurar**, por si necesitas volver atrás.

> Después de restaurar se cierra la sesión de todos. Vuelve a entrar con un usuario que exista en el backup restaurado.

## Copia en la nube

Además de guardarlo en el servidor, cada backup se puede **subir cifrado** a **Amazon S3** (o compatibles como Cloudflare R2, Backblaze B2, Wasabi o MinIO) o a **Google Drive**. Así, si el servidor se daña o se pierde, los datos siguen a salvo.

Se configura en **Copias de seguridad → Copia en la nube** ([abrir](route:settings.cloud_backups.index)):

- Solo pueden entrar **superadministradores**, y siempre se pide **contraseña y código de doble factor** (ver [Seguridad](help:security)).
- Cada destino trae una **guía paso a paso** dentro de la pantalla y cada campo explica de dónde sale.
- La **contraseña de cifrado** cierra cada archivo antes de subirlo. Guárdala fuera del servidor: sin ella los backups de la nube no se pueden abrir.
- **Probar conexión** confirma que las llaves y permisos funcionan.
- La pantalla muestra **cómo salió la última copia** a cada destino.

## Backups en la carpeta Documentos

En el computador donde está instalado (Ubuntu), los backups también se copian a la carpeta **Documentos → Snipe-IT Workspace backups**, para encontrarlos fácilmente desde el explorador de archivos:

- Se copian media hora después de cada backup (2:00, 8:00, 14:00 y 20:00).
- Solo se copian los backups **terminados**, nunca uno a medio escribir.
- Para copiarlos al instante: `snipe-it-workspace sync-backups` en la terminal.
