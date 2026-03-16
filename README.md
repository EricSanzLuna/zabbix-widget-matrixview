# Matrix View for Zabbix 7.0

`Matrix View` es un widget de dashboard para Zabbix 7.0 que muestra una matriz de `hosts x columnas`.
Puede trabajar en dos modos:

- `Problems`: las columnas salen de un tag de problema/trigger, por ejemplo `matrix:IIS`.
- `Latest data`: las columnas se definen manualmente y cada celda muestra el valor más reciente de un item.

## Instalación

La carpeta que debes copiar al frontend de Zabbix es `matrix_view`, no la raíz completa del repositorio.

La estructura correcta en el servidor debe quedar así:

```text
ui/modules/matrix_view/
  manifest.json
  Widget.php
  actions/
  includes/
  views/
  assets/
```

Después:

1. Ve a `Administration -> General -> Modules`.
2. Pulsa `Scan directory`.
3. Habilita `Matrix View`.
4. Abre un dashboard en modo edición y agrega el widget.

## Configuración rápida

### Parámetros comunes

- `Host groups`: grupos de hosts incluidos.
- `Hosts`: hosts específicos adicionales o filtro manual.
- `Host order`: orden alfabético ascendente o descendente.
- `Max hosts`: límite de filas renderizadas.
- `Max columns`: límite de columnas renderizadas.
- `Density`: `Compact` o `Comfortable`.

## Modo Problems

Este modo funciona parecido a una vista operativa basada en problemas activos.

- `Matrix tag key`: nombre del tag usado para generar columnas. Valor sugerido: `matrix`.
- `Severities`: severidades que deben entrar a la matriz.
- `Acknowledged`: filtra por problemas reconocidos o no reconocidos.
- `Suppressed problems`: incluye u oculta problemas suprimidos.
- `Hosts in maintenance`: controla si se incluyen hosts en mantenimiento.
- `Problem columns order`: lista separada por comas para priorizar columnas.
  Ejemplo: `IIS,SQL,Exchange`
- `Problem cell label`: muestra sólo severidad o severidad + contador.

### Ejemplo de tags

Si tus triggers tienen tags como:

```text
matrix:IIS
matrix:SQL
matrix:Exchange
```

Entonces el widget generará tres columnas: `IIS`, `SQL` y `Exchange`.

Al hacer clic en una celda, el widget abre `Monitoring -> Problems` filtrado por host y tag.

## Modo Latest data

Este modo es útil cuando quieres una matriz parecida a `Top hosts`, pero enfocada en valores puntuales por host.

- `Latest data columns`: una columna por línea con el formato:

```text
Label|pattern|direction|warn|high|critical
```

Donde:

- `Label`: nombre visible de la columna.
- `pattern`: patrón para buscar por `key_` o por nombre del item.
- `direction`: `asc` si valores altos son peores, `desc` si valores bajos son peores.
- `warn`, `high`, `critical`: umbrales numéricos.

### Ejemplos

```text
IIS|service.info[W3SVC,state]|desc|6|3|1
CPU|system.cpu.util|asc|70|85|95
Login|web.test.fail[Login]|asc|1|2|3
```

- `Latest data threshold direction`: dirección por defecto para columnas que no definan `asc` o `desc`.
- `Missing item label`: texto mostrado cuando el host no tiene item coincidente.

## Comportamiento visual

- Encabezado superior fijo.
- Primera columna fija con el nombre del host.
- Scroll horizontal y vertical para matrices grandes.
- Leyenda de colores.
- Tooltips por celda.
- Estados vacíos cuando no hay datos o no hay coincidencias.

## Solución de problemas

### El módulo no aparece en `Scan directory`

Revisa que `manifest.json` esté en:

```text
ui/modules/matrix_view/manifest.json
```

No debe quedar así:

```text
ui/modules/zabbix-widget-matrixview/matrix_view/manifest.json
```

### El widget muestra `No columns could be derived from the configured tag key`

Significa que Zabbix no encontró problemas activos con el tag configurado en `Matrix tag key`.
Verifica:

- que existan problemas activos
- que los triggers/problemas tengan ese tag
- que el usuario tenga permisos para verlos
- que las severidades/filtros no estén excluyendo todo

### El modo Latest data no muestra valores

Verifica:

- que el host tenga items cuyo `key_` o nombre coincidan con el patrón
- que haya último valor disponible
- que el patrón esté escrito igual o use una parte distintiva del item

## Estado actual

Versión inicial funcional para Zabbix 7.0:

- matriz por problemas con columnas por tag
- matriz por latest data con columnas manuales
- filtros operativos básicos
- navegación a Problems
- base lista para un futuro widget de detalle
