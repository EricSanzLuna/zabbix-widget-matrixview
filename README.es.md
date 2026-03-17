# Matrix View para Zabbix 7.0

Documentación en español. For the English guide, see [README.md](./README.md).

`Matrix View` es un widget de dashboard para Zabbix 7.0 que renderiza una matriz con:

- filas = hosts filtrados
- columnas = items de referencia seleccionados

El objetivo del widget es comparar el mismo item lógico entre varios hosts, con una vista tipo tablero de estado para servicios, procesos o indicadores operativos.

## 🚀 Instalación

Copia únicamente la carpeta `matrix_view` dentro de los módulos del frontend de Zabbix:

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

1. Ve a `Administration -> General -> Modules`
2. Haz clic en `Scan directory`
3. Habilita `Matrix View`
4. Añade el widget a un dashboard

## ⚙️ Configuración

### 🖥️ Filtros de hosts

- `Host groups`: limita la matriz a los hosts de los grupos seleccionados
- `Hosts`: filtro opcional para hosts específicos
- `Show hosts in maintenance`: incluye hosts que estén actualmente en mantenimiento
- `Host order`: orden ascendente o descendente por nombre
- `Host limit`: número máximo de filas a mostrar
- `Density`: espaciado de celdas en modo compacto o cómodo
- `Header orientation`: encabezados en diagonal, horizontal o vertical
- `Show indicator legend`: muestra u oculta la leyenda de estados

Los hosts que no tengan ninguno de los items seleccionados se excluyen automáticamente de la matriz.

Si un host visible está en mantenimiento, junto a su nombre se muestra un indicador con tooltip que incluye:

- nombre del mantenimiento
- si el mantenimiento es con o sin recolección de datos
- fecha/hora de fin, cuando esté disponible

### 🧱 Columnas

El widget usa items explícitos de Zabbix como columnas de referencia.

En el campo `Columns`:

1. seleccionas uno o más items
2. cada item seleccionado se convierte en una columna
3. el widget toma el `key_` exacto de ese item
4. para cada host visible, busca un item con el mismo `key_`

Esto funciona mejor cuando todos los hosts objetivo exponen las mismas keys.

Ejemplo:

- seleccionas `CPU utilization` de un host
- si los demás hosts tienen el mismo `key_`, esa columna se llena correctamente

### 🏷️ Alias de columnas

Puedes renombrar visualmente los encabezados con `Column aliases`.

Formato:

```text
key|alias
```

Ejemplos:

```text
service.info[W3SVC,state]|IIS
service.info["W3SVC",state]|IIS
service.info[HEATEmailService,state]|HEAT Email
icmpping|Ping
```

Notas:

- `key` debe corresponder al `key_` del item seleccionado
- el widget normaliza variantes con y sin comillas, así que ambas son válidas
- el alias sólo afecta la visualización
- el tooltip conserva el nombre completo original del item

### ↔️ Orden de columnas

Puedes reordenar columnas manualmente con `Column order`.

Formato:

- una línea por `item key` o por alias

Ejemplos:

```text
IIS
HEAT Email
service.info["SQLSERVER",state]
service.info["SQLSERVERAGENT",state]
```

Notas:

- las columnas listadas se renderizan primero, en el orden indicado
- las no listadas se muestran después, respetando su orden original
- puedes mezclar aliases y keys

### 📏 Umbrales por item

Puedes sobrescribir los umbrales globales por item con `Per-item thresholds`.

Formato:

```text
key|direction|warning|high|critical
```

Ejemplos:

```text
system.cpu.util|asc|70|85|95
vfs.fs.size[/,pused]|asc|80|90|95
service.info["W3SVC",state]|desc|6|3|1
```

Notas:

- `direction` acepta `asc` o `desc`
- si un item no tiene override, se usan los umbrales globales

## 🎨 Colores y evaluación de estado

El widget soporta valores numéricos, valores de texto y también el uso de triggers activos asociados al item.

### 🚨 Modo trigger-first

Cuando `State source` está en `Triggers first, thresholds fallback`:

1. el widget revisa si hay triggers activos asociados al item
2. si existen, la severidad más alta define el color de la celda
3. si no existen, cae a umbrales numéricos o patrones de texto

Este es el modo recomendado cuando las severidades de tus triggers ya representan la importancia operativa real.

### 🟢 Colores de indicadores

Cada estado visual puede personalizarse con un color HEX:

- `OK color`
- `Info color`
- `Warning color`
- `High color`
- `Critical color`
- `Missing item color`

Formatos aceptados:

```text
4bb476
#4bb476
```

Estos colores controlan tanto el icono como el tinte suave del fondo de la celda.

### 🔢 Valores numéricos

Los valores numéricos usan primero los umbrales por item, si existen; en caso contrario usan los globales:

- `Warning threshold`
- `High threshold`
- `Critical threshold`

Y una dirección:

- `Higher values are worse`
- `Lower values are worse`

Ejemplo para CPU:

- Warning: `70`
- High: `85`
- Critical: `95`

### 🔤 Valores de texto

Los valores de texto usan coincidencia por patrones:

- `OK text patterns`
- `Warning text patterns`
- `Critical text patterns`

Los patrones se separan por comas y se comparan sin distinguir mayúsculas/minúsculas.

Ejemplo para servicios Windows/Linux:

```text
OK text patterns: running,up,ok,healthy,1
Warning text patterns: warning,degraded
Critical text patterns: stopped,down,critical,failed,fail,error,0
```

Si no hay coincidencia:

- la celda se muestra como `Info`

Si el item no existe en un host:

- la celda usa `Missing item label`

## 🧠 Cómo se construye la matriz

Para cada host seleccionado:

1. el widget recorre los items de referencia seleccionados
2. toma el `key_` de cada item
3. busca un item con ese mismo `key_` en el host actual
4. obtiene el último valor
5. colorea la celda según triggers, umbrales o patrones de texto

## ✅ Casos de uso recomendados

Este diseño funciona especialmente bien cuando:

- todos los hosts comparten las mismas keys
- necesitas una matriz host x servicio/estado
- quieres algo más parecido a un tablero operativo que a una gráfica temporal

Buenos ejemplos:

- estado de servicios
- CPU / memoria / disco
- colas y contadores de procesos
- banderas de salud de aplicaciones

## 🛠️ Troubleshooting

### El módulo no aparece al escanear el directorio

Verifica que esté instalado como:

```text
ui/modules/matrix_view/manifest.json
```

No como:

```text
ui/modules/zabbix-widget-matrixview/matrix_view/manifest.json
```

### El widget muestra `No matching items were found`

Revisa que:

- los hosts seleccionados realmente tengan esas keys
- los items de referencia usen keys compartidas por los demás hosts
- el usuario tenga permisos de lectura sobre esos hosts/items
- los hosts estén monitoreados y con datos recientes

### Las celdas muestran `No item`

Ese host no tiene un item con el mismo `key_` que la columna de referencia seleccionada.

### Aparece el icono de mantenimiento en un host

Ese host está actualmente en mantenimiento. Pasa el mouse sobre el indicador junto al nombre del host para ver el nombre del mantenimiento, el modo y la fecha de fin.

### Los aliases no parecen aplicar

Verifica el `Key` exacto del item en Zabbix:

1. Ve a `Data collection -> Hosts`
2. Abre el host
3. Entra a `Items`
4. Abre el item
5. Copia el campo `Key`

Luego usa esa key en `Column aliases`. Para checks de servicio, el widget acepta tanto la variante con comillas como sin comillas.

### Los colores no coinciden con el estado esperado

Ajusta:

- umbrales numéricos
- patrones de texto para OK / Warning / Critical
- modo de evaluación (`Triggers first` o sólo thresholds/text patterns)

## 📌 Limitaciones actuales

- no existe modo `Problems` por tags
- no hay editor embebido de columnas como `Top hosts`
- no hay modal de detalle por celda

La versión actual se enfoca intencionalmente en una matriz basada en items, más confiable y más compatible con Zabbix 7.0.
