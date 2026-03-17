# Matrix View for Zabbix 7.0

`Matrix View` is a dashboard widget for Zabbix 7.0 that renders a matrix of:

- rows = filtered hosts
- columns = selected reference items

The widget is designed for service/status dashboards where you want to compare the same logical item across many hosts, similar to a simplified matrix-style version of `Top hosts`.

## Installation

Copy only the `matrix_view` folder into Zabbix frontend modules:

```text
ui/modules/matrix_view/
  manifest.json
  Widget.php
  actions/
  includes/
  views/
  assets/
```

Then:

1. Open `Administration -> General -> Modules`
2. Click `Scan directory`
3. Enable `Matrix View`
4. Add the widget to a dashboard

## Configuration

### Host filters

- `Host groups`: limits the matrix to hosts in the selected groups
- `Hosts`: optional explicit host filter
- `Show hosts in maintenance`: includes hosts currently in maintenance
- `Host order`: ascending or descending by name
- `Host limit`: maximum number of rows rendered
- `Density`: compact or comfortable cell spacing

### Columns

The widget uses explicit Zabbix items as reference columns.

In the `Columns` field:

1. select one or more items
2. each selected item becomes one matrix column
3. the widget uses the selected item's exact `key_`
4. for every visible host, it searches for an item with the same `key_`

This means the best results come when all target hosts expose the same item keys.

Example:

- select `CPU utilization` from one host
- if other hosts also have the same `key_`, they will fill that column

### Per-item thresholds

You can override thresholds for specific selected items in `Per-item thresholds`.

Format:

```text
key|direction|warning|high|critical
```

Examples:

```text
system.cpu.util|asc|70|85|95
vfs.fs.size[/,pused]|asc|80|90|95
service.info[W3SVC,state]|desc|6|3|1
```

Notes:

- `key` must match the selected reference item's exact `key_`
- `direction` accepts `asc` or `desc`
- if a selected item has no override, global thresholds are used

## Colors and state evaluation

The widget supports both numeric and text item values, and can also use active triggers associated with the item.

### Trigger-first mode

When `State source` is set to `Triggers first, thresholds fallback`:

1. the widget checks for active triggers linked to the item
2. if one or more active triggers exist, the highest trigger severity drives the cell color
3. if no active trigger exists, the widget falls back to numeric thresholds or text patterns

This is the recommended mode when your trigger severities already represent the operational importance of each item.

### Numeric values

Numeric values use per-item thresholds when defined, otherwise global thresholds:

- `Warning threshold`
- `High threshold`
- `Critical threshold`

And one direction:

- `Higher values are worse`
- `Lower values are worse`

Example for CPU:

- Warning: `70`
- High: `85`
- Critical: `95`

### Text values

Text values use pattern matching:

- `OK text patterns`
- `Warning text patterns`
- `Critical text patterns`

Patterns are comma-separated and matched case-insensitively.

Example for Windows/Linux services:

```text
OK text patterns: running,up,ok,healthy,1
Warning text patterns: warning,degraded
Critical text patterns: stopped,down,critical,failed,fail,error,0
```

If no pattern matches:

- the cell is shown as neutral/info

If the item does not exist on a host:

- the cell uses `Missing item label`

## How the matrix is built

For each selected host:

1. the widget iterates through the selected reference items
2. it takes the `key_` of each selected item
3. it looks for an item with the same `key_` on the current host
4. it renders the latest value
5. it colors the cell using thresholds or text patterns

## Recommended usage

This design works especially well when:

- all hosts share the same item keys
- you want a host x service/status matrix
- you want something visually closer to a service board than a time-series widget

Good examples:

- service states
- CPU / memory / disk indicators
- queue depth / process counts
- application health flags

## Troubleshooting

### Module does not appear in Scan directory

Make sure the module is installed as:

```text
ui/modules/matrix_view/manifest.json
```

Not:

```text
ui/modules/zabbix-widget-matrixview/matrix_view/manifest.json
```

### The widget shows `No matching items were found`

Check:

- selected hosts really have those item keys
- the selected reference items use keys shared by the other hosts
- the user has permissions to read those hosts/items
- the hosts are monitored and exposing recent values

### Cells show `No item`

That host does not have an item with the same `key_` as the selected reference column.

### Colors do not match expected service states

Adjust:

- numeric thresholds
- text patterns for OK / Warning / Critical

## Current limitations

- no tag-based `Problems` mode
- no detail modal yet

The current version intentionally focuses on a more reliable item-based matrix with a simpler configuration model.
