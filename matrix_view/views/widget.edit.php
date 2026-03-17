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
$show_maintenance = new CWidgetFieldCheckBoxView($data['fields']['show_maintenance']);
$state_source = new CWidgetFieldSelectView($data['fields']['state_source']);
$columns_config = new CWidgetFieldTextAreaView($data['fields']['columns_config']);
$column_aliases = new CWidgetFieldTextAreaView($data['fields']['column_aliases']);
$item_thresholds = new CWidgetFieldTextAreaView($data['fields']['item_thresholds']);
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
$ok_text = new CWidgetFieldTextBoxView($data['fields']['ok_text']);
$warning_text = new CWidgetFieldTextBoxView($data['fields']['warning_text']);
$critical_text = new CWidgetFieldTextBoxView($data['fields']['critical_text']);
$missing_label = new CWidgetFieldTextBoxView($data['fields']['missing_label']);

$columns_table = (new CTag('table', true))
	->addClass('matrix-view-editor__table')
	->addItem(
		(new CTag('thead', true))->addItem(
			(new CTag('tr', true))
				->addItem((new CTag('th', true, ''))->addClass('matrix-view-editor__drag-head'))
				->addItem(new CTag('th', true, _('Name')))
				->addItem(new CTag('th', true, _('Data')))
				->addItem(new CTag('th', true, _('Thresholds')))
				->addItem(new CTag('th', true, _('Action')))
		)
	)
	->addItem(
		(new CTag('tbody', true))
			->addClass('matrix-view-editor__rows')
			->setAttribute('data-role', 'rows')
	);

$editor_actions = (new CDiv([
	(new CTag('button', true, _('Add')))
		->addClass('js-matrix-view-add-column')
		->setAttribute('type', 'button'),
	(new CSpan(_('Drag rows to reorder columns.')))
		->addClass('matrix-view-editor__hint')
]))->addClass('matrix-view-editor__actions');

$modal = (new CDiv([
	(new CDiv([
		(new CDiv(_('Update column')))
			->addClass('matrix-view-editor__modal-title')
			->setAttribute('data-role', 'modal-title'),
		(new CTag('button', true, "\xC3\x97"))
			->addClass('js-matrix-view-cancel matrix-view-editor__modal-close')
			->setAttribute('type', 'button'),
		(new CDiv([
			(new CTag('label', true, _('Name')))
				->addClass('matrix-view-editor__label'),
			(new CTag('input', false))
				->addClass('js-matrix-view-column-label matrix-view-editor__input')
				->setAttribute('type', 'text')
				->setAttribute('name', 'matrix_view_column_label')
				->setAttribute('value', '')
				->setAttribute('autocomplete', 'off')
		]))->addClass('matrix-view-editor__field'),
		(new CDiv([
			(new CTag('label', true, _('Reference item')))
				->addClass('matrix-view-editor__label'),
			(new CTag('select', true))
				->addClass('js-matrix-view-column-item matrix-view-editor__input'),
			(new CDiv(_('Choose one of the items selected above. Each reference item can be used once.')))
				->addClass('matrix-view-editor__subhint')
		]))->addClass('matrix-view-editor__field'),
		(new CDiv([
			(new CTag('label', true, _('Threshold direction')))
				->addClass('matrix-view-editor__label'),
			(new CTag('select', true, [
				(new CTag('option', true, _('Higher values are worse')))->setAttribute('value', 'asc'),
				(new CTag('option', true, _('Lower values are worse')))->setAttribute('value', 'desc')
			]))->addClass('js-matrix-view-column-direction matrix-view-editor__input')
		]))->addClass('matrix-view-editor__field'),
		(new CDiv([
			(new CDiv([
				(new CTag('label', true, _('Warning')))
					->addClass('matrix-view-editor__label'),
				(new CTag('input', false))
					->addClass('js-matrix-view-column-warning matrix-view-editor__input')
					->setAttribute('type', 'text')
					->setAttribute('name', 'matrix_view_column_warning')
					->setAttribute('value', '')
			]))->addClass('matrix-view-editor__field matrix-view-editor__field--threshold'),
			(new CDiv([
				(new CTag('label', true, _('High')))
					->addClass('matrix-view-editor__label'),
				(new CTag('input', false))
					->addClass('js-matrix-view-column-high matrix-view-editor__input')
					->setAttribute('type', 'text')
					->setAttribute('name', 'matrix_view_column_high')
					->setAttribute('value', '')
			]))->addClass('matrix-view-editor__field matrix-view-editor__field--threshold'),
			(new CDiv([
				(new CTag('label', true, _('Critical')))
					->addClass('matrix-view-editor__label'),
				(new CTag('input', false))
					->addClass('js-matrix-view-column-critical matrix-view-editor__input')
					->setAttribute('type', 'text')
					->setAttribute('name', 'matrix_view_column_critical')
					->setAttribute('value', '')
			]))->addClass('matrix-view-editor__field matrix-view-editor__field--threshold')
		]))->addClass('matrix-view-editor__threshold-grid'),
		(new CDiv([
			(new CTag('button', true, _('Update')))
				->addClass('js-matrix-view-save-column')
				->setAttribute('type', 'button'),
			(new CTag('button', true, _('Cancel')))
				->addClass('js-matrix-view-cancel')
				->setAttribute('type', 'button')
		]))->addClass('matrix-view-editor__modal-actions')
	]))->addClass('matrix-view-editor__modal')
]))->addClass('matrix-view-editor__modal-overlay')
	->setAttribute('data-role', 'modal')
	->setAttribute('hidden', 'hidden');

$editor = (new CDiv([
	$editor_actions,
	$columns_table,
	(new CDiv(_('Select one or more reference items above, then use Add to create visible matrix columns. Each column can override its own name and thresholds.')))
		->addClass('matrix-view__help'),
	$modal
]))->addClass('matrix-view-editor')
	->setAttribute('data-role', 'matrix-view-column-editor');

$style = <<<'CSS'
<style>
.matrix-view-editor{display:flex;flex-direction:column;gap:10px}
.matrix-view-editor__actions{display:flex;align-items:center;gap:12px}
.matrix-view-editor__hint{color:#9ea7b3;font-size:11px}
.matrix-view-editor__table{width:100%;border-collapse:separate;border-spacing:0;box-shadow:inset 0 0 0 1px rgba(255,255,255,.08)}
.matrix-view-editor__table th,.matrix-view-editor__table td{padding:8px 10px;border-bottom:1px solid rgba(255,255,255,.06);text-align:left}
.matrix-view-editor__table th{color:#9ea7b3;font-size:11px}
.matrix-view-editor__table tr:last-child td{border-bottom:0}
.matrix-view-editor__drag-head{width:18px}
.matrix-view-editor__drag{cursor:grab;color:#7f8792;user-select:none}
.matrix-view-editor__row--dragging{opacity:.45}
.matrix-view-editor__actions-cell{display:flex;gap:10px}
.matrix-view-editor__link{background:none;border:0;padding:0;color:#6ab7ff;cursor:pointer}
.matrix-view-editor__data-cell{color:#cfd6df;font-size:12px}
.matrix-view-editor__thresholds-cell{color:#9ea7b3;font-size:11px}
.matrix-view-editor__empty td{color:#9ea7b3;font-style:italic}
.matrix-view-editor__modal-overlay{position:fixed;inset:0;background:rgba(10,12,15,.62);display:flex;align-items:center;justify-content:center;z-index:1000}
.matrix-view-editor__modal{position:relative;width:min(640px,calc(100vw - 48px));background:#2b2b2b;border:1px solid rgba(255,255,255,.09);box-shadow:0 16px 48px rgba(0,0,0,.45);padding:20px;display:flex;flex-direction:column;gap:14px}
.matrix-view-editor__modal-title{font-size:20px;font-weight:600;color:#fff}
.matrix-view-editor__modal-close{position:absolute;right:22px;top:18px;min-width:auto;padding:0 6px}
.matrix-view-editor__field{display:flex;flex-direction:column;gap:6px}
.matrix-view-editor__field--threshold{min-width:0}
.matrix-view-editor__threshold-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
.matrix-view-editor__label{color:#cbd3dc;font-size:12px}
.matrix-view-editor__subhint{color:#8e98a4;font-size:11px}
.matrix-view-editor__input{width:100%}
.matrix-view-editor__modal-actions{display:flex;justify-content:flex-end;gap:10px}
.matrix-view-editor__storage{display:none}
</style>
CSS;

$form = (new CWidgetFormView($data))
	->addItem($style)
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
	->addItem([
		new CLabel(_('Available items')),
		new CFormField(
			(new CDiv(_('Select the items that can be used by the column editor.')))
				->addClass('matrix-view__help')
		)
	])
	->addField(
		new CWidgetFieldMultiSelectItemView($data['fields']['itemids'], $data['captions']['ms']['items']['itemids'] ?? [])
	)
	->addItem([
		new CLabel(_('Columns')),
		new CFormField($editor)
	])
	->addItem([
		new CLabel(_('Color rules')),
		new CFormField(
			(new CDiv(_('Use trigger severities when available, with thresholds and text patterns as fallback. Per-column overrides are managed in the editor above.')))
				->addClass('matrix-view__help')
		)
	])
	->addField($state_source)
	->addField($threshold_direction)
	->addField($warning_threshold)
	->addField($high_threshold)
	->addField($critical_threshold)
	->addItem([
		new CLabel(_('Indicator colors')),
		new CFormField(
			(new CDiv(_('Use HEX colors like #4bb476. These colors drive the icon and the soft cell tint for each state.')))
				->addClass('matrix-view__help')
		)
	])
	->addField($color_ok)
	->addField($color_info)
	->addField($color_warning)
	->addField($color_high)
	->addField($color_critical)
	->addField($color_missing)
	->addField($ok_text)
	->addField($warning_text)
	->addField($critical_text)
	->addField($missing_label)
	->addField($columns_config, 'matrix-view-editor__storage')
	->addField($column_aliases, 'matrix-view-editor__storage')
	->addField($item_thresholds, 'matrix-view-editor__storage')
	->includeJsFile('widget.edit.js.php')
	->addJavaScript('window.matrixViewColumnEditorInit && window.matrixViewColumnEditorInit('.json_encode([
		'item_captions' => $data['captions']['ms']['items']['itemids'] ?? []
	]).');');

$form->show();
