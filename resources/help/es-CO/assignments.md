# Asignaciones

Cómo se entrega un elemento a una persona, qué pasa cuando ya tiene uno de la misma categoría y dónde se revisan esos casos.

## Una unidad por asignación

Cada asignación entrega **una sola pieza con su placa**. No hay campos de cantidad: si una persona recibe dos diademas, son dos asignaciones, cada una con su placa. Así siempre se sabe qué unidad exacta tiene cada persona.

En todas las pantallas de asignación **la placa va primero y destacada**, para confirmar que se entrega la unidad correcta.

## Asignar un activo

1. Abre el activo y presiona **Asignar** (o usa la acción de la fila en la lista).
2. Elige **a quién**: una persona, una ubicación u otro activo.
3. Revisa la fecha y, si aplica, la fecha esperada de devolución.
4. Agrega una nota si hace falta (en inglés) y guarda.

También puedes asignar varios activos a la vez marcándolos en la lista y usando **Asignar** en las acciones de grupo, o entregar un **kit** completo.

## Asignaciones duplicadas

Si la persona **ya tiene un elemento de la misma categoría**, el sistema avisa antes de guardar: «Esta persona tiene 1 en "Diademas". Con esta asignación quedará con 2.» Para continuar hay que confirmar y registrar:

- **Justificación** (en inglés), por ejemplo «Backup headset for night shifts».
- **Número de ticket** que respalda la entrega, por ejemplo INC-12345.

### Categorías que requieren aprobación

En las categorías que el administrador marque (normalmente portátiles y escritorios) se pide además:

- **Aprobado por**: quién autorizó, elegido de la lista de aprobadores.
- **Comprobante de aprobación**: un PDF o una imagen (JPG, PNG o WebP), por ejemplo el correo de aprobación.

Sin esos datos la asignación no se guarda.

### Dónde aplica

La regla aplica en **todas** las formas de entregar algo: asignación individual, asignación en grupo, kits, transferencias entre personas, solicitudes atendidas, envíos y la API. En las asignaciones en grupo, si alguien quedaría con un duplicado, el sistema pide hacer esa entrega de forma individual, donde se registran el motivo, el ticket y la aprobación.

## Página de asignaciones duplicadas

[Seguimiento → Asignaciones duplicadas](route:duplicate-assignments.index) lista a las personas que tienen dos o más unidades de una misma categoría. En cada fila puedes:

- ver **qué unidades** tiene (con sus placas);
- revisar las **justificaciones registradas**: motivo, ticket, quién aprobó y el comprobante.

Las filas marcadas «Sin justificación registrada» son duplicados de antes de esta regla o hechos por fuera del flujo; conviene revisarlos.

## Configurar aprobadores y categorías

En **Configuraciones → General**, sección **Aprobación de asignaciones duplicadas** (solo superadministradores):

- **Aprobadores autorizados**: las personas que pueden figurar como aprobadoras.
- **Categorías que requieren aprobación**: donde un duplicado exige aprobador y comprobante. En las demás basta la justificación y el ticket.
