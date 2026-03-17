<?php

?>
window.matrixViewColumnEditorInit = (function() {
	function normalizeCaptions(rawCaptions) {
		const captions = {};

		if (Array.isArray(rawCaptions)) {
			for (const entry of rawCaptions) {
				if (entry && typeof entry === 'object') {
					const id = String(entry.id ?? entry.itemid ?? entry.value ?? '');
					const name = String(entry.name ?? entry.label ?? entry.caption ?? '');

					if (id !== '') {
						captions[id] = name || id;
					}
				}
			}
		}
		else if (rawCaptions && typeof rawCaptions === 'object') {
			for (const [id, value] of Object.entries(rawCaptions)) {
				if (value && typeof value === 'object') {
					captions[String(id)] = String(value.name ?? value.label ?? value.caption ?? id);
				}
				else {
					captions[String(id)] = String(value);
				}
			}
		}

		return captions;
	}

	function parseLines(textarea) {
		return textarea.value
			.split(/\r?\n/)
			.map(line => line.trim())
			.filter(line => line !== '');
	}

	function createCell(text) {
		const cell = document.createElement('td');
		cell.textContent = text;
		return cell;
	}

	function formatThresholds(column) {
		const values = [column.warning, column.high, column.critical]
			.map(value => String(value ?? '').trim());

		if (values.every(value => value === '')) {
			return 'Global';
		}

		return `${column.direction === 'desc' ? 'Desc' : 'Asc'} ${values.join(' / ')}`;
	}

	function readSelectedItemIds(form) {
		return Array.from(form.querySelectorAll('input[name="fields[itemids][]"]'))
			.map(input => input.value)
			.filter(value => value !== '');
	}

	function buildInitialColumns(form, captionsById) {
		const configField = form.querySelector('[name="fields[columns_config]"]');
		const aliasField = form.querySelector('[name="fields[column_aliases]"]');
		const thresholdField = form.querySelector('[name="fields[item_thresholds]"]');

		if (configField && configField.value.trim() !== '') {
			try {
				const parsed = JSON.parse(configField.value);

				if (Array.isArray(parsed)) {
					return parsed.map(column => ({
						itemid: String(column.itemid ?? ''),
						label: String(column.label ?? ''),
						direction: String(column.direction ?? 'asc'),
						warning: String(column.warning ?? ''),
						high: String(column.high ?? ''),
						critical: String(column.critical ?? '')
					})).filter(column => column.itemid !== '');
				}
			}
			catch (error) {
				console.warn('Matrix View: invalid columns_config payload.', error);
			}
		}

		const aliasesByKey = {};
		const thresholdsByKey = {};

		if (aliasField) {
			for (const line of parseLines(aliasField)) {
				const parts = line.split('|');

				if (parts.length >= 2) {
					aliasesByKey[parts[0].trim()] = parts.slice(1).join('|').trim();
				}
			}
		}

		if (thresholdField) {
			for (const line of parseLines(thresholdField)) {
				const parts = line.split('|');

				if (parts.length >= 5) {
					thresholdsByKey[parts[0].trim()] = {
						direction: parts[1].trim() || 'asc',
						warning: parts[2].trim(),
						high: parts[3].trim(),
						critical: parts[4].trim()
					};
				}
			}
		}

		return [];
	}

	function syncLegacyFields(form, state, captionsById) {
		const configField = form.querySelector('[name="fields[columns_config]"]');
		const aliasField = form.querySelector('[name="fields[column_aliases]"]');
		const thresholdField = form.querySelector('[name="fields[item_thresholds]"]');

		if (configField) {
			configField.value = JSON.stringify(state.columns);
		}

		if (aliasField) {
			aliasField.value = state.columns
				.filter(column => column.label !== '')
				.map(column => `${column.itemid}|${column.label}`)
				.join("\n");
		}

		if (thresholdField) {
			thresholdField.value = state.columns
				.filter(column => column.warning !== '' || column.high !== '' || column.critical !== '')
				.map(column => [
					column.itemid,
					column.direction || 'asc',
					column.warning || '',
					column.high || '',
					column.critical || ''
				].join('|'))
				.join("\n");
		}
	}

	function init(config) {
		const form = document.querySelector('form.dashboard-widget-matrix_view');
		const editor = form ? form.querySelector('[data-role="matrix-view-column-editor"]') : null;

		if (!form || !editor) {
			return;
		}

		const captionsById = normalizeCaptions(config.item_captions || {});
		const rowsContainer = editor.querySelector('[data-role="rows"]');
		const addButton = editor.querySelector('.js-matrix-view-add-column');
		const modal = editor.querySelector('[data-role="modal"]');
		const fieldLabel = modal.querySelector('.js-matrix-view-column-label');
		const fieldItem = modal.querySelector('.js-matrix-view-column-item');
		const fieldDirection = modal.querySelector('.js-matrix-view-column-direction');
		const fieldWarning = modal.querySelector('.js-matrix-view-column-warning');
		const fieldHigh = modal.querySelector('.js-matrix-view-column-high');
		const fieldCritical = modal.querySelector('.js-matrix-view-column-critical');
		const modalTitle = modal.querySelector('[data-role="modal-title"]');
		const saveButton = modal.querySelector('.js-matrix-view-save-column');
		const cancelButtons = modal.querySelectorAll('.js-matrix-view-cancel');
		const state = {
			columns: buildInitialColumns(form, captionsById),
			editingIndex: null,
			dragIndex: null
		};

		function getAvailableItems() {
			return readSelectedItemIds(form).map(itemid => ({
				id: itemid,
				name: captionsById[itemid] ?? itemid
			}));
		}

		function ensureColumnsMatchSelection() {
			const availableIds = new Set(getAvailableItems().map(item => item.id));

			state.columns = state.columns.filter(column => availableIds.has(column.itemid));
		}

		function fillItemOptions(selectedItemId, currentIndex) {
			const availableItems = getAvailableItems();
			fieldItem.innerHTML = '';

			if (!availableItems.length) {
				const option = document.createElement('option');
				option.value = '';
				option.textContent = 'Select items above first';
				fieldItem.appendChild(option);
				return;
			}

			for (const item of availableItems) {
				const option = document.createElement('option');
				option.value = item.id;
				option.textContent = item.name;
				option.disabled = state.columns.some((column, index) => index !== currentIndex && column.itemid === item.id);
				option.selected = item.id === selectedItemId;
				fieldItem.appendChild(option);
			}
		}

		function openModal(index) {
			const column = index !== null
				? state.columns[index]
				: {itemid: '', label: '', direction: 'asc', warning: '', high: '', critical: ''};
			const availableItems = getAvailableItems();

			if (!availableItems.length) {
				window.alert('Select one or more items in "Available items" before adding columns.');
				return;
			}

			state.editingIndex = index;
			const firstAvailableItem = availableItems.find(item =>
				!state.columns.some(existing => existing.itemid === item.id)
			);
			fillItemOptions(column.itemid || (firstAvailableItem ? firstAvailableItem.id : availableItems[0].id), index);
			fieldLabel.value = column.label || '';
			fieldDirection.value = column.direction || 'asc';
			fieldWarning.value = column.warning || '';
			fieldHigh.value = column.high || '';
			fieldCritical.value = column.critical || '';
			modalTitle.textContent = index === null ? 'Add column' : 'Update column';
			saveButton.textContent = index === null ? 'Add' : 'Update';
			modal.hidden = false;
		}

		function closeModal() {
			state.editingIndex = null;
			modal.hidden = true;
		}

		function render() {
			ensureColumnsMatchSelection();
			rowsContainer.innerHTML = '';

			if (!state.columns.length) {
				const row = document.createElement('tr');
				row.className = 'matrix-view-editor__empty';
				const cell = document.createElement('td');
				cell.colSpan = 5;
				cell.textContent = 'No columns configured yet.';
				row.appendChild(cell);
				rowsContainer.appendChild(row);
				syncLegacyFields(form, state, captionsById);
				return;
			}

			state.columns.forEach((column, index) => {
				const row = document.createElement('tr');
				row.draggable = true;
				row.dataset.index = String(index);

				row.addEventListener('dragstart', () => {
					state.dragIndex = index;
					row.classList.add('matrix-view-editor__row--dragging');
				});

				row.addEventListener('dragend', () => {
					state.dragIndex = null;
					row.classList.remove('matrix-view-editor__row--dragging');
				});

				row.addEventListener('dragover', event => {
					event.preventDefault();
				});

				row.addEventListener('drop', event => {
					event.preventDefault();

					if (state.dragIndex === null || state.dragIndex === index) {
						return;
					}

					const [moved] = state.columns.splice(state.dragIndex, 1);
					state.columns.splice(index, 0, moved);
					render();
				});

				const dragCell = document.createElement('td');
				dragCell.className = 'matrix-view-editor__drag';
				dragCell.textContent = '::';
				row.appendChild(dragCell);

				const itemName = captionsById[column.itemid] ?? column.itemid;
				row.appendChild(createCell(column.label || itemName));
				const dataCell = createCell(itemName);
				dataCell.className = 'matrix-view-editor__data-cell';
				row.appendChild(dataCell);
				const thresholdsCell = createCell(formatThresholds(column));
				thresholdsCell.className = 'matrix-view-editor__thresholds-cell';
				row.appendChild(thresholdsCell);

				const actionsCell = document.createElement('td');
				actionsCell.className = 'matrix-view-editor__actions-cell';

				const editButton = document.createElement('button');
				editButton.type = 'button';
				editButton.className = 'matrix-view-editor__link';
				editButton.textContent = 'Edit';
				editButton.addEventListener('click', () => openModal(index));
				actionsCell.appendChild(editButton);

				const removeButton = document.createElement('button');
				removeButton.type = 'button';
				removeButton.className = 'matrix-view-editor__link';
				removeButton.textContent = 'Remove';
				removeButton.addEventListener('click', () => {
					state.columns.splice(index, 1);
					render();
				});
				actionsCell.appendChild(removeButton);

				row.appendChild(actionsCell);
				rowsContainer.appendChild(row);
			});

			syncLegacyFields(form, state, captionsById);
		}

		addButton.addEventListener('click', () => openModal(null));
		saveButton.addEventListener('click', event => {
			event.preventDefault();

			if (!fieldItem.value) {
				window.alert('Select a reference item for this column.');
				return;
			}

			if (state.columns.some((column, index) =>
				index !== state.editingIndex && column.itemid === fieldItem.value
			)) {
				window.alert('That reference item is already used by another column.');
				return;
			}

			const nextColumn = {
				itemid: fieldItem.value,
				label: fieldLabel.value.trim(),
				direction: fieldDirection.value,
				warning: fieldWarning.value.trim(),
				high: fieldHigh.value.trim(),
				critical: fieldCritical.value.trim()
			};

			if (state.editingIndex === null) {
				state.columns.push(nextColumn);
			}
			else {
				state.columns[state.editingIndex] = nextColumn;
			}

			closeModal();
			render();
		});

		for (const button of cancelButtons) {
			button.addEventListener('click', event => {
				event.preventDefault();
				closeModal();
			});
		}

		modal.addEventListener('click', event => {
			if (event.target === modal) {
				closeModal();
			}
		});

		document.addEventListener('keydown', event => {
			if (!modal.hidden && event.key === 'Escape') {
				closeModal();
			}
		});

		form.addEventListener('submit', () => {
			ensureColumnsMatchSelection();
			syncLegacyFields(form, state, captionsById);
		});

		render();
	}

	return init;
})();
