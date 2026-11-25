# Envíos a personas remotas

Cómo registrar un equipo que se envía a alguien que trabaja a distancia y cómo queda asignado cuando llega.

## Para qué sirve

Cuando un equipo **no se entrega en mano** sino por transportadora, no conviene asignarlo de inmediato: todavía no lo tiene la persona. El envío lo deja **reservado** para el destinatario mientras viaja, y la asignación se completa **cuando se confirma la entrega**.

## Registrar un envío

Hay tres formas:

- **[Seguimiento → Equipos en envío](route:shipments.index) → Registrar envío**, eligiendo el activo.
- Desde la página de un activo disponible.
- Al **crear un activo nuevo**, marcando **Registrar envío para este activo**: se crea el activo y su envío en un solo paso.

El activo debe estar en un estado que permita asignarlo (listo para desplegar). Cada envío lleva **un solo activo**.

### Datos del envío

| Campo | Qué poner |
| --- | --- |
| **Destinatario** | La persona que recibirá el equipo. |
| **Plataforma de compra** | Dónde se compró, si es una compra nueva (ej.: Amazon, CDW). |
| **Transportadora** | Quién lo lleva (ej.: FedEx, UPS). |
| **Número de guía** | El tracking que da la transportadora. |
| **Fecha estimada de entrega** | Cuándo debería llegar. Con ella se calculan las alertas. |
| **Compra aprobada por** | Quién autorizó la compra, de la lista de aprobadores de compras. |
| **Aprobación de compra** y **Comprobante** | La referencia de la aprobación y un PDF o imagen que la respalde. |

## Estados del envío

La línea de avance muestra en qué va: **registrado → en tránsito → recibido → asignado**. En las listas verás:

| Estado | Significa |
| --- | --- |
| **En tránsito** | Va en camino y la fecha estimada no ha llegado. |
| **Entrega prevista hoy** | Hoy es la fecha estimada. |
| **Entrega pendiente** | La fecha estimada ya pasó y nadie ha confirmado la entrega. |
| **Recibido · asignación pendiente** | Llegó, pero falta completar la asignación (ver abajo). |
| **Recibido y asignado** | Terminado: el activo ya está asignado a la persona. |
| **Cancelado** | Se canceló y el activo volvió a estar disponible. |

## Confirmar la entrega

- **La persona que lo recibe** puede entrar a la aplicación, abrir el envío y presionar **Confirmar que recibí el equipo**.
- **Un responsable de inventario** puede confirmarlo por ella con **Confirmar entrega**, escribiendo cómo lo verificó (por ejemplo «Confirmed by phone with the user»).

Al confirmarse, **el activo queda asignado** a la persona automáticamente y queda registrado en su historial.

### Cuando queda «asignación pendiente»

Si la persona **ya tenía un elemento de esa categoría**, la entrega se registra pero la asignación no se completa sola: un responsable debe presionar **Completar asignación** y escribir la justificación (y la aprobación, si la categoría la exige). Ver [Asignaciones duplicadas](help:assignments#asignaciones-duplicadas).

## Reprogramar o cancelar

- **Reprogramar entrega**: cambia la fecha estimada y pide el motivo, por ejemplo «Carrier delayed the delivery».
- **Cancelar envío**: con motivo; el activo vuelve a quedar disponible.

## Pestañas y alertas

La página de envíos tiene tres pestañas:

- **En curso**: todo lo que va en camino.
- **Entregas por confirmar**: lo que llega hoy o ya está atrasado.
- **Envíos pasados**: los recibidos y los cancelados, con su fecha de cierre.

Los envíos que llegan hoy o están atrasados también aparecen en **Requiere atención** del tablero y en el contador del menú.

## Configurar los aprobadores de compras

En **Configuraciones → General**, sección **Aprobaciones de compra**, un superadministrador elige las personas que pueden figurar como aprobadoras en los envíos.
