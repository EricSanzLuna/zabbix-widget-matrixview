<?php

?>
window.widget_matrix_view_form = new class {
	init() {
		this._form = document.getElementById('widget-dialogue-form');
		this._mode = document.getElementById('source_mode');

		if (!this._form || !this._mode) {
			return;
		}

		this._mode.addEventListener('change', () => this.updateMode());
		this.updateMode();
	}

	updateMode() {
		const showProblems = this._mode.value === '0';

		for (const element of this._form.querySelectorAll('.js-mode-problems')) {
			element.style.display = showProblems ? '' : 'none';
		}

		for (const element of this._form.querySelectorAll('.js-mode-latest-data')) {
			element.style.display = showProblems ? 'none' : '';
		}
	}
};
