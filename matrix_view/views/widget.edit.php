<?php

/**
 * Matrix View widget configuration.
 *
 * @var CView $this
 * @var array $data
 */

$source_mode = new CWidgetFieldSelectView($data['fields']['source_mode']);
$host_order = new CWidgetFieldSelectView($data['fields']['host_order']);
$visual_mode = new CWidgetFieldSelectView($data['fields']['visual_mode']);
$ack_filter = new CWidgetFieldSelectView($data['fields']['problem_ack_filter']);
$suppressed_filter = new CWidgetFieldSelectView($data['fields']['problem_suppressed_filter']);
$maintenance_filter = new CWidgetFieldSelectView($data['fields']['problem_maintenance_filter']);
$show_problem_count = new CWidgetFieldSelectView($data['fields']['show_problem_count']);
$latest_direction = new CWidgetFieldSelectView($data['fields']['latest_default_direction']);
$latest_columns = new CWidgetFieldTextAreaView($data['fields']['latest_columns']);
$problem_severities = new CWidgetFieldSeveritiesView($data['fields']['problem_severities']);
$tag_key = new CWidgetFieldTextBoxView($data['fields']['tag_key']);
$column_order = new CWidgetFieldTextBoxView($data['fields']['column_order']);
$missing_label = new CWidgetFieldTextBoxView($data['fields']['missing_label']);

$form = new CWidgetFormView($data);

$mode_row = static function($label, $field, string $class): array {
	return [
		$label->addClass($class),
		(new CFormField($field))->addClass($class)
	];
};

$form
	->addField($source_mode)
	->addField(
		new CWidgetFieldMultiSelectGroupView($data['fields']['groupids'], $data['captions']['ms']['groups']['groupids'] ?? [])
	)
	->addField(
		new CWidgetFieldMultiSelectHostView($data['fields']['hostids'], $data['captions']['ms']['hosts']['hostids'] ?? [])
	)
	->addField($host_order)
	->addField(new CWidgetFieldIntegerBoxView($data['fields']['limit_hosts']))
	->addField(new CWidgetFieldIntegerBoxView($data['fields']['limit_columns']))
	->addField($visual_mode)
	->addItem([
		(new CLabel(_('Problems mode')))->addClass('js-mode-problems matrix-view__section-title'),
		(new CFormField(
			(new CDiv(_('Use active problem tags to build the matrix columns, similar to an operational status board.')))
				->addClass('matrix-view__help')
		))->addClass('js-mode-problems')
	])
	->addItem($mode_row($tag_key->getLabel(), $tag_key->getView(), 'js-mode-problems'))
	->addItem($mode_row($problem_severities->getLabel(), $problem_severities->getView(), 'js-mode-problems'))
	->addItem($mode_row($ack_filter->getLabel(), $ack_filter->getView(), 'js-mode-problems'))
	->addItem($mode_row($suppressed_filter->getLabel(), $suppressed_filter->getView(), 'js-mode-problems'))
	->addItem($mode_row($maintenance_filter->getLabel(), $maintenance_filter->getView(), 'js-mode-problems'))
	->addItem($mode_row($column_order->getLabel(), $column_order->getView(), 'js-mode-problems'))
	->addItem($mode_row($show_problem_count->getLabel(), $show_problem_count->getView(), 'js-mode-problems'))
	->addItem([
		(new CLabel(_('Latest data mode')))->addClass('js-mode-latest-data matrix-view__section-title'),
		(new CFormField(
			(new CDiv(_('Define one column per line using Label|pattern|direction|warn|high|critical. Example: IIS|service.info[W3SVC,state]|desc|6|3|1')))
				->addClass('matrix-view__help')
		))->addClass('js-mode-latest-data')
	])
	->addItem($mode_row($latest_columns->getLabel(), $latest_columns->getView(), 'js-mode-latest-data'))
	->addItem($mode_row($latest_direction->getLabel(), $latest_direction->getView(), 'js-mode-latest-data'))
	->addItem($mode_row($missing_label->getLabel(), $missing_label->getView(), 'js-mode-latest-data'))
	->addItem([
		(new CLabel(_('Latest data syntax')))->addClass('js-mode-latest-data'),
		(new CFormField(
			(new CDiv(_('One column per line: Label|pattern|direction|warn|high|critical')))
				->addClass('matrix-view__help')
		))->addClass('js-mode-latest-data')
	])
	->includeJsFile('widget.edit.js.php')
	->addJavaScript('widget_matrix_view_form.init();')
	->show();
