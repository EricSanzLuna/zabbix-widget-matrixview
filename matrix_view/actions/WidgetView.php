<?php

namespace Modules\MatrixView\Actions;

use API;
use CControllerDashboardWidgetView;
use CControllerResponseData;
use Modules\MatrixView\Widget;

class WidgetView extends CControllerDashboardWidgetView {

	private const PROBLEM_TAG_OPERATOR_EQUALS = 0;

	protected function doAction(): void {
		$fields = $this->getNormalizedFields();
		$hosts = $this->getHosts($fields);

		$data = [
			'name' => $this->getInput('name', $this->widget->getName()),
			'fields_values' => $fields,
			'matrix' => $fields['source_mode'] == Widget::SOURCE_PROBLEMS
				? $this->buildProblemsMatrix($hosts, $fields)
				: $this->buildLatestDataMatrix($hosts, $fields),
			'user' => [
				'debug_mode' => $this->getDebugMode()
			]
		];

		$this->setResponse(new CControllerResponseData($data));
	}

	private function getNormalizedFields(): array {
		$defaults = [
			'source_mode' => Widget::SOURCE_PROBLEMS,
			'groupids' => [],
			'hostids' => [],
			'host_order' => Widget::ORDER_NAME_ASC,
			'limit_hosts' => 25,
			'limit_columns' => 20,
			'visual_mode' => Widget::VISUAL_COMPACT,
			'tag_key' => 'matrix',
			'problem_severities' => [],
			'problem_ack_filter' => Widget::FILTER_ALL,
			'problem_suppressed_filter' => Widget::FILTER_ALL,
			'problem_maintenance_filter' => Widget::FILTER_ALL,
			'column_order' => '',
			'show_problem_count' => 1,
			'latest_columns' => '',
			'latest_default_direction' => Widget::LATEST_ASCENDING,
			'missing_label' => _('No item')
		];

		return array_replace($defaults, $this->fields_values);
	}

	private function getHosts(array $fields): array {
		$options = [
			'output' => ['hostid', 'name', 'host', 'maintenance_status', 'status'],
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
			$maintenance_on = (int) $db_host['maintenance_status'] === HOST_MAINTENANCE_STATUS_ON;

			if ($fields['problem_maintenance_filter'] == Widget::FILTER_NO && $maintenance_on) {
				continue;
			}

			if ($fields['problem_maintenance_filter'] == Widget::FILTER_YES && !$maintenance_on) {
				continue;
			}

			$hosts[$db_host['hostid']] = [
				'hostid' => (string) $db_host['hostid'],
				'name' => $db_host['name'] !== '' ? $db_host['name'] : $db_host['host'],
				'maintenance' => $maintenance_on
			];
		}

		return array_slice($hosts, 0, max(1, (int) $fields['limit_hosts']), true);
	}

	private function buildProblemsMatrix(array $hosts, array $fields): array {
		$matrix = [
			'mode' => Widget::SOURCE_PROBLEMS,
			'rows' => [],
			'columns' => [],
			'legend' => $this->getLegend(Widget::SOURCE_PROBLEMS),
			'states' => $this->getStateLabels(),
			'warnings' => [],
			'empty_state' => _('No active problems matched the selected hosts and filters.')
		];

		if (!$hosts) {
			$matrix['empty_state'] = _('No visible hosts match the widget filters.');

			return $matrix;
		}

		$options = [
			'output' => ['eventid', 'name', 'severity', 'acknowledged', 'clock', 'suppressed'],
			'hostids' => array_keys($hosts),
			'selectHosts' => ['hostid', 'name'],
			'selectTags' => ['tag', 'value'],
			'preservekeys' => false,
			'sortfield' => ['severity', 'clock'],
			'sortorder' => [ZBX_SORT_DOWN, ZBX_SORT_DOWN]
		];

		if ($fields['problem_severities']) {
			$options['severities'] = $fields['problem_severities'];
		}

		if ($fields['problem_ack_filter'] != Widget::FILTER_ALL) {
			$options['acknowledged'] = $fields['problem_ack_filter'] == Widget::FILTER_YES;
		}

		if ($fields['problem_suppressed_filter'] != Widget::FILTER_ALL) {
			$options['suppressed'] = $fields['problem_suppressed_filter'] == Widget::FILTER_YES;
		}

		$db_problems = API::Problem()->get($options);
		$columns = [];
		$rows = [];

		foreach ($hosts as $hostid => $host) {
			$rows[$hostid] = [
				'hostid' => $hostid,
				'label' => $host['name'],
				'maintenance' => $host['maintenance'],
				'cells' => []
			];
		}

		foreach ($db_problems as $problem) {
			$column_value = $this->extractProblemColumn($problem, $fields['tag_key']);

			if ($column_value === null || !isset($problem['hosts'])) {
				continue;
			}

			foreach ($problem['hosts'] as $problem_host) {
				$hostid = (string) $problem_host['hostid'];

				if (!array_key_exists($hostid, $rows)) {
					continue;
				}

				if (!array_key_exists($column_value, $columns)) {
					$columns[$column_value] = [
						'id' => $column_value,
						'label' => $column_value
					];
				}

				if (!isset($rows[$hostid]['cells'][$column_value])) {
					$rows[$hostid]['cells'][$column_value] = $this->createEmptyProblemCell($hostid, $column_value);
				}

				$rows[$hostid]['cells'][$column_value]['count']++;
				$rows[$hostid]['cells'][$column_value]['severity'] = max(
					$rows[$hostid]['cells'][$column_value]['severity'],
					(int) $problem['severity']
				);
				$rows[$hostid]['cells'][$column_value]['clock'] = max(
					$rows[$hostid]['cells'][$column_value]['clock'],
					(int) $problem['clock']
				);
				$rows[$hostid]['cells'][$column_value]['problems'][] = [
					'eventid' => (string) $problem['eventid'],
					'name' => $problem['name'],
					'severity' => (int) $problem['severity'],
					'clock' => (int) $problem['clock'],
					'acknowledged' => (int) $problem['acknowledged'],
					'suppressed' => (int) $problem['suppressed']
				];
			}
		}

		$ordered_columns = $this->orderProblemColumns(array_values($columns), $fields);
		$matrix['columns'] = array_slice($ordered_columns, 0, max(1, (int) $fields['limit_columns']));
		$matrix['rows'] = $this->finalizeProblemRows(array_values($rows), $matrix['columns'], $fields);

		if (!$matrix['columns']) {
			$matrix['empty_state'] = _('No columns could be derived from the configured tag key.');
		}

		return $matrix;
	}

	private function buildLatestDataMatrix(array $hosts, array $fields): array {
		$matrix = [
			'mode' => Widget::SOURCE_LATEST_DATA,
			'rows' => [],
			'columns' => [],
			'legend' => $this->getLegend(Widget::SOURCE_LATEST_DATA),
			'states' => $this->getStateLabels(),
			'warnings' => [],
			'empty_state' => _('No items matched the configured latest data columns.')
		];

		if (!$hosts) {
			$matrix['empty_state'] = _('No visible hosts match the widget filters.');

			return $matrix;
		}

		$columns = $this->parseLatestColumns($fields['latest_columns'], (int) $fields['latest_default_direction']);

		if (!$columns) {
			$matrix['warnings'][] = _('Latest data mode requires at least one column definition.');
			$matrix['empty_state'] = _('Add one or more latest data columns using the format Label|pattern|direction|warn|high|critical.');

			return $matrix;
		}

		$columns = array_slice($columns, 0, max(1, (int) $fields['limit_columns']));
		$matrix['columns'] = $columns;
		$items_by_column = $this->loadLatestItemsByColumn($hosts, $columns);

		foreach ($hosts as $hostid => $host) {
			$row = [
				'hostid' => $hostid,
				'label' => $host['name'],
				'maintenance' => $host['maintenance'],
				'cells' => []
			];

			foreach ($columns as $column) {
				$item = $items_by_column[$column['id']][$hostid] ?? null;
				$row['cells'][$column['id']] = $this->buildLatestCell($hostid, $column, $item, $fields['missing_label']);
			}

			$matrix['rows'][] = $row;
		}

		if (!$items_by_column) {
			$matrix['warnings'][] = _('No items were returned by the API for the configured patterns.');
		}

		return $matrix;
	}

	private function parseLatestColumns(string $raw_columns, int $default_direction): array {
		$columns = [];
		$lines = preg_split('/\r\n|\r|\n/', trim($raw_columns));

		if (!$lines) {
			return [];
		}

		foreach ($lines as $index => $line) {
			$line = trim($line);

			if ($line === '') {
				continue;
			}

			$parts = array_map('trim', explode('|', $line));
			$label = $parts[0] ?? '';
			$pattern = $parts[1] ?? $label;

			if ($label === '' || $pattern === '') {
				continue;
			}

			$columns[] = [
				'id' => 'col_'.$index,
				'label' => $label,
				'pattern' => $pattern,
				'direction' => $this->parseDirection($parts[2] ?? '', $default_direction),
				'warn' => $this->parseNullableNumber($parts[3] ?? null),
				'high' => $this->parseNullableNumber($parts[4] ?? null),
				'critical' => $this->parseNullableNumber($parts[5] ?? null)
			];
		}

		return $columns;
	}

	private function loadLatestItemsByColumn(array $hosts, array $columns): array {
		$result = [];
		$hostids = array_keys($hosts);

		foreach ($columns as $column) {
			$db_items = API::Item()->get([
				'output' => ['itemid', 'hostid', 'name', 'key_', 'lastvalue', 'lastclock', 'value_type', 'units', 'state', 'status'],
				'hostids' => $hostids,
				'monitored' => true,
				'search' => [
					'key_' => $column['pattern'],
					'name' => $column['pattern']
				],
				'searchByAny' => true,
				'searchWildcardsEnabled' => true,
				'sortfield' => ['name'],
				'sortorder' => ZBX_SORT_UP,
				'preservekeys' => false
			]);

			if (!$db_items) {
				continue;
			}

			foreach ($db_items as $db_item) {
				$hostid = (string) $db_item['hostid'];
				$current = $result[$column['id']][$hostid] ?? null;

				if ($current === null || $this->getItemMatchScore($db_item, $column['pattern']) > $this->getItemMatchScore($current, $column['pattern'])) {
					$result[$column['id']][$hostid] = $db_item;
				}
			}
		}

		return $result;
	}

	private function buildLatestCell(string $hostid, array $column, ?array $item, string $missing_label): array {
		if ($item === null) {
			return [
				'hostid' => $hostid,
				'column_id' => $column['id'],
				'label' => $missing_label,
				'value' => null,
				'state' => 'missing',
				'lastclock' => null,
				'tooltip' => $missing_label,
				'link' => null,
				'detail' => [
					'type' => 'latest_data',
					'hostid' => $hostid,
					'column' => $column['label'],
					'pattern' => $column['pattern']
				]
			];
		}

		$raw_value = $item['lastvalue'] !== '' ? $item['lastvalue'] : null;
		$state = $this->evaluateLatestState($raw_value, $column);
		$label = $raw_value !== null ? $raw_value : $missing_label;
		$tooltip = sprintf('%s: %s', $item['name'], $label);

		if ((int) $item['lastclock'] > 0) {
			$tooltip .= "\n".sprintf('%s: %s', _('Updated'), zbx_date2str(DATE_TIME_FORMAT_SECONDS, (int) $item['lastclock']));
		}

		return [
			'hostid' => $hostid,
			'column_id' => $column['id'],
			'label' => $label,
			'value' => $raw_value,
			'state' => $state,
			'lastclock' => (int) $item['lastclock'],
			'tooltip' => $tooltip,
			'link' => null,
			'detail' => [
				'type' => 'latest_data',
				'hostid' => $hostid,
				'itemid' => (string) $item['itemid'],
				'column' => $column['label'],
				'pattern' => $column['pattern']
			]
		];
	}

	private function createEmptyProblemCell(string $hostid, string $column_value): array {
		return [
			'hostid' => $hostid,
			'column_id' => $column_value,
			'label' => '',
			'count' => 0,
			'severity' => -1,
			'clock' => 0,
			'state' => 'ok',
			'tooltip' => _('No active problems'),
			'link' => null,
			'problems' => [],
			'detail' => [
				'type' => 'problems',
				'hostid' => $hostid,
				'tag_value' => $column_value
			]
		];
	}

	private function finalizeProblemRows(array $rows, array $columns, array $fields): array {
		$column_ids = array_column($columns, 'id');

		foreach ($rows as &$row) {
			$cells = [];

			foreach ($column_ids as $column_id) {
				$cell = $row['cells'][$column_id] ?? $this->createEmptyProblemCell($row['hostid'], $column_id);

				if ($cell['count'] > 0) {
					$cell['state'] = $this->getSeverityState($cell['severity']);
					$cell['label'] = $fields['show_problem_count']
						? sprintf('%s (%d)', $this->getSeverityShortLabel($cell['severity']), $cell['count'])
						: $this->getSeverityShortLabel($cell['severity']);
					$cell['tooltip'] = $this->buildProblemTooltip($cell);
					$cell['link'] = $this->buildProblemsUrl($row['hostid'], $fields['tag_key'], $column_id);
				}
				else {
					$cell['label'] = _('OK');
				}

				$cells[$column_id] = $cell;
			}

			$row['cells'] = $cells;
		}
		unset($row);

		return $rows;
	}

	private function buildProblemTooltip(array $cell): string {
		$tooltip = sprintf('%s: %d', _('Active problems'), $cell['count']);

		if ($cell['clock'] > 0) {
			$tooltip .= "\n".sprintf('%s: %s', _('Last event'), zbx_date2str(DATE_TIME_FORMAT_SECONDS, $cell['clock']));
		}

		foreach (array_slice($cell['problems'], 0, 5) as $problem) {
			$tooltip .= "\n".sprintf('[%s] %s', $this->getSeverityShortLabel($problem['severity']), $problem['name']);
		}

		return $tooltip;
	}

	private function extractProblemColumn(array $problem, string $tag_key): ?string {
		if (!isset($problem['tags'])) {
			return null;
		}

		foreach ($problem['tags'] as $tag) {
			if ($tag['tag'] === $tag_key && $tag['value'] !== '') {
				return $tag['value'];
			}
		}

		return null;
	}

	private function orderProblemColumns(array $columns, array $fields): array {
		$preferred = array_filter(array_map('trim', explode(',', $fields['column_order'])));

		if ($preferred) {
			$order_map = array_flip($preferred);

			usort($columns, static function(array $left, array $right) use ($order_map): int {
				$left_pos = $order_map[$left['label']] ?? PHP_INT_MAX;
				$right_pos = $order_map[$right['label']] ?? PHP_INT_MAX;

				if ($left_pos === $right_pos) {
					return strcasecmp($left['label'], $right['label']);
				}

				return $left_pos <=> $right_pos;
			});

			return $columns;
		}

		usort($columns, static function(array $left, array $right): int {
			return strcasecmp($left['label'], $right['label']);
		});

		return $columns;
	}

	private function buildProblemsUrl(string $hostid, string $tag_key, string $tag_value): string {
		$query = http_build_query([
			'action' => 'problem.view',
			'filter_set' => 1,
			'hostids' => [$hostid],
			'tags' => [[
				'tag' => $tag_key,
				'operator' => self::PROBLEM_TAG_OPERATOR_EQUALS,
				'value' => $tag_value
			]]
		]);

		return 'zabbix.php?'.$query;
	}

	private function getLegend(int $mode): array {
		if ($mode == Widget::SOURCE_PROBLEMS) {
			return [
				['state' => 'ok', 'label' => _('OK')],
				['state' => 'info', 'label' => _('Information')],
				['state' => 'warning', 'label' => _('Warning')],
				['state' => 'average', 'label' => _('Average')],
				['state' => 'high', 'label' => _('High')],
				['state' => 'disaster', 'label' => _('Disaster')]
			];
		}

		return [
			['state' => 'ok', 'label' => _('Normal')],
			['state' => 'warning', 'label' => _('Warning threshold')],
			['state' => 'high', 'label' => _('High threshold')],
			['state' => 'disaster', 'label' => _('Critical threshold')],
			['state' => 'missing', 'label' => _('Missing item')]
		];
	}

	private function getStateLabels(): array {
		return [
			'ok' => _('OK'),
			'info' => _('Info'),
			'warning' => _('Warning'),
			'average' => _('Average'),
			'high' => _('High'),
			'disaster' => _('Disaster'),
			'missing' => _('No item')
		];
	}

	private function getSeverityState(int $severity): string {
		switch ($severity) {
			case TRIGGER_SEVERITY_NOT_CLASSIFIED:
				return 'info';
			case TRIGGER_SEVERITY_INFORMATION:
				return 'info';
			case TRIGGER_SEVERITY_WARNING:
				return 'warning';
			case TRIGGER_SEVERITY_AVERAGE:
				return 'average';
			case TRIGGER_SEVERITY_HIGH:
				return 'high';
			case TRIGGER_SEVERITY_DISASTER:
				return 'disaster';
		}

		return 'ok';
	}

	private function getSeverityShortLabel(int $severity): string {
		switch ($severity) {
			case TRIGGER_SEVERITY_NOT_CLASSIFIED:
				return _('NC');
			case TRIGGER_SEVERITY_INFORMATION:
				return _('Info');
			case TRIGGER_SEVERITY_WARNING:
				return _('Warn');
			case TRIGGER_SEVERITY_AVERAGE:
				return _('Avg');
			case TRIGGER_SEVERITY_HIGH:
				return _('High');
			case TRIGGER_SEVERITY_DISASTER:
				return _('Dis');
		}

		return _('OK');
	}

	private function evaluateLatestState(?string $value, array $column): string {
		if ($value === null || !is_numeric($value)) {
			return 'ok';
		}

		$numeric_value = (float) $value;
		$thresholds = [
			'warning' => $column['warn'],
			'high' => $column['high'],
			'disaster' => $column['critical']
		];

		if ($column['direction'] == Widget::LATEST_DESCENDING) {
			foreach (['disaster', 'high', 'warning'] as $state) {
				if ($thresholds[$state] !== null && $numeric_value <= $thresholds[$state]) {
					return $state;
				}
			}

			return 'ok';
		}

		foreach (['disaster', 'high', 'warning'] as $state) {
			if ($thresholds[$state] !== null && $numeric_value >= $thresholds[$state]) {
				return $state;
			}
		}

		return 'ok';
	}

	private function parseDirection(string $value, int $default_direction): int {
		$value = strtolower(trim($value));

		if (in_array($value, ['desc', 'down', 'descending', 'lower'], true)) {
			return Widget::LATEST_DESCENDING;
		}

		if (in_array($value, ['asc', 'up', 'ascending', 'higher'], true)) {
			return Widget::LATEST_ASCENDING;
		}

		return $default_direction;
	}

	private function parseNullableNumber(?string $value): ?float {
		if ($value === null) {
			return null;
		}

		$value = trim($value);

		if ($value === '' || !is_numeric($value)) {
			return null;
		}

		return (float) $value;
	}

	private function getItemMatchScore(array $item, string $pattern): int {
		$pattern = mb_strtolower($pattern);
		$name = mb_strtolower($item['name']);
		$key = mb_strtolower($item['key_']);

		if ($key === $pattern || $name === $pattern) {
			return 300;
		}

		if (strpos($key, $pattern) !== false) {
			return 200;
		}

		if (strpos($name, $pattern) !== false) {
			return 100;
		}

		return 0;
	}
}
