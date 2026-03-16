<?php

namespace Modules\MatrixView;

use Zabbix\Core\CWidget;

class Widget extends CWidget {

	public const SOURCE_PROBLEMS = 0;
	public const SOURCE_LATEST_DATA = 1;

	public const VISUAL_COMPACT = 0;
	public const VISUAL_COMFORTABLE = 1;

	public const FILTER_ALL = -1;
	public const FILTER_NO = 0;
	public const FILTER_YES = 1;

	public const ORDER_NAME_ASC = 0;
	public const ORDER_NAME_DESC = 1;

	public const LATEST_ASCENDING = 0;
	public const LATEST_DESCENDING = 1;

	public function getTranslationStrings(): array {
		return [
			'class.widget.js' => [
				'No data' => _('No data'),
				'No matching rows' => _('No matching rows'),
				'Open Problems' => _('Open Problems')
			]
		];
	}
}
