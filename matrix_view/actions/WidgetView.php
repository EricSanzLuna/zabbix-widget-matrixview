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
			'itemids' => [],
			'threshold_direction' => Widget::THRESHOLD_ASCENDING,
			'warning_threshold' => '70',
			'high_threshold' => '85',
			'critical_threshold' => '95',
			'ok_text' => 'running,up,ok,healthy,1',
			'warning_text' => 'warning,degraded',
			'critical_text' => 'stopped,down,critical,failed,fail,error,0',
			'missing_label' => _('No item')
		];

		return array_replace($defaults, $this->fields_values);
	}

	private function getHosts(array $fields): array {
		$options = [
			'output' => ['hostid', 'name', 'host', 'maintenance_status'],
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

		$hosts = [];

		foreach ($db_hosts as $db_host) {
			$maintenance = (int) $db_host['maintenance_status'] === HOST_MAINTENANCE_STATUS_ON;

			if (!$fields['show_maintenance'] && $maintenance) {
				continue;
			}

			$hosts[$db_host['hostid']] = [
				'hostid' => (string) $db_host['hostid'],
				'label' => $db_host['name'] !== '' ? $db_host['name'] : $db_host['host'],
				'maintenance' => $maintenance
			];
		}

		return array_slice($hosts, 0, max(1, (int) $fields['limit_hosts']), true);
	}

	private function getReferenceItems(array $fields): array {
		if (!$fields['itemids']) {
			return [];
		}

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

			$columns[] = [
				'id' => $column_id,
				'label' => $db_item['name'],
				'key_' => $db_item['key_'],
				'units' => $db_item['units'],
				'value_type' => (int) $db_item['value_type']
			];
		}

		return $columns;
	}

	private function buildMatrix(array $hosts, array $columns, array $fields): array {
		$matrix = [
			'rows' => [],
			'columns' => $columns,
			'legend' => $this->getLegend(),
			'warnings' => [],
			'empty_state' => _('Select one or more items to build the matrix.'),
			'thresholds' => [
				'direction' => (int) $fields['threshold_direction'],
				'warning' => $this->parseNullableNumber($fields['warning_threshold']),
				'high' => $this->parseNullableNumber($fields['high_threshold']),
				'critical' => $this->parseNullableNumber($fields['critical_threshold'])
			]
		];

		if (!$columns) {
			return $matrix;
		}

		if (!$hosts) {
			$matrix['empty_state'] = _('No visible hosts match the widget filters.');

			return $matrix;
		}

		$items_by_key = $this->loadItemsForHosts($hosts, $columns);

		foreach ($hosts as $hostid => $host) {
			$row = [
				'hostid' => $hostid,
				'label' => $host['label'],
				'maintenance' => $host['maintenance'],
				'cells' => []
			];

			foreach ($columns as $column) {
				$item = $items_by_key[$column['key_']][$hostid] ?? null;
				$row['cells'][$column['id']] = $this->buildCell($hostid, $column, $item, $fields);
			}

			$matrix['rows'][] = $row;
		}

		if (!$items_by_key) {
			$matrix['warnings'][] = _('No matching items were found for the selected item keys on the filtered hosts.');
			$matrix['empty_state'] = _('No matching items were found for the selected item keys on the filtered hosts.');
		}

		return $matrix;
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

	private function buildCell(string $hostid, array $column, ?array $item, array $fields): array {
		if ($item === null) {
			return [
				'label' => $fields['missing_label'],
				'state' => 'missing',
				'tooltip' => sprintf('%s: %s', $column['label'], $fields['missing_label'])
			];
		}

		$value = $item['lastvalue'] !== '' ? $item['lastvalue'] : null;
		$label = $this->formatValue($value, $column['units']);
		$state = $this->evaluateState($value, $fields);
		$tooltip = $item['name'];

		if ($value !== null) {
			$tooltip .= "\n".sprintf('%s: %s', _('Last value'), $label);
		}

		if ((int) $item['lastclock'] > 0) {
			$tooltip .= "\n".sprintf('%s: %s', _('Updated'), zbx_date2str(DATE_TIME_FORMAT_SECONDS, (int) $item['lastclock']));
		}

		return [
			'label' => $label,
			'state' => $state,
			'tooltip' => $tooltip
		];
	}

	private function evaluateState(?string $value, array $fields): string {
		if ($value === null || $value === '') {
			return 'missing';
		}

		if (is_numeric($value)) {
			return $this->evaluateNumericState((float) $value, $fields);
		}

		return $this->evaluateTextState($value, $fields);
	}

	private function evaluateNumericState(float $value, array $fields): string {
		$warning = $this->parseNullableNumber($fields['warning_threshold']);
		$high = $this->parseNullableNumber($fields['high_threshold']);
		$critical = $this->parseNullableNumber($fields['critical_threshold']);
		$descending = (int) $fields['threshold_direction'] === Widget::THRESHOLD_DESCENDING;

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

	private function getLegend(): array {
		return [
			['state' => 'ok', 'label' => _('OK')],
			['state' => 'info', 'label' => _('Text / neutral')],
			['state' => 'warning', 'label' => _('Warning')],
			['state' => 'high', 'label' => _('High')],
			['state' => 'disaster', 'label' => _('Critical')],
			['state' => 'missing', 'label' => _('Missing item')]
		];
	}
}
