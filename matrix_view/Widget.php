<?php

namespace Modules\MatrixView;

use Zabbix\Core\CWidget;

class Widget extends CWidget {

	public const VISUAL_COMPACT = 0;
	public const VISUAL_COMFORTABLE = 1;

	public const ORDER_NAME_ASC = 0;
	public const ORDER_NAME_DESC = 1;

	public const THRESHOLD_ASCENDING = 0;
	public const THRESHOLD_DESCENDING = 1;

	public const STATE_SOURCE_TRIGGER_FIRST = 0;
	public const STATE_SOURCE_THRESHOLDS_ONLY = 1;

	public function getTranslationStrings(): array {
		return [];
	}
}
