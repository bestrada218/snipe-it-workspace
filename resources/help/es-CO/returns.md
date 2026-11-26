# Devoluciones

Cómo registrar lo que una persona devuelve, por retiro o por cambio de equipos, y seguir cada elemento hasta que queda guardado.

## Cuándo se usa

Una devolución agrupa **todo lo que una persona entrega**, por uno de dos motivos:

- **Retiro**: la persona deja la empresa.
- **Cambio de equipos**: se le cambia un equipo por otro.

Cada elemento devuelto va a uno de dos destinos:

- **Bodega del proveedor externo** (el socio de hardware): se envía en cajas y el proveedor lo recibe.
- **Bodega propia**. El elemento se guarda en una ubicación administrada por la organización.


## Crear una devolución

1. Ve a [Seguimiento → Devoluciones](route:returns.index) → **Nueva devolución**.
2. Elige la **persona que devuelve**. Sus equipos y accesorios asignados **se cargan solos**.
3. Elige el **motivo** y, si lo tienes, el **número de ticket**, el **responsable del ticket** y el **responsable de la devolución** (por defecto, tú).
4. En la tabla de elementos, marca en **Incluir** lo que se devuelve y elige el **destino** de cada uno:
   - **Bodega del proveedor externo**: responde **¿Estaba antes con el proveedor?** Si dices «No», el proveedor tendrá que recibirlo y configurarlo (dos pasos más).
   - **Bodega propia**. Elige la ubicación donde se guardará el elemento.
5. Escribe una **nota inicial** si hace falta (en inglés) y presiona **Crear devolución**.

### Elegir una bodega propia

El selector muestra la jerarquía configurada. Puedes elegir las ubicaciones habilitadas del nivel más bajo. Los niveles superiores se muestran como encabezados. [Configuraciones → Ubicaciones](route:locations.index)

## Registrar el avance a mano

**Registra cada paso manualmente** cuando ocurra. El envío al proveedor tiene cuatro pasos:

1. **Dirección confirmada**: se confirmó a dónde mandar las cajas.
2. **Cajas enviadas a la persona**.
3. **La persona envió las cajas** de vuelta.
4. **Llegó a la bodega del proveedor externo**.

Para registrar un paso, debajo de la barra de avance:

1. En **¿Qué pasó?** elige el paso.
2. En **Fecha** deja hoy o elige el día en que ocurrió (no se aceptan fechas futuras).
3. Presiona **Registrar**.

**Puedes saltar pasos**: si marcas «La persona envió las cajas», los pasos anteriores que faltaban quedan con la misma fecha.

### Deshacer un paso

Si marcaste algo por error, presiona **Deshacer «paso»** junto al formulario: se borra el último paso registrado.

> La **llegada a la bodega del proveedor externo no se puede deshacer**: en ese momento los equipos se ingresan y quedan disponibles allá.

### Pasos del proveedor por equipo

Los equipos que **no estaban antes con el proveedor** tienen dos pasos más, en su fila: **El proveedor lo recibió** y **El proveedor lo configuró**. Se registran igual (paso, fecha, Registrar) y también se pueden deshacer.

### Guardar en una bodega propia

Cuando los elementos lleguen a la bodega propia, elige la fecha y presiona **Marcar guardado en bodega**.

## Qué pasa con cada equipo

Cuando un equipo llega a su bodega:

- **Se ingresa**: deja de estar asignado a la persona y a cualquier otra cosa.
- Si estaba listo para asignar, al guardarlo en una bodega propia queda **Pendiente de revisión**. En la bodega externa queda **Pendiente de configuración** cuando necesita que el proveedor lo configure y vuelve a **Listo para asignar** al registrar ese paso.
- Su **ubicación física** pasa a ser la bodega que lo recibió.
- La ficha del activo muestra enlaces a la bodega y a la devolución debajo del estado. Esa línea desaparece cuando el equipo se vuelve a asignar.

Los **accesorios** vuelven al inventario disponible; dónde quedó cada uno se ve en la devolución.

La devolución se marca **Completada** sola cuando todos sus elementos llegaron a su destino (y, los que lo necesitan, fueron configurados por el proveedor).

## Devoluciones sin avance

Como todo se registra a mano, una devolución puede quedarse quieta sin que nadie lo note. Por eso, cuando una devolución en curso pasa varios días **sin pasos, cambios ni notas**:

- aparece en **Requiere atención** del tablero;
- aparece en la pestaña **Sin avance** de la lista de devoluciones, con la fecha del último avance y cuántos días lleva quieta;

Agregar una nota cuenta como avance.

## Notas

En **Notas** puedes agregar comentarios en cualquier momento (en inglés). **Cada nota se copia también al historial de cada equipo** de la devolución, con el número de la devolución, para que quede el rastro en el activo.

## Editar o cancelar

- **Editar datos de la devolución**: cambia el motivo, el ticket, los responsables, la **transportadora** y el **número de guía** de las cajas.
- **Cambiar destino** de un elemento: posible mientras ese elemento no se haya ingresado.
- **Cancelar devolución**: solo mientras no se haya ingresado nada. Los equipos siguen asignados a la persona.

<!-- admin-only -->

## Configurar devoluciones

En **Configuraciones → General → Devoluciones** (superadministradores):

Agregar una nota cuenta como avance.
- **Nombre del proveedor externo**: el nombre del tercero que administra la bodega y aparece en los pasos de devolución.
- **Ubicación de la bodega del proveedor**: la ubicación donde quedan los equipos que llegan allá. Debe estar marcada como bodega en Ubicaciones.

<!-- end-admin-only -->
