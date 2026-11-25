# Ubicaciones e inventario

Cómo se organizan las ubicaciones, qué es una bodega, cómo se activan y renombran los módulos de inventario y cómo se limita un grupo a una ubicación.

## Ubicaciones en jerarquía

Las ubicaciones se organizan como un árbol: **país → estado → clínica**. Cada ubicación puede tener una **ubicación padre**. Se administran en [Configuraciones → Ubicaciones](route:locations.index).

Esta jerarquía se usa en varios lugares:

- La parte **XX** de la [placa](help:asset-tags) es la ubicación padre, normalmente el estado.
- En las [devoluciones](help:returns) solo se pueden elegir clínicas, el último nivel.
- Un [grupo limitado a una ubicación](#grupos-limitados-a-una-ubicacion) también ve las ubicaciones que dependen de ella.

## Bodegas

Al editar una ubicación, la casilla **Bodega / almacenamiento** la marca como bodega o cuarto de almacenamiento. Lo que esté en una bodega se considera **guardado**, no **instalado en uso**.

Esto importa porque un activo fijo puede estar en uso en una clínica (un televisor colgado en una sala) o guardado en bodega porque no se está usando. Los equipos asignados a una bodega **no aparecen** en los módulos por ubicación como activos en uso.

La ubicación de la bodega de Rippling, para las devoluciones, debe estar marcada como bodega.

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

En la página de administración (el engranaje de arriba a la derecha) → **Grupos**, al editar un grupo, el campo **Alcance por ubicación** limita lo que sus miembros pueden ver y hacer a una ubicación y las que dependen de ella. Por ejemplo, el equipo de la clínica de Edgewater solo trabaja con lo de Edgewater.

- Déjalo vacío para no limitar por ubicación.
- Los permisos del grupo (ver, crear, asignar…) se definen abajo, como siempre.
- Si alguien intenta elegir una ubicación fuera de su alcance, el sistema lo rechaza.
