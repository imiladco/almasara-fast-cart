/**
 * ویجت افزودن به سبد — افزونه سبد سریع الماسارا
 *
 * - محصول ساده و متغیر (افزودن مستقیم واریانت از endpoint wc-ajax همین افزونه)
 * - جایگزینی خودکار دکمه با کنترل «در سبد شما» وقتی محصول/واریانت در سبد است
 * - لودر دایره‌ای (تخلیه ۳۶۰ درجه) هنگام تغییر تعداد، حالت «حداکثر»
 * - هماهنگی با هسته (fast-cart.js) از طریق رویدادهای almasara:*
 */
(function () {
	'use strict';

	var CFG = window.AMFC || {};
	var FA = '۰۱۲۳۴۵۶۷۸۹';

	function fa(n) {
		return String(n).replace(/\d/g, function (d) { return FA[d]; });
	}

	function mapKey(pid, vid) {
		return pid + ':' + (vid || 0);
	}

	/* ---------------- وضعیت مشترک سبد بین همه ویجت‌های صفحه ---------------- */

	var itemsMap = {};      // "pid:vid" → {key, quantity, max}
	var itemsPromise = null;
	var roots = [];

	function liveRoots() {
		roots = roots.filter(function (r) { return document.contains(r); });
		return roots;
	}

	function persistItems() {
		if (window.AMFCStore) {
			window.AMFCStore.merge({ items: itemsMap });
		}
	}

	// آدرس endpoint روی کانال بومی wc-ajax (سشن/لاگین مثل مرور عادی)
	function endpointUrl(name) {
		return (CFG.ajaxUrl || '/?wc-ajax=%%endpoint%%').replace('%%endpoint%%', name);
	}

	function loadItems(force) {
		if (itemsPromise && !force) {
			return itemsPromise;
		}
		itemsPromise = fetch(endpointUrl('amfc_items'), { credentials: 'same-origin' })
			.then(function (r) { return r.ok ? r.json() : null; })
			.then(function (res) {
				var data = res && res.success ? res.data : { items: [] };
				itemsMap = {};
				(data.items || []).forEach(function (it) {
					itemsMap[mapKey(it.product_id, it.variation_id)] = {
						key: it.key, quantity: it.quantity, max: it.max
					};
				});
				persistItems();
				return itemsMap;
			})
			.catch(function () { return itemsMap; });
		return itemsPromise;
	}

	function api(name, body) {
		var params = new URLSearchParams();
		Object.keys(body || {}).forEach(function (k) {
			if (k === 'variation') {
				Object.keys(body.variation).forEach(function (vk) {
					params.append('variation[' + vk + ']', body.variation[vk]);
				});
			} else {
				params.append(k, body[k]);
			}
		});
		return fetch(endpointUrl(name), {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: params.toString()
		}).then(function (r) {
			return r.json().then(function (d) {
				// قالب wp_send_json_success/error: {success, data}
				return { ok: r.ok && d && d.success, data: (d && d.data) || {} };
			}).catch(function () {
				// پاسخ JSON نبود (کش/افزونه امنیتی/خطای PHP) — وضعیت را نشان بده
				return {
					ok: false,
					data: { message: ((CFG.i18n && CFG.i18n.netError) || 'خطا') + ' (HTTP ' + r.status + ')' }
				};
			});
		});
	}

	/** اعمال fragmentهای HTML پوسته (مینی‌کارت هدر و ...) */
	function applyFragments(fragments) {
		if (!fragments) {
			return;
		}
		Object.keys(fragments).forEach(function (selector) {
			document.querySelectorAll(selector).forEach(function (el) {
				el.outerHTML = fragments[selector];
			});
		});
	}

	function emit(name, detail) {
		document.dispatchEvent(new CustomEvent(name, { detail: detail || {} }));
	}

	/* ---------------- حالت‌های نمایشی هر ویجت ---------------- */

	function currentTarget(root) {
		var pid = parseInt(root.dataset.product, 10) || 0;
		if (root.dataset.type === 'variable') {
			var vidInput = root.querySelector('input.variation_id');
			var vid = vidInput ? parseInt(vidInput.value, 10) || 0 : 0;
			return vid ? { pid: pid, vid: vid } : null; // بدون انتخاب کامل، هدفی نیست
		}
		return { pid: pid, vid: 0 };
	}

	function refreshState(root) {
		var incart = root.querySelector('.amfc-atc__incart');
		var target = currentTarget(root);
		var info = target ? itemsMap[mapKey(target.pid, target.vid)] : null;

		root.classList.toggle('amfc-atc--incart', !!info);
		if (incart) {
			incart.hidden = !info;
		}
		if (info) {
			root.querySelector('.amfc-atc__control').dataset.key = info.key;
			paintControl(root, info.quantity, info.max);
		}
	}

	function refreshAll() {
		liveRoots().forEach(refreshState);
		updateStickyPad();
	}

	/* ---------------- نوار چسبان موبایل ---------------- */

	// نوار fixed محتوای انتهای صفحه را می‌پوشاند؛ به‌اندازه بلندترین نوارِ
	// دیده‌شده به body پدینگ پایین می‌دهیم (متغیر CSS + کلاس، بدون دستکاری مستقیم)
	var stickyMq = window.matchMedia('(max-width: 767px)');

	function updateStickyPad() {
		var pad = 0;
		if (stickyMq.matches) {
			liveRoots().forEach(function (root) {
				if (!root.classList.contains('amfc-atc--stickym')) {
					return;
				}
				var el = root.classList.contains('amfc-atc--incart')
					? root.querySelector('.amfc-atc__incart')
					: root.querySelector('.amfc-atc__addrow');
				if (el && el.offsetHeight) {
					var rect = el.getBoundingClientRect();
					var bottom = parseFloat(getComputedStyle(el).bottom) || 0;
					pad = Math.max(pad, rect.height + bottom + 12);
				}
			});
		}
		document.body.classList.toggle('amfc-stickym-pad', pad > 0);
		document.body.style.setProperty('--amfc-sticky-pad', Math.ceil(pad) + 'px');
	}

	window.addEventListener('resize', updateStickyPad);
	if (stickyMq.addEventListener) {
		stickyMq.addEventListener('change', updateStickyPad);
	}

	function paintControl(root, qty, max) {
		var control = root.querySelector('.amfc-atc__control');
		control.dataset.qty = qty;
		control.dataset.max = max || 0;
		root.querySelector('.amfc-atc__ctl-value').textContent = fa(qty);
		control.classList.toggle('is-single', qty <= 1); // کاهش → آیکون حذف
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
		if (!btn) {
			return;
		}
		btn.classList.toggle('is-loading', on);
		btn.disabled = on;
		btn.querySelector('.amfc-atc__btn-in').hidden = on;
		btn.querySelector('.amfc-atc__btn-loader').hidden = !on;
	}

	/* ---------------- افزودن ---------------- */

	function collectQty(root) {
		var input = root.querySelector('.amfc-atc__qty-input');
		return input ? (parseInt(input.value, 10) || 1) : 1;
	}

	function doAdd(root) {
		var pid = parseInt(root.dataset.product, 10) || 0;
		var body = { product_id: pid, quantity: collectQty(root) };

		if (root.dataset.type === 'variable') {
			var vidInput = root.querySelector('input.variation_id');
			body.variation_id = vidInput ? parseInt(vidInput.value, 10) || 0 : 0;
			if (!body.variation_id) {
				return; // دکمه در این حالت disabled است؛ گارد اضافه
			}
			body.variation = {};
			root.querySelectorAll('select[name^="attribute_"]').forEach(function (sel) {
				body.variation[sel.name] = sel.value;
			});
		}

		buttonLoading(root, true);
		api('amfc_add', body).then(function (res) {
			buttonLoading(root, false);
			if (res.ok) {
				itemsMap[mapKey(pid, body.variation_id || 0)] = {
					key: res.data.key, quantity: res.data.quantity, max: res.data.max
				};
				persistItems();
				applyFragments(res.data.fragments);
				emit('almasara:cart_count', { count: res.data.count });
				emit('almasara:added_to_cart', {
					productId: pid, variationId: body.variation_id || 0, quantity: body.quantity
				});
				refreshAll();
			} else {
				emit('almasara:cart_error', { message: res.data && res.data.message });
			}
		}).catch(function () {
			buttonLoading(root, false);
			emit('almasara:cart_error', { message: CFG.i18n && CFG.i18n.netError });
		});
	}

	/* ---------------- تغییر تعداد / حذف ---------------- */

	function doUpdate(root, newQty) {
		var control = root.querySelector('.amfc-atc__control');
		var cartKey = control.dataset.key;
		if (!cartKey || control.classList.contains('is-loading')) {
			return;
		}

		controlLoading(root, true);
		api('amfc_update', { key: cartKey, quantity: newQty }).then(function (res) {
			controlLoading(root, false);
			if (!res.ok) {
				return;
			}
			applyFragments(res.data.fragments);
			var target = currentTarget(root);
			if (res.data.removed) {
				if (target) {
					delete itemsMap[mapKey(target.pid, target.vid)];
				}
			} else if (target && itemsMap[mapKey(target.pid, target.vid)]) {
				itemsMap[mapKey(target.pid, target.vid)].quantity = res.data.quantity;
				itemsMap[mapKey(target.pid, target.vid)].max = res.data.max;
			}
			persistItems();
			emit('almasara:cart_count', { count: res.data.count });
			refreshAll();
		}).catch(function () {
			controlLoading(root, false);
		});
	}

	/* ---------------- ساخت جعبه قیمت واریانت ---------------- */

	function escText(s) {
		return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}

	// تنظیمات قیمت که PHP روی ریشه ویجت گذاشته (واحد پول، نمایش قیمت پیشین و ...)
	function priceCfg(root) {
		if (!root.__amfcPriceCfg) {
			try {
				root.__amfcPriceCfg = JSON.parse(root.dataset.priceCfg || '{}');
			} catch (e) {
				root.__amfcPriceCfg = {};
			}
		}
		return root.__amfcPriceCfg;
	}

	function fillVariablePrice(root, variation) {
		var box = root.querySelector('[data-role="price"]');
		if (!box) {
			return;
		}
		if (!variation) {
			box.innerHTML = '';
			return;
		}
		var cfg = priceCfg(root);
		var active = parseFloat(variation.display_price);
		var regular = parseFloat(variation.display_regular_price);
		var onSale = regular > active && regular > 0;
		var unit = cfg.unit ? '<span class="amfc-atc__unit">' + escText(cfg.unit) + '</span>' : '';
		var html = '';

		if (onSale && cfg.badge !== false) {
			var pct = Math.round((regular - active) / regular * 100);
			html += '<span class="amfc-atc__discount">' + fa(pct) +
				'<svg viewBox="0 0 24 24" width="0.9em" height="0.9em" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M9 15 15 9M9.5 9.5h.01M14.5 14.5h.01"/></svg></span>';
		}
		if (onSale && cfg.old !== false) {
			html += '<del class="amfc-atc__price amfc-atc__price--old"><span class="amfc-atc__num">' + fa(regular.toLocaleString('en-US')) + '</span>' +
				(cfg.unitOld ? unit : '') + '</del>';
		}
		html += '<span class="amfc-atc__price amfc-atc__price--now">';
		if (!(active > 0) && cfg.free) {
			html += '<span class="amfc-atc__num amfc-atc__num--free">' + escText(cfg.free) + '</span>';
		} else {
			html += '<span class="amfc-atc__num">' + fa(active.toLocaleString('en-US')) + '</span>' + (cfg.unitNow !== false ? unit : '');
		}
		html += '</span>';
		box.innerHTML = html;
	}

	/* ---------------- راه‌اندازی هر ویجت ---------------- */

	function setup(root) {
		if (root.__amfcAtc) {
			return;
		}
		root.__amfcAtc = true;
		roots.push(root);

		// استپر تعداد هنگام افزودن
		var qtyInput = root.querySelector('.amfc-atc__qty-input');
		function clampAdd(v) {
			var max = qtyInput ? parseInt(qtyInput.getAttribute('max'), 10) || 0 : 0;
			v = parseInt(v, 10) || 1;
			if (v < 1) { v = 1; }
			if (max > 0 && v > max) { v = max; }
			return v;
		}
		var qMinus = root.querySelector('.amfc-atc__step--minus');
		var qPlus = root.querySelector('.amfc-atc__step--plus');
		if (qMinus && qtyInput) {
			qMinus.addEventListener('click', function () {
				qtyInput.value = clampAdd((parseInt(qtyInput.value, 10) || 1) - 1);
			});
		}
		if (qPlus && qtyInput) {
			qPlus.addEventListener('click', function () {
				qtyInput.value = clampAdd((parseInt(qtyInput.value, 10) || 1) + 1);
			});
		}

		// افزودن
		if (root.dataset.type === 'variable') {
			var form = root.querySelector('.amfc-atc__variations');
			if (form && window.jQuery) {
				var $form = window.jQuery(form);
				// init صریح (برای المنتور/رندر داینامیک؛ auto-init فقط در لود اولیه است)
				if (window.jQuery.fn.wc_variation_form && !$form.data('product_id_initialized')) {
					$form.data('product_id_initialized', true);
					$form.wc_variation_form();
				}
				// تا انتخاب کامل واریانت، دکمه واقعاً disabled است (استایل تب «غیرفعال»)
				var vbtn = root.querySelector('.amfc-atc__btn');
				if (vbtn) {
					vbtn.disabled = true;
				}
				$form.on('found_variation', function (ev, variation) {
					if (vbtn) {
						vbtn.disabled = !(variation && variation.is_purchasable && variation.is_in_stock);
					}
					fillVariablePrice(root, variation);
					refreshState(root);
				});
				$form.on('reset_data hide_variation', function () {
					if (vbtn) {
						vbtn.disabled = true;
					}
					fillVariablePrice(root, null);
					refreshState(root);
				});
				$form.on('submit', function (e) {
					e.preventDefault();
					doAdd(root);
				});
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
				if (control.classList.contains('is-max')) {
					return;
				}
				doUpdate(root, (parseInt(control.dataset.qty, 10) || 1) + 1);
			});
		}
		if (dec) {
			dec.addEventListener('click', function () {
				var control = root.querySelector('.amfc-atc__control');
				doUpdate(root, (parseInt(control.dataset.qty, 10) || 1) - 1); // صفر → حذف
			});
		}
	}

	/* ---------------- init ---------------- */

	var hydrated = false;

	function initAll(scope) {
		(scope || document).querySelectorAll('.amfc-atc').forEach(setup);

		// هیدریت آنی حالت «در سبد» از آینه محلی (IndexedDB) — بدون انتظار شبکه؛
		// سپس revalidate پس‌زمینه از سرور (stale-while-revalidate)
		if (!hydrated && window.AMFCStore) {
			hydrated = true;
			window.AMFCStore.get().then(function (snap) {
				if (snap && snap.items && !Object.keys(itemsMap).length) {
					itemsMap = snap.items;
					refreshAll();
				}
			});
		}

		loadItems().then(refreshAll);
	}

	// افزودن بومی (آرشیو و ...) هم نقشه اقلام را کهنه می‌کند — تازه‌سازی
	if (window.jQuery) {
		window.jQuery(document.body).on('added_to_cart', function () {
			loadItems(true).then(refreshAll);
		});
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
