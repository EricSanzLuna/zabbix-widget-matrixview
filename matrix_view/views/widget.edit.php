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
$state_source = new CWidgetFieldSelectView($data['fields']['state_source']);
$column_aliases = new CWidgetFieldTextAreaView($data['fields']['column_aliases']);
$item_thresholds = new CWidgetFieldTextAreaView($data['fields']['item_thresholds']);
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
		new CLabel(_('Column aliases')),
		new CFormField(
			(new CDiv(_('Optional aliases by item key. One line per selected reference item: key|alias. Example: service.info[W3SVC,state]|IIS')))
				->addClass('matrix-view__help')
		)
	])
	->addField($column_aliases)
	->addItem([
		new CLabel(_('Color rules')),
		new CFormField(
			(new CDiv(_('Use trigger severities when available, with thresholds and text patterns as fallback. You can also override thresholds per selected item.')))
				->addClass('matrix-view__help')
		)
	])
	->addField($state_source)
	->addItem([
		new CLabel(_('Per-item overrides')),
		new CFormField(
			(new CDiv(_('One line per selected reference item: key|direction|warning|high|critical. Example: system.cpu.util|asc|70|85|95')))
				->addClass('matrix-view__help')
		)
	])
	->addField($item_thresholds)
	->addField($threshold_direction)
	->addField($warning_threshold)
	->addField($high_threshold)
	->addField($critical_threshold)
	->addField($ok_text)
	->addField($warning_text)
	->addField($critical_text)
	->addField($missing_label)
	->show();
