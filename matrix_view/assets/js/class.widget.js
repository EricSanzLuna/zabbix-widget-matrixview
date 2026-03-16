class WidgetMatrixView extends CWidget {
	onStart() {
		this.bindInteractions();
	}

	onActivate() {
		this.bindInteractions();
	}

	processUpdateResponse(response) {
		super.processUpdateResponse(response);
		this.bindInteractions();
	}

	bindInteractions() {
		if (!this._target) {
			return;
		}

		for (const element of this._target.querySelectorAll('.matrix-view__cell-action')) {
			if (element.dataset.matrixViewBound === '1') {
				continue;
			}

			element.dataset.matrixViewBound = '1';
			element.addEventListener('click', () => {
				const detail = element.dataset.detail ? JSON.parse(element.dataset.detail) : {};

				document.dispatchEvent(new CustomEvent('matrix-view:cell-selected', {
					detail
				}));
			});
		}
	}
}
