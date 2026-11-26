# Cambios por versión

## 0.2.1

### Mantenimiento

- Se corrigen los avisos de severidad alta de las dependencias JavaScript de la 0.2 y se limita el servidor de recarga automática a la máquina local.
- La prueba del filtro de próximas bajas utiliza fechas de terminación explícitas para evitar fallos intermitentes.

## 0.2

Basada en **Snipe-IT v8.8.3**.

### Bodegas e inventario

- La vista Bodegas incluye gráficos de existencias, búsqueda, exportación a CSV y filtros para bodegas propias y tercerizadas. Las bodegas propias también se pueden filtrar por estado.
- Cada unidad disponible de un accesorio devuelto conserva su ubicación en bodega. La ficha del accesorio muestra las existencias por bodega, y al asignarlo se puede elegir de cuál sale.
- Al registrar el ingreso de un activo, la aplicación pregunta en qué bodega propia se guardará. Los envíos a una bodega de un proveedor externo se gestionan desde Devoluciones.
- Los estados distinguen entre equipos listos para desplegar, en revisión, pendientes de configuración, en reparación, dañados o retirados. Las devoluciones actualizan por separado el estado del equipo y su ubicación física.

### Devoluciones

- Cada artículo devuelto va a una bodega propia o a una bodega administrada por un proveedor externo. El nombre del proveedor se toma de Configuraciones.
- Cada etapa del envío se registra manualmente con su fecha. Las etapas que lo permiten se pueden deshacer. También se pueden editar el ticket, los responsables, la transportadora y el número de guía.
- El selector de bodegas muestra la jerarquía de ubicaciones y permite elegir el nivel más bajo habilitado para recibir inventario.
- Los activos devueltos quedan sin asignar. Su ficha muestra la bodega física y los enlaces a la ubicación y la devolución hasta la siguiente asignación.
- Las devoluciones aplican los permisos por empresa a los listados, los recuentos, los enlaces directos, las ediciones y la selección de responsables. Si incluyen accesorios, se exigen los permisos correspondientes.

### Copias de seguridad y recuperación

- Desde *Copias en la nube* (Configuraciones → Copias de seguridad) se pueden configurar destinos cifrados en Amazon S3 o Google Drive. Cada servicio tiene una guía y una explicación de los campos. También se puede probar la conexión y consultar el resultado de la última copia.
- La comprobación diaria también revisa los destinos en la nube configurados en la aplicación y avisa si faltan copias o están desactualizadas.
- Las copias en la nube verifican el cifrado AES-256 y comparan las sumas de verificación SHA-256 tras la subida. Los archivos descargados se validan antes de aparecer en el listado local.
- Las restauraciones validan los archivos adjuntos y el respaldo de la base de datos. La interfaz web permite realizar una restauración a la vez.

### Acceso y protección de datos

- Solo los superadministradores pueden abrir *Copias en la nube* y *Correo*. Cada visita, incluso al recargar la página, exige la contraseña y un código de doble factor. La confirmación es válida para la sección que la solicitó.
- Las descargas de copias desde la web requieren una nueva confirmación de doble factor. La API anterior de descargas está deshabilitada de forma predeterminada.
- En las instalaciones nuevas, los superadministradores deben configurar el doble factor y los datos de sesión están cifrados. Los secretos de doble factor existentes y nuevos se cifran en la base de datos.
- El Informe de actividad registra los inicios de sesión completados desde la web.
- Los datos de sesión usados en la validación y los diagnósticos de las pruebas de conexión excluyen las credenciales de nube y SMTP. El correo SMTP configurado desde la aplicación requiere TLS con validación de certificado.

### Correo, alertas y navegación

- En Configuraciones → Correo se puede configurar el servidor de salida. Hay guías para Google, Microsoft 365, SendGrid, Amazon SES y Mailgun, explicaciones de los campos y un botón para enviar un correo de prueba.
- A la hora elegida se envía un correo con los recuentos de envíos pendientes, reemplazos próximos, devoluciones sin avance y problemas de copias de seguridad, junto con un enlace a Workspace. El correo se envía si hay pendientes.
- Las devoluciones sin avance aparecen en *Requiere atención* y en una pestaña propia al vencer el plazo configurado, que es de 7 días de forma predeterminada.
- El *Centro de ayuda* está disponible en español e inglés. Tiene 14 secciones con índices, búsqueda en todo el contenido y enlaces a las pantallas correspondientes.
- Configuraciones y el pie de página muestran ambos números de versión. Al seleccionar la versión de Workspace se abre *Cambios por versión*.
- Los contadores del menú lateral se guardan en caché por usuario y se actualizan cuando cambia un activo, un envío, una devolución o una configuración.
- Los desplegables de Select2 cargan la traducción disponible para variantes regionales como el español de Colombia.

### Instalación y actualizaciones

- Las instalaciones nuevas escuchan en la interfaz local de bucle invertido, generan una contraseña para las copias de seguridad y usan cookies seguras cuando se configuran con HTTPS. El correo de instalación omite las credenciales de acceso.
- La salida de depuración está deshabilitada en producción. La compilación de Docker excluye secretos locales, copias de seguridad, archivos subidos y datos generados durante la ejecución. Apache limita el acceso a la raíz web, bloquea los archivos ejecutables subidos y omite las URL en los registros de acceso.
- El proxy de almacenamiento público requiere activación expresa. Rechaza la superposición entre los prefijos de copias de seguridad en S3 y los de almacenamiento privado, y aísla el contenido activo en el navegador.
- Las actualizaciones descargan la imagen base vigente y pueden reconstruirla con paquetes actualizados. Si una actualización queda incompleta, se vuelve a intentar hasta que Workspace supere la comprobación de estado.
- La compilación del contenedor de Ubuntu requiere el parche de seguridad de PHP de noviembre de 2026 y omite el instalador de PEAR que ya no se usa.
- La integración continua ejecuta las pruebas automáticas y audita las dependencias de PHP y JavaScript antes de publicar.
- Las dependencias de CSV y Excel incorporan las correcciones de seguridad de League CSV 9.28.1 y SheetJS 0.20.3.

## 0.1

Primera versión de Snipe-IT Workspace, basada en **Snipe-IT v8.8.3**.

### Placas de activos

- El formato `XX-KKK-PP[-TTT]` se genera a partir de la ubicación, el tipo y, para los computadores, el sufijo del sistema operativo. La placa se ingresa mediante el generador.
- Un único consecutivo `KKK` se comparte en todo el inventario. Si dos personas guardan activos al mismo tiempo, cada activo recibe un número diferente.
- Los tipos de placa se administran desde Configuraciones. Al renombrar un código se actualizan todas las placas que lo usan y se registra el cambio en el historial de cada activo.
- La placa aparece primero en los títulos, listados, asignaciones, resultados de búsqueda y etiquetas impresas.

### Asignaciones

- Se asigna una unidad con placa a la vez.
- En todos los flujos de asignación, las asignaciones duplicadas requieren un motivo y un ticket. Las categorías seleccionadas también requieren aprobación y evidencia.
- Hay una página de asignaciones duplicadas.

### Envíos a personal remoto

- Los envíos registran transportadora, guía, fecha estimada de entrega y aprobación de compra.
- Hay una barra de avance, seguimiento de próximos vencimientos y atrasos, y una pestaña de envíos pasados.

### Devoluciones

- Las devoluciones por terminación o cambio cargan automáticamente los artículos asignados a la persona.
- Cada artículo puede ir a la bodega de un proveedor o a una ubicación de almacenamiento. Se registran las etapas de envío y de preparación por el proveedor, el responsable del ticket y el responsable de la devolución. Las notas se copian al historial de cada artículo.
- Las ubicaciones de almacenamiento se distinguen de las ubicaciones donde los activos están en uso.

### Otras funciones

- Fechas obligatorias de reemplazo con alertas.
- Informes de daños para activos marcados como dañados.
- Etiquetas impresas con placa, modelo, empresa y número de serie, con la placa primero.
- Los módulos de inventario se pueden desactivar y renombrar. Los grupos de permisos se pueden limitar a una ubicación, y el informe de actividad es configurable.

### Workspace

- Interfaz rediseñada, lista *Requiere atención* en el tablero y búsqueda global. Disponible en español e inglés.

### Copias de seguridad e instalación

- Se generan copias de seguridad cada 6 horas y se copian a una carpeta en Documentos.
- Se pueden configurar copias cifradas en Amazon S3, almacenamiento compatible con S3 o Google Drive. Las restauraciones comprueban la copia completa antes de modificar los datos.
- Instalación en Ubuntu con un solo comando y acceso a las funciones de administración mediante `snipe-it-workspace`.
