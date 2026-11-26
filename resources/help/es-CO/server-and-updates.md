# Servidor, versiones y actualizaciones

Para quien administra el computador donde está instalado: cómo se instala, cómo se actualiza, cómo se numeran las versiones y los comandos útiles.

## Versiones

Snipe-IT Workspace tiene **su propio número de versión**, separado del de Snipe-IT, el programa original en el que se basa:

| Cambio | Ejemplo |
| --- | --- |
| Arreglos pequeños y ajustes | 0.1 → 0.1.1 → 0.1.2 |
| Una función nueva o un cambio notable | 0.1.x → 0.2 |
| Versión formal y estable | 0.x → 1.0 |

Dónde verla:

- al pie de todas las páginas;
- en **Configuraciones → General → Información del sistema**, junto a la versión de Snipe-IT;
- en la terminal, con `snipe-it-workspace status`.

Lo que trae cada versión está en [Cambios por versión](route:changelog).

## Cómo llegan las versiones al servidor

- El trabajo del día a día se hace en una rama de desarrollo. **El servidor nunca se actualiza desde ahí.**
- Cuando una versión está lista, se publica: se actualiza el registro de cambios, se sube el número y se le pone su etiqueta (por ejemplo `v0.2`).
- El servidor **solo instala versiones publicadas**.

## Actualizar

En la terminal del servidor:

```
snipe-it-workspace update
```

Solo hace algo si hay una versión nueva. Antes de actualizar **hace un backup**; si el backup falla, no actualiza. Luego descarga la versión, reinicia la aplicación (uno o dos minutos sin servicio) y comprueba que responda. También te dice de qué versión a cuál pasaste.

Para que se actualice sola cada noche a las 3:30: `snipe-it-workspace auto-update on` (y `off` para quitarlo).

## Comandos útiles

| Comando | Qué hace |
| --- | --- |
| `snipe-it-workspace status` | Si está funcionando y qué versiones tiene. |
| `snipe-it-workspace update` | Instala la última versión publicada (con backup antes). |
| `snipe-it-workspace backup` | Hace un backup ahora y lo copia a Documentos. |
| `snipe-it-workspace backups` | Lista los backups del servidor. |
| `snipe-it-workspace sync-backups` | Copia ya los backups terminados a Documentos. |
| `snipe-it-workspace logs` | Muestra el registro de la aplicación en vivo. |
| `snipe-it-workspace restart` / `start` / `stop` | Reinicia, enciende o apaga la aplicación. |
| `snipe-it-workspace auto-update on` / `off` | Actualización automática cada noche. |

## Instalar en un computador nuevo

En un Ubuntu 22.04 o más reciente, con tu usuario normal (sin sudo):

```
curl -fsSL https://raw.githubusercontent.com/bestrada218/snipe-it-workspace/main/scripts/workspace-setup.sh | bash
```

El instalador pone todo lo necesario, arranca la aplicación y pregunta si quieres actualizaciones automáticas y si el equipo no debe suspenderse. Al terminar, abre la dirección que muestra y crea el primer administrador.
