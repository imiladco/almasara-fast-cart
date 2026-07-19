/**
 * Almasara Fast Cart — لایه خوش‌بینانه افزودن به سبد (رسپی ۱+)
 *
 * اصل: لایه بهبود روی مکانیزم بومی ووکامرس، نه جایگزین آن. اگر افزونه
 * غیرفعال شود، افزودن به سبدِ استاندارد ووکامرس دست‌نخورده کار می‌کند.
 *
 * شامل: هیدریت بج از کوکی بومی (ضد کش)، افزایش خوش‌بینانه با event
 * delegation، آشتی با کوکی روی رویداد added_to_cart، sync چندتب با
 * BroadcastChannel، رویداد سفارشی برای آنالیتیکس، و پیش‌بارگذاری صفحه سبد.
 */
(function () {
	'use strict';

	var CFG = window.AMFC || {};
	var COUNT_COOKIE = 'woocommerce_items_in_cart';

	/* ---------------- ابزارها ---------------- */

	function getCookie(name) {
		var m = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
		return m ? decodeURIComponent(m.pop()) : '';
	}

	function cartCountFromCookie() {
		return parseInt(getCookie(COUNT_COOKIE), 10) || 0;
	}

	var FA = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
	function toFa(n) {
		return String(n).replace(/\d/g, function (d) { return FA[d]; });
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

	// channel بین تب‌ها؛ set محلی broadcast می‌کند، دریافت فقط رنگ می‌کند
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

	/* ---------------- آشتی با سرور ---------------- */

	// منبع حقیقت فوری = کوکی بومی ووکامرس (کش نمی‌شود، بدون nonce)
	function reconcileFromCookie() {
		setCount(cartCountFromCookie());
	}

	// خلاصه دقیق (جمع کل/مینی‌کارت) از endpoint سبک — فقط وقتی لازم شد
	function fetchSummary() {
		if (!CFG.summaryUrl) {
			return Promise.resolve(null);
		}
		return fetch(CFG.summaryUrl, {
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': CFG.nonce || '' }
		})
			.then(function (r) { return r.ok ? r.json() : null; })
			.then(function (data) {
				if (data && typeof data.count !== 'undefined') {
					setCount(data.count);
				}
				return data;
			})
			.catch(function () { return null; });
	}

	/* ---------------- اعلان (Toast) ---------------- */

	var toastEl = null;
	var toastTimer = null;

	function toast(msg) {
		if (!CFG.toast || !CFG.toast.enabled) {
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
		toastEl.classList.add('is-visible');
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () {
			toastEl.classList.remove('is-visible');
		}, 2500);
	}

	/* ---------------- افزودن خوش‌بینانه ---------------- */

	function isVerifiedOK() {
		// گیت تأیید هویت پوسته: اگر صراحتاً false بود، دخالت نکن (ریدایرکت پوسته)
		return window.isVerified !== false;
	}

	function readQty(button) {
		// آرشیو: data-quantity؛ صفحه محصول: input.qty نزدیک دکمه
		var q = parseInt(button.getAttribute('data-quantity'), 10);
		if (q > 0) {
			return q;
		}
		var form = button.closest('form.cart, form');
		var input = form ? form.querySelector('input.qty, input[name="quantity"]') : null;
		q = input ? parseInt(input.value, 10) : 1;
		return q > 0 ? q : 1;
	}

	function optimisticAdd(button) {
		if (!isVerifiedOK()) {
			return; // بگذار رفتار پوسته (ریدایرکت به verify) اجرا شود
		}
		var qty = readQty(button);
		setCount(lastCount + qty);
		toast((CFG.toast && CFG.toast.text) || (CFG.i18n && CFG.i18n.added) || '');

		// رویداد سفارشی برای آنالیتیکس (GA4/Pixel/یکتانت به این گوش دهند)
		document.dispatchEvent(new CustomEvent('almasara:added_to_cart', {
			detail: {
				productId: button.getAttribute('data-product_id') || null,
				quantity: qty
			}
		}));
	}

	// event delegation: دکمه‌های حال و آینده (quick-view، صفحه‌بندی ایجکسی، ویجت‌ها)
	document.addEventListener('click', function (e) {
		var button = e.target.closest('.add_to_cart_button:not(.product_type_variable), .single_add_to_cart_button');
		if (!button || button.classList.contains('disabled')) {
			return;
		}
		optimisticAdd(button);
	}, true);

	/* ---------------- گوش به رویداد بومی ووکامرس ---------------- */

	// ووکامرس رویدادهایش را با jQuery روی body می‌زند؛ فرصت‌طلبانه هوک می‌کنیم
	if (window.jQuery) {
		window.jQuery(document.body).on('added_to_cart', function () {
			// کوکی توسط ووکامرس به‌روز شده؛ عدد دقیق را جایگزین افزایش خوش‌بینانه کن
			reconcileFromCookie();
		});
		window.jQuery(document.body).on('wc_fragments_refreshed wc_fragments_loaded', function () {
			reconcileFromCookie();
		});
	}

	// اگر تب دوباره فوکوس شد، ممکن است سبد در تب دیگری عوض شده باشد
	document.addEventListener('visibilitychange', function () {
		if (!document.hidden) {
			reconcileFromCookie();
		}
	});

	/* ---------------- پیش‌بارگذاری صفحه سبد (Speculation Rules) ---------------- */

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
			prerender: [{
				source: 'list',
				urls: [CFG.cartUrl],
				eagerness: 'moderate'
			}]
		});
		document.head.appendChild(script);
	}

	/* ---------------- شروع ---------------- */

	function init() {
		reconcileFromCookie(); // بج آنی از کوکی، ضد کش، بدون درخواست سرور
		injectSpeculationRules();
	}

	if (document.readyState !== 'loading') {
		init();
	} else {
		document.addEventListener('DOMContentLoaded', init);
	}
})();
