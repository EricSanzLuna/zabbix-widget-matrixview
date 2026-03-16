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

(new CWidgetFormView($data))
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
	->addField($tag_key, 'js-mode-problems')
	->addField($problem_severities, 'js-mode-problems')
	->addField($ack_filter, 'js-mode-problems')
	->addField($suppressed_filter, 'js-mode-problems')
	->addField($maintenance_filter, 'js-mode-problems')
	->addField($column_order, 'js-mode-problems')
	->addField($show_problem_count, 'js-mode-problems')
	->addItem([
		(new CLabel(_('Latest data mode')))->addClass('js-mode-latest-data matrix-view__section-title'),
		(new CFormField(
			(new CDiv(_('Define one column per line using Label|pattern|direction|warn|high|critical. Example: IIS|service.info[W3SVC,state]|desc|6|3|1')))
				->addClass('matrix-view__help')
		))->addClass('js-mode-latest-data')
	])
	->addField($latest_columns, 'js-mode-latest-data')
	->addField($latest_direction, 'js-mode-latest-data')
	->addField($missing_label, 'js-mode-latest-data')
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
