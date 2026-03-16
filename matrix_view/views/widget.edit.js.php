<?php

?>
window.widget_matrix_view_form = new class {
	init() {
		this._form = document.getElementById('widget-dialogue-form') || document.querySelector('form');
		this._mode = this._form
			? this._form.querySelector('[name="source_mode"], [name$="[source_mode]"]')
			: null;

		if (!this._form || !this._mode) {
			return;
		}

		this._mode.addEventListener('change', () => this.updateMode());
		this.updateMode();
	}

	updateMode() {
		const showProblems = this._mode.value === '0';

		for (const element of this._form.querySelectorAll('.js-mode-problems')) {
			this.toggleElement(element, showProblems);
		}

		for (const element of this._form.querySelectorAll('.js-mode-latest-data')) {
			this.toggleElement(element, !showProblems);
		}
	}

	toggleElement(element, visible) {
		const row = element.closest('.form_row, .fields-group, li, .table-forms-td-left, .table-forms-td-right') || element;

		row.style.display = visible ? '' : 'none';
	}
};
