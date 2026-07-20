/**
 * Almasara Fast Cart — لایه خوش‌بینانه (هسته، مستقل از المنتور)
 *
 * - هیدریت بج از کوکی بومی woocommerce_items_in_cart (ضد کش صفحه، بدون سرور)
 * - افزایش خوش‌بینانه برای دکمه‌های بومی ووکامرس با event delegation
 * - آشتی با کوکی روی رویداد بومی added_to_cart
 * - sync چندتب با BroadcastChannel
 * - toast متمرکز: هر افزودنی (بومی یا ویجت) رویداد almasara:added_to_cart
 *   می‌فرستد و فقط اینجا toast نشان داده می‌شود (بدون دوبار نمایش)
 * - پیش‌بارگذاری صفحه سبد با Speculation Rules
 *
 * بدون نانس REST: هویت با کوکی سشن ووکامرس منتقل می‌شود (مثل wc-ajax)؛
 * نانسِ جاسازی‌شده در صفحه کش‌شده بعد از انقضا همه‌چیز را 403 می‌کرد.
 */
(function () {
	'use strict';

	var CFG = window.AMFC || {};
	var COUNT_COOKIE = 'woocommerce_items_in_cart';
	var FA = '۰۱۲۳۴۵۶۷۸۹';

	function toFa(n) {
		return String(n).replace(/\d/g, function (d) { return FA[d]; });
	}

	function getCookie(name) {
		var m = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
		return m ? decodeURIComponent(m.pop()) : '';
	}

	function cartCountFromCookie() {
		return parseInt(getCookie(COUNT_COOKIE), 10) || 0;
	}

	/* ---------------- بج شمارنده ---------------- */

	var lastCount = -1;

	function paintBadge(count) {
		if (!CFG.countSelector) {
			return;
		}
		var nodes = document.querySelectorAll(CFG.countSelector);
		for (var i = 0; i < nodes.length; i++) {
			nodes[i].textContent = toFa(count);
			nodes[i].classList.toggle('amfc-has-items', count > 0);
		}
	}

	var channel = ('BroadcastChannel' in window) ? new BroadcastChannel('almasara-fast-cart') : null;

	function setCount(count, fromRemote) {
		count = Math.max(0, count | 0);
		if (count === lastCount) {
			return;
		}
		lastCount = count;
		paintBadge(count);
		if (channel && !fromRemote) {
			channel.postMessage({ type: 'count', value: count });
		}
	}

	if (channel) {
		channel.onmessage = function (e) {
			if (e.data && e.data.type === 'count') {
				setCount(e.data.value, true);
			}
		};
	}

	function reconcileFromCookie() {
		setCount(cartCountFromCookie());
	}

	/* ---------------- اعلان (Toast) ---------------- */

	var toastEl = null;
	var toastTimer = null;

	function toast(msg, isError) {
		if (!msg || (!isError && (!CFG.toast || !CFG.toast.enabled))) {
			return;
		}
		if (!toastEl) {
			toastEl = document.createElement('div');
			toastEl.className = 'amfc-toast';
			toastEl.setAttribute('role', 'status');
			toastEl.setAttribute('aria-live', 'polite');
			document.body.appendChild(toastEl);
		}
		toastEl.textContent = msg;
		toastEl.classList.toggle('is-error', !!isError);
		toastEl.classList.add('is-visible');
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () {
			toastEl.classList.remove('is-visible');
		}, 2500);
	}

	/* ---------------- افزودن خوش‌بینانه (دکمه‌های بومی) ---------------- */

	function readQty(button) {
		var q = parseInt(button.getAttribute('data-quantity'), 10);
		if (q > 0) {
			return q;
		}
		var form = button.closest('form.cart, form');
		var input = form ? form.querySelector('input.qty, input[name="quantity"]') : null;
		q = input ? parseInt(input.value, 10) : 1;
		return q > 0 ? q : 1;
	}

	// event delegation: دکمه‌های حال و آینده (آرشیو، quick-view، صفحه‌بندی ایجکسی)
	document.addEventListener('click', function (e) {
		var button = e.target.closest('.add_to_cart_button.ajax_add_to_cart:not(.product_type_variable)');
		if (!button || button.classList.contains('disabled')) {
			return;
		}
		var qty = readQty(button);
		setCount(Math.max(0, lastCount) + qty);
		document.dispatchEvent(new CustomEvent('almasara:added_to_cart', {
			detail: {
				productId: button.getAttribute('data-product_id') || null,
				quantity: qty,
				optimistic: true
			}
		}));
	}, true);

	/* ---------------- رویدادهای هماهنگی ---------------- */

	// toast متمرکز + آنالیتیکس‌پسند: هر افزودنی این رویداد را می‌فرستد
	document.addEventListener('almasara:added_to_cart', function () {
		toast((CFG.toast && CFG.toast.text) || (CFG.i18n && CFG.i18n.added) || '');
	});

	// خطاها (از ویجت یا هر مصرف‌کننده دیگر)
	document.addEventListener('almasara:cart_error', function (e) {
		toast((e.detail && e.detail.message) || (CFG.i18n && CFG.i18n.addFailed) || '', true);
	});

	// عددِ معتبرِ سرور بعد از add/update ویجت
	document.addEventListener('almasara:cart_count', function (e) {
		if (e.detail && typeof e.detail.count !== 'undefined') {
			setCount(parseInt(e.detail.count, 10) || 0);
		}
	});

	// آشتی بعد از افزودن بومی ووکامرس (کوکی به‌روز شده)
	if (window.jQuery) {
		window.jQuery(document.body).on('added_to_cart wc_fragments_refreshed wc_fragments_loaded', function () {
			reconcileFromCookie();
		});
	}

	// برگشت فوکوس به تب: شاید سبد در تب/صفحه دیگری عوض شده باشد
	document.addEventListener('visibilitychange', function () {
		if (!document.hidden) {
			reconcileFromCookie();
		}
	});

	/* ---------------- پیش‌بارگذاری صفحه سبد ---------------- */

	function injectSpeculationRules() {
		if (!CFG.prefetch || !CFG.cartUrl) {
			return;
		}
		if (!HTMLScriptElement.supports || !HTMLScriptElement.supports('speculationrules')) {
			return;
		}
		var script = document.createElement('script');
		script.type = 'speculationrules';
		script.textContent = JSON.stringify({
			prerender: [{ source: 'list', urls: [CFG.cartUrl], eagerness: 'moderate' }]
		});
		document.head.appendChild(script);
	}

	/* ---------------- شروع ---------------- */

	function init() {
		reconcileFromCookie();
		injectSpeculationRules();
	}

	if (document.readyState !== 'loading') {
		init();
	} else {
		document.addEventListener('DOMContentLoaded', init);
	}
})();
