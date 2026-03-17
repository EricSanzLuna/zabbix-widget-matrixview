<?php

/**
 * Matrix View widget configuration.
 *
 * @var CView $this
 * @var array $data
 */

$host_order = new CWidgetFieldSelectView($data['fields']['host_order']);
$visual_mode = new CWidgetFieldSelectView($data['fields']['visual_mode']);
$header_orientation = new CWidgetFieldSelectView($data['fields']['header_orientation']);
$show_legend = new CWidgetFieldCheckBoxView($data['fields']['show_legend']);
$show_maintenance = new CWidgetFieldCheckBoxView($data['fields']['show_maintenance']);
$state_source = new CWidgetFieldSelectView($data['fields']['state_source']);
$threshold_direction = new CWidgetFieldSelectView($data['fields']['threshold_direction']);
$warning_threshold = new CWidgetFieldNumericBoxView($data['fields']['warning_threshold']);
$high_threshold = new CWidgetFieldNumericBoxView($data['fields']['high_threshold']);
$critical_threshold = new CWidgetFieldNumericBoxView($data['fields']['critical_threshold']);
$color_ok = new CWidgetFieldTextBoxView($data['fields']['color_ok']);
$color_info = new CWidgetFieldTextBoxView($data['fields']['color_info']);
$color_warning = new CWidgetFieldTextBoxView($data['fields']['color_warning']);
$color_high = new CWidgetFieldTextBoxView($data['fields']['color_high']);
$color_critical = new CWidgetFieldTextBoxView($data['fields']['color_critical']);
$color_missing = new CWidgetFieldTextBoxView($data['fields']['color_missing']);
$column_aliases = new CWidgetFieldTextAreaView($data['fields']['column_aliases']);
$column_order = new CWidgetFieldTextAreaView($data['fields']['column_order']);
$item_thresholds = new CWidgetFieldTextAreaView($data['fields']['item_thresholds']);
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
	->addField($header_orientation)
	->addField($show_legend)
	->addItem([
		new CLabel(_('Columns')),
		new CFormField(
			(new CDiv(_('Select one or more reference items. Each selected item becomes one column, and the widget searches for the same item key on every visible host.')))
				->addClass('matrix-view__help')
		)
	])
	->addField(
		new CWidgetFieldMultiSelectItemView($data['fields']['itemids'], $data['captions']['ms']['items']['itemids'] ?? [])
	)
	->addItem([
		new CLabel(_('Column aliases')),
		new CFormField(
			(new CDiv(_('Optional aliases by item key. One line per column: key|alias. Example: service.info[W3SVC,state]|IIS')))
				->addClass('matrix-view__help')
		)
	])
	->addField($column_aliases)
	->addItem([
		new CLabel(_('Column order')),
		new CFormField(
			(new CDiv(_('Optional manual order. Use one line per item key or alias. Listed columns are rendered first in this order; the rest stay after them.')))
				->addClass('matrix-view__help')
		)
	])
	->addField($column_order)
	->addItem([
		new CLabel(_('State evaluation')),
		new CFormField(
			(new CDiv(_('Use trigger severities when available, with numeric thresholds and text patterns as fallback.')))
				->addClass('matrix-view__help')
		)
	])
	->addField($state_source)
	->addField($threshold_direction)
	->addField($warning_threshold)
	->addField($high_threshold)
	->addField($critical_threshold)
	->addItem([
		new CLabel(_('Per-item thresholds')),
		new CFormField(
			(new CDiv(_('Override thresholds for specific item keys using: key|direction|warning|high|critical. Example: system.cpu.util|asc|70|85|95')))
				->addClass('matrix-view__help')
		)
	])
	->addField($item_thresholds)
	->addItem([
		new CLabel(_('Indicator colors')),
		new CFormField(
			(new CDiv(_('Use HEX values like 4bb476 or #4bb476. These colors drive each state indicator and its soft background tint.')))
				->addClass('matrix-view__help')
		)
	])
	->addField($color_ok)
	->addField($color_info)
	->addField($color_warning)
	->addField($color_high)
	->addField($color_critical)
	->addField($color_missing)
	->addItem([
		new CLabel(_('Text matching')),
		new CFormField(
			(new CDiv(_('Comma-separated patterns are matched case-insensitively for text values.')))
				->addClass('matrix-view__help')
		)
	])
	->addField($ok_text)
	->addField($warning_text)
	->addField($critical_text)
	->addField($missing_label)
	->show();
