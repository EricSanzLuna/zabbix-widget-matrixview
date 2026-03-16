<?php

namespace Modules\MatrixView\Includes;

use Modules\MatrixView\Widget;
use Zabbix\Widgets\CWidgetField;
use Zabbix\Widgets\CWidgetForm;
use Zabbix\Widgets\Fields\CWidgetFieldIntegerBox;
use Zabbix\Widgets\Fields\CWidgetFieldMultiSelectGroup;
use Zabbix\Widgets\Fields\CWidgetFieldMultiSelectHost;
use Zabbix\Widgets\Fields\CWidgetFieldSelect;
use Zabbix\Widgets\Fields\CWidgetFieldSeverities;
use Zabbix\Widgets\Fields\CWidgetFieldTextArea;
use Zabbix\Widgets\Fields\CWidgetFieldTextBox;

class WidgetForm extends CWidgetForm {

	public function addFields(): self {
		return $this
			->addField(
				(new CWidgetFieldSelect('source_mode', _('Source mode'), [
					Widget::SOURCE_PROBLEMS => _('Problems'),
					Widget::SOURCE_LATEST_DATA => _('Latest data')
				]))
					->setDefault(Widget::SOURCE_PROBLEMS)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
			)
			->addField(
				new CWidgetFieldMultiSelectGroup('groupids', _('Host groups'))
			)
			->addField(
				new CWidgetFieldMultiSelectHost('hostids', _('Hosts'))
			)
			->addField(
				(new CWidgetFieldSelect('host_order', _('Host order'), [
					Widget::ORDER_NAME_ASC => _('Name ascending'),
					Widget::ORDER_NAME_DESC => _('Name descending')
				]))->setDefault(Widget::ORDER_NAME_ASC)
			)
			->addField(
				(new CWidgetFieldIntegerBox('limit_hosts', _('Max hosts')))
					->setDefault(25)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
			)
			->addField(
				(new CWidgetFieldIntegerBox('limit_columns', _('Max columns')))
					->setDefault(20)
					->setFlags(CWidgetField::FLAG_NOT_EMPTY | CWidgetField::FLAG_LABEL_ASTERISK)
			)
			->addField(
				(new CWidgetFieldSelect('visual_mode', _('Density'), [
					Widget::VISUAL_COMPACT => _('Compact'),
					Widget::VISUAL_COMFORTABLE => _('Comfortable')
				]))->setDefault(Widget::VISUAL_COMPACT)
			)
			->addField(
				(new CWidgetFieldTextBox('tag_key', _('Matrix tag key')))
					->setDefault('matrix')
			)
			->addField(
				new CWidgetFieldSeverities('problem_severities', _('Severities'))
			)
			->addField(
				(new CWidgetFieldSelect('problem_ack_filter', _('Acknowledged'), [
					Widget::FILTER_ALL => _('All'),
					Widget::FILTER_NO => _('Unacknowledged only'),
					Widget::FILTER_YES => _('Acknowledged only')
				]))->setDefault(Widget::FILTER_ALL)
			)
			->addField(
				(new CWidgetFieldSelect('problem_suppressed_filter', _('Suppressed problems'), [
					Widget::FILTER_ALL => _('All'),
					Widget::FILTER_NO => _('Hide suppressed'),
					Widget::FILTER_YES => _('Only suppressed')
				]))->setDefault(Widget::FILTER_ALL)
			)
			->addField(
				(new CWidgetFieldSelect('problem_maintenance_filter', _('Hosts in maintenance'), [
					Widget::FILTER_ALL => _('All'),
					Widget::FILTER_NO => _('Hide maintenance'),
					Widget::FILTER_YES => _('Only maintenance')
				]))->setDefault(Widget::FILTER_ALL)
			)
			->addField(
				new CWidgetFieldTextBox('column_order', _('Problem columns order'))
			)
			->addField(
				(new CWidgetFieldSelect('show_problem_count', _('Problem cell label'), [
					0 => _('Severity only'),
					1 => _('Severity + count')
				]))->setDefault(1)
			)
			->addField(
				new CWidgetFieldTextArea('latest_columns', _('Latest data columns'))
			)
			->addField(
				(new CWidgetFieldSelect('latest_default_direction', _('Latest data threshold direction'), [
					Widget::LATEST_ASCENDING => _('Higher values are worse'),
					Widget::LATEST_DESCENDING => _('Lower values are worse')
				]))->setDefault(Widget::LATEST_ASCENDING)
			)
			->addField(
				(new CWidgetFieldTextBox('missing_label', _('Missing item label')))
					->setDefault(_('No item'))
			);
	}
}
