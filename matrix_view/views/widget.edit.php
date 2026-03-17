<?php

/**
 * Matrix View widget configuration.
 *
 * @var CView $this
 * @var array $data
 */

$host_order = new CWidgetFieldSelectView($data['fields']['host_order']);
$visual_mode = new CWidgetFieldSelectView($data['fields']['visual_mode']);
$show_maintenance = new CWidgetFieldCheckBoxView($data['fields']['show_maintenance']);
$threshold_direction = new CWidgetFieldSelectView($data['fields']['threshold_direction']);
$warning_threshold = new CWidgetFieldNumericBoxView($data['fields']['warning_threshold']);
$high_threshold = new CWidgetFieldNumericBoxView($data['fields']['high_threshold']);
$critical_threshold = new CWidgetFieldNumericBoxView($data['fields']['critical_threshold']);
$ok_text = new CWidgetFieldTextBoxView($data['fields']['ok_text']);
$warning_text = new CWidgetFieldTextBoxView($data['fields']['warning_text']);
$critical_text = new CWidgetFieldTextBoxView($data['fields']['critical_text']);
$missing_label = new CWidgetFieldTextBoxView($data['fields']['missing_label']);

(new CWidgetFormView($data))
	->addField(
		new CWidgetFieldMultiSelectGroupView($data['fields']['groupids'], $data['captions']['ms']['groups']['groupids'] ?? [])
	)
	->addField(
		new CWidgetFieldMultiSelectHostView($data['fields']['hostids'], $data['captions']['ms']['hosts']['hostids'] ?? [])
	)
	->addField($show_maintenance)
	->addField($host_order)
	->addField(new CWidgetFieldIntegerBoxView($data['fields']['limit_hosts']))
	->addField($visual_mode)
	->addItem([
		new CLabel(_('Columns')),
		new CFormField(
			(new CDiv(_('Select one or more reference items. The widget will use each selected item key as a column and search for that same key on every filtered host.')))
				->addClass('matrix-view__help')
		)
	])
	->addField(
		new CWidgetFieldMultiSelectItemView($data['fields']['itemids'], $data['captions']['ms']['items']['itemids'] ?? [])
	)
	->addItem([
		new CLabel(_('Color rules')),
		new CFormField(
			(new CDiv(_('Numeric values use the thresholds below. Text values use the pattern lists. Example text statuses: running, stopped, failed, warning.')))
				->addClass('matrix-view__help')
		)
	])
	->addField($threshold_direction)
	->addField($warning_threshold)
	->addField($high_threshold)
	->addField($critical_threshold)
	->addField($ok_text)
	->addField($warning_text)
	->addField($critical_text)
	->addField($missing_label)
	->show();
