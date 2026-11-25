# Devoluciones

Cómo registrar lo que una persona devuelve, por retiro o por cambio de equipos, y seguir cada elemento hasta que queda guardado.

## Cuándo se usa

Una devolución agrupa **todo lo que una persona entrega**, por uno de dos motivos:

- **Retiro**: la persona deja la empresa.
- **Cambio de equipos**: se le cambia un equipo por otro.

Cada elemento devuelto va a uno de dos destinos:

- **Bodega de Rippling** (el socio de hardware): se envía en cajas y Rippling lo recibe.
- **Bodega de una clínica**: se queda guardado en una de nuestras clínicas.

> El nombre «Rippling» y su bodega se configuran en Configuraciones → General → Devoluciones. Si allí aparece otro nombre, léelo en lugar de Rippling.

## Crear una devolución

1. Ve a [Seguimiento → Devoluciones](route:returns.index) → **Nueva devolución**.
2. Elige la **persona que devuelve**. Sus equipos y accesorios asignados **se cargan solos**.
3. Elige el **motivo** y, si lo tienes, el **número de ticket**, el **responsable del ticket** y el **responsable de la devolución** (por defecto, tú).
4. En la tabla de elementos, marca en **Incluir** lo que se devuelve y elige el **destino** de cada uno:
   - **Bodega de Rippling**: responde **¿Estaba antes en Rippling?** Si dices «No», Rippling tendrá que recibirlo y configurarlo (dos pasos más).
   - **Bodega de una clínica**: elige **en qué clínica** se guarda.
5. Escribe una **nota inicial** si hace falta (en inglés) y presiona **Crear devolución**.

### La lista de clínicas

La lista muestra las ubicaciones en orden jerárquico (país → estado → clínica). **Solo se pueden elegir clínicas**, el último nivel; los países y estados aparecen en gris. Las clínicas salen de [Configuraciones → Ubicaciones](route:locations.index).

## Registrar el avance a mano

El envío de las cajas se maneja en otra plataforma, así que **cada paso se registra a mano** cuando ocurre. Los cuatro pasos del envío a Rippling son:

1. **Dirección confirmada**: se confirmó a dónde mandar las cajas.
2. **Cajas enviadas a la persona**.
3. **La persona envió las cajas** de vuelta.
4. **Llegó a la bodega de Rippling**.

Para registrar un paso, debajo de la barra de avance:

1. En **¿Qué pasó?** elige el paso.
2. En **Fecha** deja hoy o elige el día en que ocurrió (no se aceptan fechas futuras).
3. Presiona **Registrar**.

**Puedes saltar pasos**: si marcas «La persona envió las cajas», los pasos anteriores que faltaban quedan con la misma fecha.

### Deshacer un paso

Si marcaste algo por error, presiona **Deshacer «paso»** junto al formulario: se borra el último paso registrado.

> La **llegada a la bodega de Rippling no se puede deshacer**: en ese momento los equipos se ingresan y quedan disponibles allá.

### Pasos de Rippling por equipo

Los equipos que **no estaban antes en Rippling** tienen dos pasos más, en su fila: **Rippling lo recibió** y **Rippling lo configuró**. Se registran igual (paso, fecha, Registrar) y también se pueden deshacer.

### Guardar en la clínica

En los elementos que van a una clínica, cuando ya están allá, elige la fecha y presiona **Marcar guardado en bodega**.

## Qué pasa con cada equipo

Cuando un equipo llega a su destino (llegada a Rippling o guardado en la clínica):

- **Se ingresa**: deja de estar asignado a la persona y a cualquier otra cosa.
- Queda **listo para desplegar**, disponible para entregarse a otra persona.
- Su **ubicación** pasa a ser la clínica o la bodega de Rippling.
- La página del activo muestra, debajo del estado, **«En la bodega de Edgewater · Devolución #12»** (o «En la bodega de Rippling»), con enlaces a la ubicación y a la devolución. Esa línea desaparece cuando el equipo se vuelve a asignar.

Los **accesorios** vuelven al inventario disponible; dónde quedó cada uno se ve en la devolución.

La devolución se marca **Completada** sola cuando todos sus elementos llegaron a su destino (y, los que lo necesitan, fueron configurados por Rippling).

## Notas

En **Notas** puedes agregar comentarios en cualquier momento (en inglés). **Cada nota se copia también al historial de cada equipo** de la devolución, con el número de la devolución, para que quede el rastro en el activo.

## Editar o cancelar

- **Editar datos de la devolución**: cambia el motivo, el ticket, los responsables, la **transportadora** y el **número de guía** de las cajas.
- **Cambiar destino** de un elemento: posible mientras ese elemento no se haya ingresado.
- **Cancelar devolución**: solo mientras no se haya ingresado nada. Los equipos siguen asignados a la persona.

## Configurar devoluciones

En **Configuraciones → General → Devoluciones** (superadministradores):

- **Nombre de la bodega del proveedor**: el nombre que se muestra en todos los pasos, por ejemplo Rippling.
- **Ubicación de la bodega del proveedor**: la ubicación donde quedan los equipos que llegan allá. Debe estar marcada como bodega en Ubicaciones.
