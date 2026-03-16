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

foreach ($matrix['warnings'] as $warning) {
	$wrapper->addItem((new CDiv($warning))->addClass('matrix-view__warning'));
}

$legend = (new CTag('ul', true))->addClass('matrix-view__legend');

foreach ($matrix['legend'] as $legend_item) {
	$legend->addItem(
		(new CTag('li', true, [
			(new CSpan())->addClass('matrix-view__legend-swatch matrix-view__cell--'.$legend_item['state']),
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
		(new CTag('th', true, _('Host')))->addClass('matrix-view__sticky-col matrix-view__sticky-head')
	);

	foreach ($matrix['columns'] as $column) {
		$header_row->addItem(
			(new CTag('th', true, $column['label']))->addClass('matrix-view__sticky-head')
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

		$table_row->addItem((new CTag('th', true, $host_label))->addClass('matrix-view__sticky-col'));

		foreach ($matrix['columns'] as $column) {
			$cell = $row['cells'][$column['id']];
			$cell_content = (new CSpan($cell['label']))->addClass('matrix-view__cell-label');
			$target = $cell['link'] !== null
				? (new CLink($cell_content, $cell['link']))
				: (new CTag('button', true, $cell_content));

			if ($cell['link'] === null) {
				$target->setAttribute('type', 'button');
			}

			$target
				->addClass('matrix-view__cell-action')
				->setAttribute('title', $cell['tooltip'])
				->setAttribute('data-detail', json_encode($cell['detail']) ?: '{}')
				->setAttribute('data-state', $cell['state']);

			$table_row->addItem(
				(new CTag('td', true, $target))
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
