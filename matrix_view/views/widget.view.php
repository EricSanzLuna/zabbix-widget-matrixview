<?php

/**
 * Matrix View widget presentation.
 *
 * @var CView $this
 * @var array $data
 */

$matrix = $data['matrix'];
$fields = $data['fields_values'];
$wrapper = (new CDiv())->addClass('matrix-view');
$wrapper->addClass($fields['visual_mode'] == \Modules\MatrixView\Widget::VISUAL_COMFORTABLE
	? 'matrix-view--comfortable'
	: 'matrix-view--compact'
);
$wrapper->addClass(match ((int) ($fields['header_orientation'] ?? \Modules\MatrixView\Widget::HEADER_DIAGONAL)) {
	\Modules\MatrixView\Widget::HEADER_HORIZONTAL => 'matrix-view--headers-horizontal',
	\Modules\MatrixView\Widget::HEADER_VERTICAL => 'matrix-view--headers-vertical',
	default => 'matrix-view--headers-diagonal'
});

$shorten_label = static function(string $label): string {
	if (preg_match('/"([^"]+)"/', $label, $matches) === 1) {
		return $matches[1];
	}

	$label = preg_replace('/^State of service\s*/i', '', $label) ?? $label;
	$label = preg_replace('/^Ping to\s*/i', '', $label) ?? $label;
	$label = preg_replace('/^Acceso\s*/i', '', $label) ?? $label;
	$label = preg_replace('/\s*\([^)]*\)\s*/', '', $label) ?? $label;
	$label = trim($label);

	if (mb_strlen($label) > 22) {
		return mb_substr($label, 0, 22).'...';
	}

	return $label;
};

foreach ($matrix['warnings'] as $warning) {
	$wrapper->addItem((new CDiv($warning))->addClass('matrix-view__warning'));
}

$legend = (new CTag('ul', true))->addClass('matrix-view__legend');

foreach ($matrix['legend'] as $legend_item) {
	$legend_icon = (new CSpan())->addClass('matrix-view__legend-symbol matrix-view__cell--'.$legend_item['state'])
		->setAttribute('aria-hidden', 'true');
	$legend->addItem(
		(new CTag('li', true, [
			$legend_icon,
			(new CSpan($legend_item['label']))->addClass('matrix-view__legend-label')
		]))->addClass('matrix-view__legend-item')
	);
}

$wrapper->addItem($legend);

if (!$matrix['columns'] || !$matrix['rows']) {
	$wrapper->addItem((new CDiv($matrix['empty_state']))->addClass('matrix-view__empty'));
}
else {
	$table_wrap = (new CDiv())->addClass('matrix-view__table-wrap');
	$table = (new CTag('table', true))->addClass('matrix-view__table');
	$thead = new CTag('thead', true);
	$header_row = new CTag('tr', true);
	$header_row->addItem(
		(new CTag('th', true, _('Host Name')))->addClass('matrix-view__sticky-col matrix-view__sticky-head matrix-view__host-head')
	);

	foreach ($matrix['columns'] as $column) {
		$column_title = $column['label'];

		if (($column['full_label'] ?? $column['label']) !== $column['label']) {
			$column_title = $column['label']."\n".$column['full_label'];
		}

		$short_label = $shorten_label($column['label']);

		$header_row->addItem(
			(new CTag('th', true,
				(new CSpan($short_label))
					->addClass('matrix-view__column-label')
					->setAttribute('title', $column_title)
			))->addClass('matrix-view__sticky-head matrix-view__column-head')
		);
	}

	$thead->addItem($header_row);
	$table->addItem($thead);
	$tbody = new CTag('tbody', true);

	foreach ($matrix['rows'] as $row) {
		$table_row = new CTag('tr', true);
		$host_label = $row['maintenance']
			? $row['label'].' ('._('maintenance').')'
			: $row['label'];
		$host_link = (new CLinkAction($host_label))
			->addClass('matrix-view__host-link')
			->setAttribute('data-menu-popup', json_encode([
				'type' => 'host',
				'data' => [
					'hostid' => $row['hostid']
				]
			]))
			->setAttribute('aria-haspopup', 'true')
			->setAttribute('aria-expanded', 'false');

		$table_row->addItem((new CTag('th', true, $host_link))->addClass('matrix-view__sticky-col'));

		foreach ($matrix['columns'] as $column) {
			$cell = $row['cells'][$column['id']];
			$show_value = !in_array($cell['state'], ['ok', 'missing'], true);
			$cell_body = [
				(new CSpan())
					->addClass('matrix-view__icon matrix-view__cell--'.$cell['state'].' '.$cell['icon_class'])
					->setAttribute('aria-label', ucfirst($cell['state']))
					->setAttribute('role', 'img'),
				(new CSpan($show_value ? $cell['label'] : ''))
					->addClass('matrix-view__value')
			];

			$table_row->addItem(
				(new CTag('td', true,
					(new CTag('div', true, $cell_body))
						->addClass('matrix-view__cell-action')
						->setAttribute('title', $cell['tooltip'])
				))
					->addClass('matrix-view__cell matrix-view__cell--'.$cell['state'])
			);
		}

		$tbody->addItem($table_row);
	}

	$table->addItem($tbody);
	$table_wrap->addItem($table);
	$wrapper->addItem($table_wrap);
}

(new CWidgetView($data))
	->addItem($wrapper)
	->show();
