<?php

namespace Modules\MatrixView\Includes;

use Modules\MatrixView\Widget;
use Zabbix\Widgets\CWidgetField;
use Zabbix\Widgets\CWidgetForm;
use Zabbix\Widgets\Fields\CWidgetFieldCheckBox;
use Zabbix\Widgets\Fields\CWidgetFieldIntegerBox;
use Zabbix\Widgets\Fields\CWidgetFieldMultiSelectGroup;
use Zabbix\Widgets\Fields\CWidgetFieldMultiSelectHost;
use Zabbix\Widgets\Fields\CWidgetFieldMultiSelectItem;
use Zabbix\Widgets\Fields\CWidgetFieldNumericBox;
use Zabbix\Widgets\Fields\CWidgetFieldSelect;
use Zabbix\Widgets\Fields\CWidgetFieldTextBox;

class WidgetForm extends CWidgetForm {

	public function addFields(): self {
		return $this
			->addField(
				new CWidgetFieldMultiSelectGroup('groupids', _('Host groups'))
			)
			->addField(
				new CWidgetFieldMultiSelectHost('hostids', _('Hosts'))
			)
			->addField(
				(new CWidgetFieldCheckBox('show_maintenance', _('Show hosts in maintenance')))
					->setDefault(1)
			)
			->addField(
				(new CWidgetFieldSelect('host_order', _('Host order'), [
					Widget::ORDER_NAME_ASC => _('Name ascending'),
					Widget::ORDER_NAME_DESC => _('Name descending')
				]))->setDefault(Widget::ORDER_NAME_ASC)
			)
			->addField(
				(new CWidgetFieldIntegerBox('limit_hosts', _('Host limit')))
					->setDefault(25)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
			)
			->addField(
				(new CWidgetFieldSelect('visual_mode', _('Density'), [
					Widget::VISUAL_COMPACT => _('Compact'),
					Widget::VISUAL_COMFORTABLE => _('Comfortable')
				]))->setDefault(Widget::VISUAL_COMPACT)
			)
			->addField(
				(new CWidgetFieldMultiSelectItem('itemids', _('Columns')))
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
			)
			->addField(
				(new CWidgetFieldSelect('threshold_direction', _('Numeric thresholds'), [
					Widget::THRESHOLD_ASCENDING => _('Higher values are worse'),
					Widget::THRESHOLD_DESCENDING => _('Lower values are worse')
				]))->setDefault(Widget::THRESHOLD_ASCENDING)
			)
			->addField(
				(new CWidgetFieldNumericBox('warning_threshold', _('Warning threshold')))
					->setDefault('70')
			)
			->addField(
				(new CWidgetFieldNumericBox('high_threshold', _('High threshold')))
					->setDefault('85')
			)
			->addField(
				(new CWidgetFieldNumericBox('critical_threshold', _('Critical threshold')))
					->setDefault('95')
			)
			->addField(
				(new CWidgetFieldTextBox('ok_text', _('OK text patterns')))
					->setDefault('running,up,ok,healthy,1')
			)
			->addField(
				(new CWidgetFieldTextBox('warning_text', _('Warning text patterns')))
					->setDefault('warning,degraded')
			)
			->addField(
				(new CWidgetFieldTextBox('critical_text', _('Critical text patterns')))
					->setDefault('stopped,down,critical,failed,fail,error,0')
			)
			->addField(
				(new CWidgetFieldTextBox('missing_label', _('Missing item label')))
					->setDefault(_('No item'))
			);
	}
}
