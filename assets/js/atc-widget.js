/**
 * ویجت افزودن به سبد (افزونه سبد سریع الماسارا)
 *
 * - محصول ساده و متغیر (افزودن مستقیم واریانت)
 * - جایگزینی خودکار دکمه با کنترل «در سبد شما» وقتی در سبد است
 * - افزودن/تغییر تعداد از REST همین افزونه؛ بجِ سبد از fast-cart.js
 * - لودر دایره‌ای هنگام تغییر تعداد و حالت «حداکثر»
 */
(function () {
	'use strict';

	var CFG = window.AMFC || {};
	var FA = '۰۱۲۳۴۵۶۷۸۹';

	function fa(n) {
		return String(n).replace(/\d/g, function (d) { return FA[d]; });
	}

	function key(pid, vid) {
		return pid + ':' + (vid || 0);
	}

	/* ---------------- وضعیت مشترک سبد ---------------- */

	var itemsMap = {};      // "pid:vid" -> {key, quantity, max}
	var itemsLoaded = null; // promise
	var roots = [];

	function loadItems(force) {
		if (itemsLoaded && !force) {
			return itemsLoaded;
		}
		itemsLoaded = fetch(CFG.restBase + '/items', {
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': CFG.nonce || '' }
		})
			.then(function (r) { return r.ok ? r.json() : { items: [] }; })
			.then(function (data) {
				itemsMap = {};
				(data.items || []).forEach(function (it) {
					itemsMap[key(it.product_id, it.variation_id)] = {
						key: it.key, quantity: it.quantity, max: it.max
					};
				});
				return itemsMap;
			})
			.catch(function () { itemsMap = {}; return itemsMap; });
		return itemsLoaded;
	}

	function api(path, body) {
		return fetch(CFG.restBase + path, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': CFG.nonce || '' },
			body: JSON.stringify(body || {})
		}).then(function (r) {
			return r.json().then(function (d) { return { ok: r.ok, data: d }; });
		});
	}

	function pushCount(count) {
		document.dispatchEvent(new CustomEvent('almasara:cart_count', { detail: { count: count } }));
	}

	/* ---------------- نمایش حالت‌ها ---------------- */

	function showAddState(root) {
		root.classList.remove('amfc-atc--incart');
	}

	function showIncartState(root, info) {
		root.classList.add('amfc-atc--incart');
		var control = root.querySelector('.amfc-atc__control');
		control.dataset.key = info.key;
		paintControl(root, info.quantity, info.max);
	}

	function paintControl(root, qty, max) {
		var control = root.querySelector('.amfc-atc__control');
		control.dataset.qty = qty;
		control.dataset.max = max || 0;
		root.querySelector('.amfc-atc__ctl-value').textContent = fa(qty);
		// حالت تک (دکمه حذف) وقتی تعداد ۱ است
		control.classList.toggle('is-single', qty <= 1);
		// حالت حداکثر
		var atMax = max > 0 && qty >= max;
		control.classList.toggle('is-max', atMax);
		root.querySelector('.amfc-atc__ctl-max').hidden = !atMax;
	}

	function controlLoading(root, on) {
		root.querySelector('.amfc-atc__ctl-value').hidden = on;
		root.querySelector('.amfc-atc__ctl-loader').hidden = !on;
		root.querySelector('.amfc-atc__control').classList.toggle('is-loading', on);
	}

	function buttonLoading(root, on) {
		var btn = root.querySelector('.amfc-atc__btn');
		if (!btn) { return; }
		btn.classList.toggle('is-loading', on);
		btn.disabled = on;
		var inner = btn.querySelector('.amfc-atc__btn-in');
		var loader = btn.querySelector('.amfc-atc__btn-loader');
		if (inner) { inner.hidden = on; }
		if (loader) { loader.hidden = !on; }
	}

	/* ---------------- تشخیص هدف هر ویجت ---------------- */

	function currentTarget(root) {
		var pid = parseInt(root.dataset.product, 10) || 0;
		if (root.dataset.type === 'variable') {
			var vidInput = root.querySelector('input.variation_id');
			var vid = vidInput ? parseInt(vidInput.value, 10) || 0 : 0;
			return vid ? { pid: pid, vid: vid } : null; // بدون انتخاب واریانت هدفی نیست
		}
		return { pid: pid, vid: 0 };
	}

	function refreshState(root) {
		var t = currentTarget(root);
		if (!t) {
			showAddState(root);
			return;
		}
		var info = itemsMap[key(t.pid, t.vid)];
		if (info) {
			showIncartState(root, info);
		} else {
			showAddState(root);
		}
	}

	function refreshAll() {
		roots.forEach(refreshState);
	}

	/* ---------------- افزودن ---------------- */

	function collectQty(root) {
		var input = root.querySelector('.amfc-atc__qty-input');
		return input ? (parseInt(input.value, 10) || 1) : 1;
	}

	function collectVariation(root) {
		var out = { variation_id: 0, variation: {} };
		var vidInput = root.querySelector('input.variation_id');
		out.variation_id = vidInput ? parseInt(vidInput.value, 10) || 0 : 0;
		root.querySelectorAll('select[name^="attribute_"]').forEach(function (sel) {
			out.variation[sel.name] = sel.value;
		});
		return out;
	}

	function doAdd(root) {
		var pid = parseInt(root.dataset.product, 10) || 0;
		var body = { product_id: pid, quantity: collectQty(root) };

		if (root.dataset.type === 'variable') {
			var v = collectVariation(root);
			if (!v.variation_id) { return; } // واریانت انتخاب نشده
			body.variation_id = v.variation_id;
			body.variation = v.variation;
		}

		buttonLoading(root, true);
		api('/add', body).then(function (res) {
			buttonLoading(root, false);
			if (res.ok && res.data && res.data.success) {
				var t = currentTarget(root) || { pid: pid, vid: body.variation_id || 0 };
				itemsMap[key(t.pid, t.vid)] = {
					key: res.data.key, quantity: res.data.quantity, max: res.data.max
				};
				pushCount(res.data.count);
				document.dispatchEvent(new CustomEvent('almasara:added_to_cart', {
					detail: { productId: pid, variationId: body.variation_id || 0, quantity: body.quantity }
				}));
				refreshAll();
			} else {
				flashError(root, (res.data && res.data.message) || 'خطا در افزودن به سبد');
			}
		}).catch(function () {
			buttonLoading(root, false);
			flashError(root, 'ارتباط با سرور برقرار نشد');
		});
	}

	function flashError(root, msg) {
		var btn = root.querySelector('.amfc-atc__btn');
		if (btn) {
			btn.setAttribute('data-amfc-error', msg);
			btn.classList.add('amfc-atc__btn--error');
			setTimeout(function () { btn.classList.remove('amfc-atc__btn--error'); }, 2500);
		}
	}

	/* ---------------- تغییر تعداد در سبد ---------------- */

	function doUpdate(root, newQty) {
		var control = root.querySelector('.amfc-atc__control');
		var cartKey = control.dataset.key;
		if (!cartKey) { return; }

		controlLoading(root, true);
		api('/update', { key: cartKey, quantity: newQty }).then(function (res) {
			controlLoading(root, false);
			if (!res.ok || !res.data) {
				return;
			}
			var t = currentTarget(root);
			if (res.data.removed) {
				if (t) { delete itemsMap[key(t.pid, t.vid)]; }
				pushCount(res.data.count);
				refreshAll();
			} else {
				if (t && itemsMap[key(t.pid, t.vid)]) {
					itemsMap[key(t.pid, t.vid)].quantity = res.data.quantity;
					itemsMap[key(t.pid, t.vid)].max = res.data.max;
				}
				paintControl(root, res.data.quantity, res.data.max);
				pushCount(res.data.count);
			}
		}).catch(function () {
			controlLoading(root, false);
		});
	}

	/* ---------------- راه‌اندازی هر ویجت ---------------- */

	function setup(root) {
		if (root.__amfcAtc) { return; }
		root.__amfcAtc = true;
		roots.push(root);

		// استپر تعداد هنگام افزودن
		var qtyInput = root.querySelector('.amfc-atc__qty-input');
		var qMinus = root.querySelector('.amfc-atc__step--minus');
		var qPlus = root.querySelector('.amfc-atc__step--plus');
		function clampAdd(v) {
			var max = qtyInput ? parseInt(qtyInput.getAttribute('max'), 10) || 0 : 0;
			v = parseInt(v, 10) || 1;
			if (v < 1) { v = 1; }
			if (max > 0 && v > max) { v = max; }
			return v;
		}
		if (qMinus && qtyInput) {
			qMinus.addEventListener('click', function () { qtyInput.value = clampAdd((parseInt(qtyInput.value, 10) || 1) - 1); });
		}
		if (qPlus && qtyInput) {
			qPlus.addEventListener('click', function () { qtyInput.value = clampAdd((parseInt(qtyInput.value, 10) || 1) + 1); });
		}

		// افزودن
		if (root.dataset.type === 'variable') {
			var form = root.querySelector('.amfc-atc__variations');
			if (form) {
				form.addEventListener('submit', function (e) {
					e.preventDefault();
					doAdd(root);
				});
				// رویدادهای واریانت WooCommerce (jQuery) → قیمت و بررسی وجود در سبد
				if (window.jQuery) {
					window.jQuery(form).on('found_variation', function (ev, variation) {
						fillVariablePrice(root, variation);
						refreshState(root);
					});
					window.jQuery(form).on('reset_data', function () {
						var box = root.querySelector('[data-role="price"]');
						if (box) { box.innerHTML = ''; }
						refreshState(root);
					});
				}
			}
		} else {
			var btn = root.querySelector('.amfc-atc__btn');
			if (btn) {
				btn.addEventListener('click', function (e) {
					e.preventDefault();
					doAdd(root);
				});
			}
		}

		// کنترل «در سبد»
		var inc = root.querySelector('.amfc-atc__ctl--inc');
		var dec = root.querySelector('.amfc-atc__ctl--dec');
		if (inc) {
			inc.addEventListener('click', function () {
				var control = root.querySelector('.amfc-atc__control');
				if (control.classList.contains('is-max') || control.classList.contains('is-loading')) { return; }
				doUpdate(root, (parseInt(control.dataset.qty, 10) || 1) + 1);
			});
		}
		if (dec) {
			dec.addEventListener('click', function () {
				var control = root.querySelector('.amfc-atc__control');
				if (control.classList.contains('is-loading')) { return; }
				var q = parseInt(control.dataset.qty, 10) || 1;
				doUpdate(root, q - 1); // ۰ → حذف
			});
		}
	}

	/** ساخت جعبه قیمت واریانت از داده‌ی found_variation */
	function fillVariablePrice(root, variation) {
		var box = root.querySelector('[data-role="price"]');
		if (!box || !variation) { return; }
		var active = parseFloat(variation.display_price);
		var regular = parseFloat(variation.display_regular_price);
		var onSale = variation.is_on_sale || (regular > active);
		var html = '';
		if (onSale && regular > 0) {
			var pct = Math.round((regular - active) / regular * 100);
			html += '<span class="amfc-atc__discount">' + fa(pct.toLocaleString('en-US')) +
				'<svg viewBox="0 0 24 24" width="0.9em" height="0.9em" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M9 15 15 9M9.5 9.5h.01M14.5 14.5h.01"/></svg></span>';
			html += '<del class="amfc-atc__regular">' + fa(regular.toLocaleString('en-US')) + '</del>';
		}
		html += '<span class="amfc-atc__final">' + fa(active.toLocaleString('en-US')) + '</span>';
		html += '<span class="amfc-atc__currency">تومان</span>';
		box.innerHTML = html;
	}

	/* ---------------- init ---------------- */

	function initAll(scope) {
		(scope || document).querySelectorAll('.amfc-atc').forEach(setup);
		loadItems().then(refreshAll);
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		window.elementorFrontend.hooks.addAction('frontend/element_ready/amfc-add-to-cart.default', function ($el) {
			initAll($el && $el[0] ? $el[0] : document);
		});
	}

	if (document.readyState !== 'loading') {
		initAll(document);
	} else {
		document.addEventListener('DOMContentLoaded', function () { initAll(document); });
	}
})();
