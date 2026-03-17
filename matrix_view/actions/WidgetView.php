<?php

namespace Modules\MatrixView\Actions;

use API;
use CControllerDashboardWidgetView;
use CControllerResponseData;
use Modules\MatrixView\Widget;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		$fields = $this->getNormalizedFields();
		$hosts = $this->getHosts($fields);
		$reference_items = $this->getReferenceItems($fields);

		$data = [
			'name' => $this->getInput('name', $this->widget->getName()),
			'fields_values' => $fields,
			'matrix' => $this->buildMatrix($hosts, $reference_items, $fields),
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		];

		$this->setResponse(new CControllerResponseData($data));
	}

	private function getNormalizedFields(): array {
		$defaults = [
			'groupids' => [],
			'hostids' => [],
			'show_maintenance' => 1,
			'host_order' => Widget::ORDER_NAME_ASC,
			'limit_hosts' => 25,
			'visual_mode' => Widget::VISUAL_COMPACT,
			'header_orientation' => Widget::HEADER_DIAGONAL,
			'show_legend' => 1,
			'itemids' => [],
			'column_aliases' => '',
			'column_order' => '',
			'state_source' => Widget::STATE_SOURCE_TRIGGER_FIRST,
			'item_thresholds' => '',
			'threshold_direction' => Widget::THRESHOLD_ASCENDING,
			'warning_threshold' => '70',
			'high_threshold' => '85',
			'critical_threshold' => '95',
			'color_ok' => '4bb476',
			'color_info' => '5d86bb',
			'color_warning' => 'd9a24a',
			'color_high' => 'ea8d3a',
			'color_critical' => 'd35353',
			'color_missing' => '7f8792',
			'ok_text' => 'running,up,ok,healthy,1',
			'warning_text' => 'warning,degraded',
			'critical_text' => 'stopped,down,critical,failed,fail,error,0',
			'missing_label' => _('No item')
		];

		return array_replace($defaults, $this->fields_values);
	}

	private function getHosts(array $fields): array {
		$options = [
			'output' => ['hostid', 'name', 'host', 'maintenance_status', 'maintenance_type', 'maintenanceid', 'maintenance_from'],
			'monitored_hosts' => true,
			'preservekeys' => true,
			'sortfield' => 'name',
			'sortorder' => $fields['host_order'] == Widget::ORDER_NAME_DESC ? ZBX_SORT_DOWN : ZBX_SORT_UP
		];

		if ($fields['groupids']) {
			$options['groupids'] = $fields['groupids'];
		}

		if ($fields['hostids']) {
			$options['hostids'] = $fields['hostids'];
		}

		$db_hosts = API::Host()->get($options);

		if (!$db_hosts) {
			return [];
		}

		$maintenance_details = $this->getMaintenanceDetails($db_hosts);
		$hosts = [];

		foreach ($db_hosts as $db_host) {
			$maintenance = (int) $db_host['maintenance_status'] === HOST_MAINTENANCE_STATUS_ON;
			$maintenanceid = (string) ($db_host['maintenanceid'] ?? '');

			if (!$fields['show_maintenance'] && $maintenance) {
				continue;
			}

			$maintenance_info = $maintenance && $maintenanceid !== ''
				? ($maintenance_details[$maintenanceid] ?? null)
				: null;

			$hosts[$db_host['hostid']] = [
				'hostid' => (string) $db_host['hostid'],
				'label' => $db_host['name'] !== '' ? $db_host['name'] : $db_host['host'],
				'maintenance' => $maintenance
					? [
						'id' => $maintenanceid,
						'type' => (int) $db_host['maintenance_type'],
						'from' => (int) ($db_host['maintenance_from'] ?? 0),
						'name' => $maintenance_info['name'] ?? _('Maintenance'),
						'active_till' => (int) ($maintenance_info['active_till'] ?? 0)
					]
					: null
			];
		}

		return array_slice($hosts, 0, max(1, (int) $fields['limit_hosts']), true);
	}

	private function getMaintenanceDetails(array $db_hosts): array {
		$maintenanceids = [];

		foreach ($db_hosts as $db_host) {
			if ((int) ($db_host['maintenance_status'] ?? HOST_MAINTENANCE_STATUS_OFF) !== HOST_MAINTENANCE_STATUS_ON) {
				continue;
			}

			$maintenanceid = (string) ($db_host['maintenanceid'] ?? '');

			if ($maintenanceid !== '') {
				$maintenanceids[] = $maintenanceid;
			}
		}

		$maintenanceids = array_values(array_unique($maintenanceids));

		if (!$maintenanceids) {
			return [];
		}

		$db_maintenances = API::Maintenance()->get([
			'output' => ['maintenanceid', 'name', 'active_since', 'active_till', 'maintenance_type'],
			'maintenanceids' => $maintenanceids,
			'preservekeys' => false
		]);

		if (!$db_maintenances) {
			return [];
		}

		$maintenances = [];

		foreach ($db_maintenances as $db_maintenance) {
			$maintenances[(string) $db_maintenance['maintenanceid']] = $db_maintenance;
		}

		return $maintenances;
	}

	private function getReferenceItems(array $fields): array {
		if (!$fields['itemids']) {
			return [];
		}

		$column_aliases = $this->parseColumnAliases($fields['column_aliases']);

		$db_items = API::Item()->get([
			'output' => ['itemid', 'name', 'key_', 'units', 'value_type'],
			'itemids' => $fields['itemids'],
			'preservekeys' => false
		]);

		if (!$db_items) {
			return [];
		}

		$columns = [];

		foreach ($db_items as $db_item) {
			$column_id = (string) $db_item['itemid'];
			$full_label = $db_item['name'];
			$display_label = $column_aliases[$this->normalizeItemKey($db_item['key_'])] ?? $full_label;

			$columns[] = [
				'id' => $column_id,
				'label' => $display_label,
				'full_label' => $full_label,
				'key_' => $db_item['key_'],
				'units' => $db_item['units'],
				'value_type' => (int) $db_item['value_type'],
				'thresholds' => null
			];
		}

		$this->sortColumns($columns, $fields['column_order']);

		return $columns;
	}

	private function buildMatrix(array $hosts, array $columns, array $fields): array {
		$matrix = [
			'rows' => [],
			'columns' => $columns,
			'legend' => $this->getLegend(),
			'warnings' => [],
			'empty_state' => _('Select one or more items to build the matrix.'),
			'thresholds' => $this->getGlobalThresholds($fields)
		];

		if (!$columns) {
			return $matrix;
		}

		if (!$hosts) {
			$matrix['empty_state'] = _('No visible hosts match the widget filters.');

			return $matrix;
		}

		$items_by_key = $this->loadItemsForHosts($hosts, $columns);
		$active_triggers_by_itemid = $this->loadActiveTriggersForItems($items_by_key);
		$item_thresholds = $this->parseItemThresholds($fields['item_thresholds']);
		$hosts = $this->filterHostsWithVisibleItems($hosts, $columns, $items_by_key);

		if (!$hosts) {
			$matrix['empty_state'] = _('No hosts have any of the selected items.');

			return $matrix;
		}

		foreach ($hosts as $hostid => $host) {
			$row = [
				'hostid' => $hostid,
				'label' => $host['label'],
				'maintenance' => $host['maintenance'],
				'cells' => []
			];

			foreach ($columns as $column) {
				$item = $items_by_key[$column['key_']][$hostid] ?? null;
				$itemid = $item !== null ? (string) $item['itemid'] : null;
				$row['cells'][$column['id']] = $this->buildCell(
					$column,
					$item,
					$itemid !== null ? ($active_triggers_by_itemid[$itemid] ?? null) : null,
					$fields,
					$column['thresholds'] ?? $this->findConfigByItemKey($item_thresholds, $column['key_'])
				);
			}

			$matrix['rows'][] = $row;
		}

		if (!$items_by_key) {
			$matrix['warnings'][] = _('No matching items were found for the selected item keys on the filtered hosts.');
			$matrix['empty_state'] = _('No matching items were found for the selected item keys on the filtered hosts.');
		}

		$matrix['rows'] = array_filter($matrix['rows'], static function(array $row): bool {
			foreach ($row['cells'] as $cell) {
				if ($cell['state'] !== 'missing') {
					return true;
				}
			}

			return false;
		});

		if (!$matrix['rows']) {
			$matrix['empty_state'] = _('No hosts have the configured items.');
		}

		return $matrix;
	}

	private function filterHostsWithVisibleItems(array $hosts, array $columns, array $items_by_key): array {
		$filtered_hosts = [];

		foreach ($hosts as $hostid => $host) {
			foreach ($columns as $column) {
				if (isset($items_by_key[$column['key_']][$hostid])) {
					$filtered_hosts[$hostid] = $host;
					break;
				}
			}
		}

		return $filtered_hosts;
	}

	private function loadActiveTriggersForItems(array $items_by_key): array {
		$itemids = [];

		foreach ($items_by_key as $items_by_host) {
			foreach ($items_by_host as $item) {
				$itemids[] = $item['itemid'];
			}
		}

		$itemids = array_values(array_unique($itemids));

		if (!$itemids) {
			return [];
		}

		$db_triggers = API::Trigger()->get([
			'output' => ['triggerid', 'description', 'priority', 'value'],
			'itemids' => $itemids,
			'filter' => ['value' => TRIGGER_VALUE_TRUE],
			'selectItems' => ['itemid'],
			'monitored' => true,
			'preservekeys' => false
		]);

		if (!$db_triggers) {
			return [];
		}

		$triggers_by_itemid = [];

		foreach ($db_triggers as $db_trigger) {
			foreach ($db_trigger['items'] ?? [] as $db_item) {
				$itemid = (string) $db_item['itemid'];

				if (!isset($triggers_by_itemid[$itemid])
						|| (int) $db_trigger['priority'] > $triggers_by_itemid[$itemid]['severity']) {
					$triggers_by_itemid[$itemid] = [
						'severity' => (int) $db_trigger['priority'],
						'description' => $db_trigger['description']
					];
				}
			}
		}

		return $triggers_by_itemid;
	}

	private function loadItemsForHosts(array $hosts, array $columns): array {
		$items_by_key = [];
		$hostids = array_keys($hosts);

		foreach ($columns as $column) {
			$db_items = API::Item()->get([
				'output' => ['itemid', 'hostid', 'name', 'key_', 'lastvalue', 'lastclock', 'units', 'value_type', 'state', 'status'],
				'hostids' => $hostids,
				'filter' => ['key_' => $column['key_']],
				'monitored' => true,
				'preservekeys' => false
			]);

			if (!$db_items) {
				continue;
			}

			foreach ($db_items as $db_item) {
				$items_by_key[$column['key_']][(string) $db_item['hostid']] = $db_item;
			}
		}

		return $items_by_key;
	}

	private function buildCell(array $column, ?array $item, ?array $active_trigger, array $fields, ?array $column_thresholds): array {
		if ($item === null) {
			return [
				'label' => $fields['missing_label'],
				'state' => 'missing',
				'tooltip' => sprintf('%s: %s', $column['label'], $fields['missing_label']),
				'icon_class' => 'missing'
			];
		}

		$value = $item['lastvalue'] !== '' ? $item['lastvalue'] : null;
		$label = $this->formatValue($value, $column['units']);
		$state = $this->evaluateState($value, $fields, $active_trigger, $column_thresholds);
		$tooltip = $item['name'];

		if ($value !== null) {
			$tooltip .= "\n".sprintf('%s: %s', _('Last value'), $label);
		}

		if ($active_trigger !== null) {
			$tooltip .= "\n".sprintf('%s: %s', _('Trigger'), $active_trigger['description']);
		}

		if ((int) $item['lastclock'] > 0) {
			$tooltip .= "\n".sprintf('%s: %s', _('Updated'), zbx_date2str(DATE_TIME_FORMAT_SECONDS, (int) $item['lastclock']));
		}

		return [
			'label' => $label,
			'state' => $state,
			'tooltip' => $tooltip,
			'icon_class' => $this->getStateIconClass($state)
		];
	}

	private function evaluateState(?string $value, array $fields, ?array $active_trigger, ?array $column_thresholds): string {
		if ($value === null || $value === '') {
			return 'missing';
		}

		if ((int) $fields['state_source'] === Widget::STATE_SOURCE_TRIGGER_FIRST && $active_trigger !== null) {
			return $this->mapTriggerSeverityToState($active_trigger['severity']);
		}

		if (is_numeric($value)) {
			return $this->evaluateNumericState((float) $value, $fields, $column_thresholds);
		}

		return $this->evaluateTextState($value, $fields);
	}

	private function evaluateNumericState(float $value, array $fields, ?array $column_thresholds): string {
		$thresholds = $column_thresholds ?? $this->getGlobalThresholds($fields);
		$warning = $thresholds['warning'];
		$high = $thresholds['high'];
		$critical = $thresholds['critical'];
		$descending = (int) $thresholds['direction'] === Widget::THRESHOLD_DESCENDING;

		if ($descending) {
			if ($critical !== null && $value <= $critical) {
				return 'disaster';
			}

			if ($high !== null && $value <= $high) {
				return 'high';
			}

			if ($warning !== null && $value <= $warning) {
				return 'warning';
			}

			return 'ok';
		}

		if ($critical !== null && $value >= $critical) {
			return 'disaster';
		}

		if ($high !== null && $value >= $high) {
			return 'high';
		}

		if ($warning !== null && $value >= $warning) {
			return 'warning';
		}

		return 'ok';
	}

	private function evaluateTextState(string $value, array $fields): string {
		$normalized = mb_strtolower(trim($value));

		if ($this->matchesAnyPattern($normalized, $fields['critical_text'])) {
			return 'disaster';
		}

		if ($this->matchesAnyPattern($normalized, $fields['warning_text'])) {
			return 'warning';
		}

		if ($this->matchesAnyPattern($normalized, $fields['ok_text'])) {
			return 'ok';
		}

		return 'info';
	}

	private function mapTriggerSeverityToState(int $severity): string {
		switch ($severity) {
			case TRIGGER_SEVERITY_WARNING:
				return 'warning';
			case TRIGGER_SEVERITY_AVERAGE:
				return 'high';
			case TRIGGER_SEVERITY_HIGH:
			case TRIGGER_SEVERITY_DISASTER:
				return 'disaster';
			case TRIGGER_SEVERITY_NOT_CLASSIFIED:
			case TRIGGER_SEVERITY_INFORMATION:
			default:
				return 'info';
		}
	}

	private function matchesAnyPattern(string $value, string $patterns): bool {
		foreach (array_filter(array_map('trim', explode(',', mb_strtolower($patterns)))) as $pattern) {
			if ($pattern !== '' && mb_strpos($value, $pattern) !== false) {
				return true;
			}
		}

		return false;
	}

	private function formatValue(?string $value, string $units): string {
		if ($value === null || $value === '') {
			return _('No data');
		}

		return $units !== '' ? $value.' '.$units : $value;
	}

	private function parseNullableNumber($value): ?float {
		if ($value === null || $value === '') {
			return null;
		}

		return is_numeric($value) ? (float) $value : null;
	}

	private function getGlobalThresholds(array $fields): array {
		return [
			'direction' => (int) $fields['threshold_direction'],
			'warning' => $this->parseNullableNumber($fields['warning_threshold']),
			'high' => $this->parseNullableNumber($fields['high_threshold']),
			'critical' => $this->parseNullableNumber($fields['critical_threshold'])
		];
	}

	private function parseItemThresholds(string $raw_thresholds): array {
		$result = [];
		$lines = preg_split('/\r\n|\r|\n/', trim($raw_thresholds));

		if (!$lines) {
			return [];
		}

		foreach ($lines as $line) {
			$line = trim($line);

			if ($line === '') {
				continue;
			}

			$parts = array_map('trim', explode('|', $line));

			if (count($parts) < 5 || $parts[0] === '') {
				continue;
			}

			$result[$this->normalizeItemKey($parts[0])] = [
				'direction' => $this->parseDirection($parts[1]),
				'warning' => $this->parseNullableNumber($parts[2]),
				'high' => $this->parseNullableNumber($parts[3]),
				'critical' => $this->parseNullableNumber($parts[4])
			];
		}

		return $result;
	}

	private function parseColumnAliases(string $raw_aliases): array {
		$result = [];
		$lines = preg_split('/\r\n|\r|\n/', trim($raw_aliases));

		if (!$lines) {
			return [];
		}

		foreach ($lines as $line) {
			$line = trim($line);

			if ($line === '') {
				continue;
			}

			$parts = array_map('trim', explode('|', $line, 2));

			if (count($parts) < 2 || $parts[0] === '' || $parts[1] === '') {
				continue;
			}

			$result[$this->normalizeItemKey($parts[0])] = $parts[1];
		}

		return $result;
	}

	private function sortColumns(array &$columns, string $raw_order): void {
		$order_map = [];
		$lines = preg_split('/\r\n|\r|\n/', trim($raw_order));

		if (!$lines) {
			return;
		}

		$position = 0;

		foreach ($lines as $line) {
			$line = trim($line);

			if ($line === '') {
				continue;
			}

			$order_map[$this->normalizeItemKey($line)] = $position++;
		}

		if (!$order_map) {
			return;
		}

		foreach ($columns as $index => &$column) {
			$key_match = $this->normalizeItemKey($column['key_']);
			$alias_match = $this->normalizeItemKey($column['label']);

			$column['_sort_index'] = $index;
			$column['_sort_priority'] = $order_map[$alias_match] ?? $order_map[$key_match] ?? (100000 + $index);
		}
		unset($column);

		usort($columns, static function(array $left, array $right): int {
			if ($left['_sort_priority'] === $right['_sort_priority']) {
				return $left['_sort_index'] <=> $right['_sort_index'];
			}

			return $left['_sort_priority'] <=> $right['_sort_priority'];
		});

		foreach ($columns as &$column) {
			unset($column['_sort_index'], $column['_sort_priority']);
		}
		unset($column);
	}

	private function findConfigByItemKey(array $config, string $item_key): ?array {
		$normalized_key = $this->normalizeItemKey($item_key);

		return $config[$normalized_key] ?? null;
	}

	private function normalizeItemKey(string $item_key): string {
		$item_key = trim($item_key);
		$item_key = preg_replace('/\s+/', '', $item_key) ?? $item_key;
		$item_key = str_replace(['"', "'"], '', $item_key);

		return mb_strtolower($item_key);
	}

	private function parseDirection(string $direction): int {
		$direction = strtolower(trim($direction));

		if (in_array($direction, ['desc', 'down', 'descending', 'lower'], true)) {
			return Widget::THRESHOLD_DESCENDING;
		}

		return Widget::THRESHOLD_ASCENDING;
	}

	private function getLegend(): array {
		return [
			['state' => 'ok', 'label' => _('OK')],
			['state' => 'info', 'label' => _('Info')],
			['state' => 'warning', 'label' => _('Warning')],
			['state' => 'high', 'label' => _('High')],
			['state' => 'disaster', 'label' => _('Critical')],
			['state' => 'missing', 'label' => _('Missing item')]
		];
	}

	private function getStateIconClass(string $state): string {
		return 'matrix-view__icon--'.$state;
	}
}
