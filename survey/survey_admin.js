/**
 * Survey Admin Builder - Vanilla JS (no dependencies)
 * Drag & drop field reordering + add/remove fields
 *
 * Strategy: cards have draggable="true" in HTML.
 * We track WHERE the mousedown happened. In dragstart,
 * we only allow the drag if mousedown was on the .drag-handle.
 * Otherwise we cancel the drag with e.preventDefault().
 * This lets users select text in inputs normally.
 */
(function () {
	'use strict';

	var list = document.getElementById('survey-field-list');
	if (!list) return;

	var orderInput = document.getElementById('field-order-input');
	var nextIndex = parseInt(list.getAttribute('data-next-index') || '0', 10);
	/* ---- Inline SVG icons (single color via currentColor) ---- */
	var SVG_OPEN = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">';
	var ICON_GRIP = SVG_OPEN + '<circle cx="9" cy="6" r="1.4" fill="currentColor" stroke="none"/><circle cx="15" cy="6" r="1.4" fill="currentColor" stroke="none"/><circle cx="9" cy="12" r="1.4" fill="currentColor" stroke="none"/><circle cx="15" cy="12" r="1.4" fill="currentColor" stroke="none"/><circle cx="9" cy="18" r="1.4" fill="currentColor" stroke="none"/><circle cx="15" cy="18" r="1.4" fill="currentColor" stroke="none"/></svg>';
	var ICON_X = SVG_OPEN + '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
	var ICON_CODE = SVG_OPEN + '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>';

	/* ============================================================
	   Drag & Drop
	   ============================================================ */

	var dragged = null;
	var mousedownWasOnHandle = false;

	// Track where mousedown occurs
	list.addEventListener('mousedown', function (e) {
		mousedownWasOnHandle = !!e.target.closest('.drag-handle');
	});

	list.addEventListener('dragstart', function (e) {
		// Only allow drag if mousedown was on the handle
		if (!mousedownWasOnHandle) {
			e.preventDefault();
			return;
		}

		var card = e.target.closest('.survey-field-card');
		if (!card) { e.preventDefault(); return; }

		dragged = card;
		// Delay class addition so browser captures the card image first
		var c = card;
		window.setTimeout(function () { c.classList.add('dragging'); }, 0);

		e.dataTransfer.effectAllowed = 'move';
		e.dataTransfer.setData('text/plain', c.dataset.index || '');
	});

	list.addEventListener('dragend', function (e) {
		var card = e.target.closest('.survey-field-card');
		if (card) card.classList.remove('dragging');
		clearDropIndicators();
		dragged = null;
		mousedownWasOnHandle = false;
		renumberCards();
	});

	list.addEventListener('dragover', function (e) {
		e.preventDefault();
		e.dataTransfer.dropEffect = 'move';
		clearDropIndicators();

		var card = e.target.closest('.survey-field-card');
		if (!card || card === dragged) {
			// Over the list background: highlight last card as drop-below
			if (!card && dragged) {
				var all = list.querySelectorAll('.survey-field-card');
				if (all.length > 1) all[all.length - 1].classList.add('drop-below');
			}
			return;
		}

		var rect = card.getBoundingClientRect();
		if (e.clientY < rect.top + rect.height / 2) {
			card.classList.add('drop-above');
		} else {
			card.classList.add('drop-below');
		}
	});

	list.addEventListener('dragleave', function (e) {
		if (e.target === list) { clearDropIndicators(); return; }
		var card = e.target.closest('.survey-field-card');
		if (card && (!e.relatedTarget || !card.contains(e.relatedTarget))) {
			card.classList.remove('drop-above', 'drop-below');
		}
	});

	list.addEventListener('drop', function (e) {
		e.preventDefault();
		clearDropIndicators();
		if (!dragged) return;

		var target = e.target.closest('.survey-field-card');

		if (!target || target === dragged) {
			// Dropped on list background -> move to end
			list.appendChild(dragged);
		} else {
			var rect = target.getBoundingClientRect();
			if (e.clientY < rect.top + rect.height / 2) {
				list.insertBefore(dragged, target);
			} else {
				list.insertBefore(dragged, target.nextSibling);
			}
		}

		dragged = null;
		mousedownWasOnHandle = false;
		renumberCards();
	});

	function clearDropIndicators() {
		var cards = list.querySelectorAll('.survey-field-card');
		for (var i = 0; i < cards.length; i++) {
			cards[i].classList.remove('drop-above', 'drop-below', 'drop-target');
		}
	}

	function renumberCards() {
		var cards = list.querySelectorAll('.survey-field-card');
		for (var i = 0; i < cards.length; i++) {
			// Only update the DISPLAY number, NOT data-index
			// data-index must stay as the original key (matches input names)
			var numEl = cards[i].querySelector('.field-num');
			if (numEl) numEl.textContent = i + 1;
		}
		updateOrderInput();
	}

	function updateOrderInput() {
		if (!orderInput) return;
		var cards = list.querySelectorAll('.survey-field-card');
		var idx = [];
		for (var i = 0; i < cards.length; i++) {
			// Use the ORIGINAL data-index (matches form input array keys)
			idx.push(cards[i].dataset.index);
		}
		orderInput.value = idx.join(',');
	}

	/* ============================================================
	   Add Field
	   ============================================================ */

	var addBtn = document.getElementById('survey-add-field');
	if (addBtn) {
		addBtn.addEventListener('click', function () {
			addFieldCard('1', '', 12);
		});
	}

	function addFieldCard(type, label, col) {
		var idx = nextIndex++;
		list.setAttribute('data-next-index', nextIndex);

		var card = document.createElement('div');
		card.className = 'survey-field-card card';
		card.setAttribute('draggable', 'true');
		card.dataset.index = idx;

		var typeSelect = document.getElementById('survey-type-template');
		var typeOptions = '';
		if (typeSelect) {
			var opts = typeSelect.querySelectorAll('option');
			for (var i = 0; i < opts.length; i++) {
				var sel = (opts[i].value == type) ? ' selected' : '';
				typeOptions += '<option value="' + opts[i].value + '"' + sel + '>' + opts[i].textContent + '</option>';
			}
		}

		var isHtmlBlock = (type == '22');
		var bodyHtml;

		if (isHtmlBlock) {
			bodyHtml =
				'<div class="row g-2">'
				+ '<div class="col-12">'
				+ '<label class="form-label small mb-1">HTML Content</label>'
				+ '<textarea class="form-control form-control-sm field-text-input" name="field_text[' + idx + ']" rows="8" spellcheck="false" style="font-family:monospace;"></textarea>'
				+ '</div>'
				+ '<div class="col-6 col-md-4">'
				+ '<label class="form-label small mb-1">Type</label>'
				+ '<select class="form-select form-select-sm field-type-input" name="field_type[' + idx + ']">'
				+ typeOptions
				+ '</select>'
				+ '</div>'
				+ '<div class="col-6 col-md-2 text-center">'
				+ '<div class="form-check">'
				+ '<input class="form-check-input field-hidden-input" type="checkbox" name="field_hidden[' + idx + ']" value="1" />'
				+ '<label class="form-check-label small">Hid</label>'
				+ '</div>'
				+ '</div>'
				+ '</div>';
		} else {
			var colOptions = '<option value="12"' + (col == 12 ? ' selected' : '') + '>Full</option>'
				+ '<option value="6"' + (col == 6 ? ' selected' : '') + '>Half</option>'
				+ '<option value="4"' + (col == 4 ? ' selected' : '') + '>Third</option>'
				+ '<option value="3"' + (col == 3 ? ' selected' : '') + '>Quarter</option>';

			bodyHtml =
				'<div class="row g-2">'
				+ '<div class="col-12 col-md-5">'
				+ '<label class="form-label small mb-1">Label</label>'
				+ '<input type="text" class="form-control form-control-sm field-text-input" name="field_text[' + idx + ']" value="' + (label || '') + '" />'
				+ '</div>'
				+ '<div class="col-6 col-md-3">'
				+ '<label class="form-label small mb-1">Type</label>'
				+ '<select class="form-select form-select-sm field-type-input" name="field_type[' + idx + ']">'
				+ typeOptions
				+ '</select>'
				+ '</div>'
				+ '<div class="col-6 col-md-2">'
				+ '<label class="form-label small mb-1">Width</label>'
				+ '<select class="form-select form-select-sm field-col-input" name="field_col[' + idx + ']">'
				+ colOptions
				+ '</select>'
				+ '</div>'
				+ '<div class="col-4 col-md-1 text-center d-flex align-items-end">'
				+ '<div class="form-check">'
				+ '<input class="form-check-input field-req-input" type="checkbox" name="field_req[' + idx + ']" value="1" />'
				+ '<label class="form-check-label small">Req</label>'
				+ '</div>'
				+ '</div>'
				+ '<div class="col-4 col-md-1 text-center d-flex align-items-end">'
				+ '<div class="form-check">'
				+ '<input class="form-check-input field-hidden-input" type="checkbox" name="field_hidden[' + idx + ']" value="1" />'
				+ '<label class="form-check-label small">Hid</label>'
				+ '</div>'
				+ '</div>'
				+ '<div class="col-12 col-md-4">'
				+ '<label class="form-label small mb-1">Parameters</label>'
				+ '<input type="text" class="form-control form-control-sm field-choices-input" name="field_choices[' + idx + ']" value="" placeholder="e.g. 30,100 or opt1,opt2" />'
				+ '</div>'
				+ '</div>';
		}

		var headerPreview = isHtmlBlock
			? '<span class="badge field-type-badge"><span class="field-icon">' + ICON_CODE + '</span> HTML Block</span>'
			: '<span class="field-label-preview">' + (label || 'New field') + '</span>';

		card.innerHTML =
			'<div class="card-header">'
			+ '<span class="drag-handle" title="Drag to reorder">' + ICON_GRIP + '</span>'
			+ '<span class="field-num">' + (idx + 1) + '</span>'
			+ headerPreview
			+ '<div class="field-actions">'
			+ '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-field" title="Delete">' + ICON_X + '</button>'
			+ '</div>'
			+ '</div>'
			+ '<div class="card-body">'
			+ bodyHtml
			+ '</div>';

		var hiddenNum = document.createElement('input');
		hiddenNum.type = 'hidden';
		hiddenNum.name = 'field_number[' + idx + ']';
		hiddenNum.value = idx + 1;
		card.appendChild(hiddenNum);

		var labelInput = card.querySelector('.field-text-input');
		if (labelInput) {
			labelInput.addEventListener('input', function () {
				var preview = card.querySelector('.field-label-preview');
				if (preview) preview.textContent = labelInput.value || 'New field';
			});
		}

		list.appendChild(card);
		renumberCards();
		card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
		var newBody = card.querySelector('.card-body');
		if (newBody) newBody.classList.remove('d-none');
	}

	/* ============================================================
	   Palette buttons
	   ============================================================ */

	var palette = document.getElementById('survey-field-palette');
	if (palette) {
		palette.addEventListener('click', function (e) {
			var btn = e.target.closest('.btn[data-field-type]');
			if (!btn) return;
			var type = btn.dataset.fieldType;
			var labels = {
				'1': 'Text field', '2': 'Text area', '3': 'Checkboxes',
				'4': 'Radio buttons', '5': 'Dropdown', '6': 'Separator',
				'7': 'Date picker', '8': 'Name', '9': 'Calculation',
				'10': 'Email', '11': 'Number', '12': 'Email-to',
				'13': 'Row break', '14': 'Signature', '15': 'File upload',
				'16': 'Phone', '17': 'URL', '18': 'Color picker',
				'19': 'Range slider', '20': 'Time picker', '21': 'Country',
				'22': 'HTML block', '23': 'Password'
			};
			addFieldCard(type, labels[type] || 'New field', 12);
		});
	}

	// Delete button — event delegation (works for PHP-rendered AND JS-created cards)
	list.addEventListener('click', function (e) {
		var delBtn = e.target.closest('.btn-delete-field');
		if (!delBtn) return;
		e.stopPropagation();
		var card = delBtn.closest('.survey-field-card');
		if (card) {
			card.remove();
			renumberCards();
		}
	});

	/* ============================================================
	   Collapse / expand
	   ============================================================ */

	list.addEventListener('click', function (e) {
		var header = e.target.closest('.card-header');
		if (!header) return;
		if (e.target.closest('.drag-handle') || e.target.closest('.btn')
			|| e.target.closest('input') || e.target.closest('select')) return;
		var body = header.parentElement.querySelector('.card-body');
		if (body) body.classList.toggle('d-none');
	});

	// Collapse all existing cards on load
	var existingCards = list.querySelectorAll('.survey-field-card');
	for (var i = 0; i < existingCards.length; i++) {
		var body = existingCards[i].querySelector('.card-body');
		if (body) body.classList.add('d-none');
	}

})();
