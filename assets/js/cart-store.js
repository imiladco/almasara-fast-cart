/**
 * AMFCStore — آینه محلی سبد در IndexedDB (الگوی local-first سایت نمونه)
 *
 * snapshot: { count: number, items: { "pid:vid": {key, quantity, max} }, updatedAt }
 *
 * نقش: هیدریتِ آنی UI (بج + حالت «در سبد») بدون هیچ درخواست شبکه در لود صفحه؛
 * سرور همچنان مرجع نهایی است و در پس‌زمینه revalidate می‌شود
 * (stale-while-revalidate). در نبود IndexedDB بی‌صدا no-op می‌شود.
 */
window.AMFCStore = (function () {
	'use strict';

	var DB_NAME = 'amfc-cart';
	var STORE = 'kv';
	var KEY = 'snapshot';
	var supported = 'indexedDB' in window;

	function open() {
		return new Promise(function (resolve, reject) {
			var req = indexedDB.open(DB_NAME, 1);
			req.onupgradeneeded = function () {
				req.result.createObjectStore(STORE);
			};
			req.onsuccess = function () { resolve(req.result); };
			req.onerror = function () { reject(req.error); };
		});
	}

	function get() {
		if (!supported) {
			return Promise.resolve(null);
		}
		return open().then(function (db) {
			return new Promise(function (resolve) {
				var tx = db.transaction(STORE, 'readonly').objectStore(STORE).get(KEY);
				tx.onsuccess = function () { resolve(tx.result || null); };
				tx.onerror = function () { resolve(null); };
			});
		}).catch(function () { return null; });
	}

	function set(snapshot) {
		if (!supported) {
			return Promise.resolve(false);
		}
		snapshot.updatedAt = Date.now();
		return open().then(function (db) {
			return new Promise(function (resolve) {
				var tx = db.transaction(STORE, 'readwrite');
				tx.objectStore(STORE).put(snapshot, KEY);
				tx.oncomplete = function () { resolve(true); };
				tx.onerror = function () { resolve(false); };
			});
		}).catch(function () { return false; });
	}

	/** ادغام جزئی: فقط کلیدهای داده‌شده به‌روز می‌شوند */
	function merge(partial) {
		return get().then(function (snap) {
			snap = snap || { count: 0, items: {} };
			if (typeof partial.count !== 'undefined') {
				snap.count = partial.count;
			}
			if (typeof partial.items !== 'undefined') {
				snap.items = partial.items;
			}
			return set(snap);
		});
	}

	return { get: get, set: set, merge: merge };
})();
