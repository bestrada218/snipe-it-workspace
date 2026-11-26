# Placas

Cómo se forma cada placa, cómo se arma al crear un elemento y cómo se administran los tipos.

## El formato XX-KKK-PP-TTT

Cada elemento del inventario lleva una placa única con cuatro partes:

| Parte | Qué es | Ejemplo |
| --- | --- | --- |
| **XX** | Ubicación padre, normalmente el estado. | NY, NJ |
| **KKK** | Consecutivo global de tres dígitos. | 042 |
| **PP** | Tipo de elemento. | LP (portátil), HS (diadema) |
| **TTT** | Sufijo opcional, solo en computadores: el sistema operativo. | W11, MAC, LNX |

Ejemplos: `NY-042-LP-W11` (portátil con Windows 11 en Nueva York), `NJ-105-HS` (diadema en Nueva Jersey, sin sufijo).

La [guía de placas](route:guide.asset-tags) tiene la tabla completa de tipos y sufijos.

## El consecutivo global

- **Un número identifica una sola pieza en todo el inventario**: activos, accesorios, consumibles, componentes y licencias comparten la misma serie. Si existe `NY-042-LP-W11`, no puede existir `NJ-042-HS`.
- El número no se reutiliza, aunque el elemento se borre o cambie de tipo, ubicación o sufijo.
- Si dos personas guardan al mismo tiempo, **nunca reciben el mismo número**: quien guarda segundo recibe el siguiente libre automáticamente y ve un aviso con la placa final.
- La serie llega hasta 999.

## Armar la placa al crear un elemento

En el formulario de creación de cualquier módulo aparece el bloque **Armar la placa**:

1. Elige la **Ubicación (XX)**.
2. Elige el **Tipo (PP)**.
3. Si es un portátil o un escritorio, elige el **Sufijo (TTT)**; en los demás tipos el sufijo está desactivado.
4. El **Consecutivo (KKK)** se llena solo con el siguiente número libre.

La placa se arma arriba sola. **El campo de la placa no se escribe a mano**: así se evitan errores y números repetidos.

### Crear varios activos a la vez

Al crear activos puedes agregar filas para registrar varias unidades del mismo modelo en un solo paso. Cada fila recibe su propio consecutivo (el siguiente libre, y así sucesivamente).

<!-- admin-only -->

## Tipos de placa (PP)

Los tipos se administran en **Configuraciones → Placas de activos → Tipos de placa (PP)** ([abrir](route:settings.asset_tag_types.index)). También hay un enlace **Administrar tipos** junto al constructor de la placa.

- **Agregar** un tipo nuevo: código de dos letras y nombre. Las filas que dejes vacías se ignoran al guardar.
- **Elegir qué tipos llevan sufijo** (normalmente portátiles y escritorios).
- **Renombrar un código**, por ejemplo LP → LT: todas las placas que lo usan se actualizan en todos los módulos y cada elemento guarda el cambio en su historial.

> Un tipo que ya usan algunas placas no se puede quitar. En su lugar, cámbiale el código o el nombre.

## Placas repetidas de datos antiguos

Si se importaron datos con números repetidos, un administrador puede listarlos con el comando `php artisan snipeit:asset-tag-duplicates`. El primer registro de cada número lo conserva y los demás deben corregirse.

<!-- end-admin-only -->
