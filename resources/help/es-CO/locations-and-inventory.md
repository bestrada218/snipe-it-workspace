# Ubicaciones e inventario

Cómo se organizan las ubicaciones, qué es una bodega, cómo se activan y renombran los módulos de inventario y cómo se limita un grupo a una ubicación.

## Ubicaciones en jerarquía

Las ubicaciones se pueden organizar en una jerarquía, por ejemplo **región → sede → bodega**. Cada ubicación puede tener una **ubicación padre**. [Configuraciones → Ubicaciones](route:locations.index)

Esta jerarquía se usa en varios lugares:

- La parte **XX** de la [placa](help:asset-tags) es la ubicación padre, normalmente el estado.
- Si guardas una [devolución](help:returns) en una bodega propia, elige una ubicación habilitada del nivel más bajo.
- Un grupo limitado a una ubicación también ve las ubicaciones que dependen de ella.

## Bodegas

Al editar una ubicación, la casilla **Bodega / almacenamiento** la marca como bodega o cuarto de almacenamiento. Lo que esté en una bodega se considera **guardado**, no **instalado en uso**.

Un activo fijo puede estar instalado en una sede o guardado en una bodega. Los módulos por ubicación muestran los activos instalados por separado del inventario en bodega.

La ubicación de la bodega del proveedor externo, para las devoluciones, debe estar marcada como bodega.

<!-- admin-only -->

## Módulos de inventario

En **Configuraciones → General → Módulos de inventario** un superadministrador puede:

- **Activar o desactivar** cada tipo de inventario (accesorios, consumibles, componentes, licencias…). Al desactivar un módulo se ocultan del menú, pero sus registros se conservan.
- **Renombrar** un módulo con el nombre que use el equipo.

### Módulos por ubicación

En la misma pantalla, **Módulos por ubicación → Añadir un módulo** crea un listado propio en el menú de Inventario, por ejemplo **Activos fijos**:

| Campo | Qué hace |
| --- | --- |
| **Nombre** | Cómo aparece en el menú. |
| **Ubicación** | Limita el listado a esa ubicación (y las que dependen de ella). Vacío: todas. |
| **Categorías** | Solo entran activos de esas categorías (por ejemplo televisores y proyectores). Vacío: todos. |

El módulo muestra los activos **asignados a una ubicación** que cumplen esas condiciones, sin contar los que están en bodegas.

## Grupos limitados a una ubicación

En la página de administración (el engranaje de arriba a la derecha) → **Grupos**, al editar un grupo, el campo **Alcance por ubicación** limita lo que sus miembros pueden ver y hacer a una ubicación y las que dependen de ella.

- Déjalo vacío para no limitar por ubicación.
- Los permisos del grupo (ver, crear, asignar…) se definen abajo, como siempre.
- Si alguien intenta elegir una ubicación fuera de su alcance, el sistema lo rechaza.

<!-- end-admin-only -->
