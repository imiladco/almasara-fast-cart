/**
 * ویجت افزودن به سبد (افزونه سبد سریع الماسارا)
 *
 * افزودنِ واقعی را wc-add-to-cart و بجِ آنی را fast-cart.js انجام می‌دهد.
 * این فایل فقط UI ویجت را می‌چرخاند: استپر تعداد (sync با data-quantity
 * دکمه) و حالت بصری «افزوده‌شد».
 */
(function () {
	'use strict';

	function clampQty(input, val) {
		var min = parseInt(input.getAttribute('min'), 10) || 1;
		var max = parseInt(input.getAttribute('max'), 10) || 0;
		val = parseInt(val, 10) || min;
		if (val < min) { val = min; }
		if (max > 0 && val > max) { val = max; }
		return val;
	}

	function setup(root) {
		if (root.__amfcAtc) {
			return;
		}
		root.__amfcAtc = true;

		var input = root.querySelector('.amfc-atc__qty-input');
		var button = root.querySelector('.amfc-atc__btn');

		function sync() {
			if (input && button) {
				input.value = clampQty(input, input.value);
				button.setAttribute('data-quantity', input.value);
			}
		}

		if (input) {
			input.addEventListener('change', sync);
			input.addEventListener('input', sync);
		}

		var minus = root.querySelector('.amfc-atc__step--minus');
		var plus = root.querySelector('.amfc-atc__step--plus');
		if (minus && input) {
			minus.addEventListener('click', function () {
				input.value = clampQty(input, (parseInt(input.value, 10) || 1) - 1);
				sync();
			});
		}
		if (plus && input) {
			plus.addEventListener('click', function () {
				input.value = clampQty(input, (parseInt(input.value, 10) || 1) + 1);
				sync();
			});
		}

		sync();
	}

	function bindAddedState() {
		if (!window.jQuery) {
			return;
		}
		window.jQuery(document.body).on('added_to_cart', function (e, fragments, hash, $button) {
			if (!$button || !$button.length) {
				return;
			}
			var btn = $button[0];
			if (!btn.classList.contains('amfc-atc__btn')) {
				return;
			}
			var label = btn.querySelector('.amfc-atc__text');
			var addedText = btn.getAttribute('data-added-text');
			btn.classList.remove('loading');
			btn.classList.add('amfc-added');
			if (label && addedText) {
				if (!btn.__amfcOrig) {
					btn.__amfcOrig = label.textContent;
				}
				label.textContent = addedText;
			}
			clearTimeout(btn.__amfcTimer);
			btn.__amfcTimer = setTimeout(function () {
				btn.classList.remove('amfc-added');
				if (label && btn.__amfcOrig) {
					label.textContent = btn.__amfcOrig;
				}
			}, 2000);
		});
	}

	function initAll(scope) {
		(scope || document).querySelectorAll('.amfc-atc').forEach(setup);
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		window.elementorFrontend.hooks.addAction('frontend/element_ready/amfc-add-to-cart.default', function ($el) {
			initAll($el && $el[0] ? $el[0] : document);
		});
	}

	if (document.readyState !== 'loading') {
		initAll(document);
		bindAddedState();
	} else {
		document.addEventListener('DOMContentLoaded', function () {
			initAll(document);
			bindAddedState();
		});
	}
})();
