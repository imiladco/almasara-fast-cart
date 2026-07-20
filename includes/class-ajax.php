<?php
namespace Almasara_Fast_Cart;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * endpointهای سبد روی wc-ajax (کانال بومی خود ووکامرس).
 *
 * چرا wc-ajax و نه REST: درخواست REST بدون نانس «لاگین‌نشده» حساب می‌شود و
 * ووکامرس کوکی سشنِ کاربر لاگین‌شده را برای بازدیدکننده ناشناس نامعتبر
 * می‌داند → سشن دوم مهمان ساخته می‌شد و سبدها از هم جدا می‌افتادند.
 * wc-ajax یک درخواست کامل فرانت است: کوکی لاگین عادی احراز می‌شود، سشن
 * دقیقاً مثل مرور عادی لود می‌شود و کش صفحه هم آن را مستثنی می‌کند.
 *
 * همه عملیات از توابع بومی WooCommerce عبور می‌کنند تا هوک‌ها و درستی
 * کوپن/مالیات/موجودی حفظ بماند.
 */
final class Ajax {

    public static function init(): void {
        add_action('wc_ajax_amfc_items', [self::class, 'items']);
        add_action('wc_ajax_amfc_add', [self::class, 'add']);
        add_action('wc_ajax_amfc_update', [self::class, 'update']);
    }

    /** گارد same-origin برای نوشتن‌ها (مثل wc-ajax خود ووکامرس، بدون نانس) */
    private static function guard_mutation(): void {
        $fetch_site = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''));
        if ('cross-site' === $fetch_site) {
            wp_send_json_error(['message' => 'forbidden'], 403);
        }
        $referer = wp_get_referer();
        if ($referer && wp_parse_url($referer, PHP_URL_HOST) !== wp_parse_url(home_url(), PHP_URL_HOST)) {
            wp_send_json_error(['message' => 'forbidden'], 403);
        }
    }

    /** حداکثر تعداد مجاز (۰ = بدون سقف) */
    private static function max_qty($product): int {
        if ($product && $product->managing_stock() && !$product->backorders_allowed()) {
            return max(0, (int) $product->get_stock_quantity());
        }
        return 0;
    }

    /**
     * fragmentهای HTML برای تازه‌سازی مینی‌کارت/عناصر پوسته بعد از هر تغییر.
     * پوسته با فیلتر amfc_fragments سلکتور→HTML خودش را ثبت می‌کند.
     */
    private static function fragments(): array {
        return (array) apply_filters('amfc_fragments', []);
    }

    /* ------------------------------------------------------------------ */

    /** اقلام سبد برای تشخیص «در سبد بودن» */
    public static function items(): void {
        nocache_headers();

        if (!Session::ensure_cart()) {
            wp_send_json_error(['message' => 'no cart'], 500);
        }

        $items = [];
        foreach (WC()->cart->get_cart() as $key => $item) {
            $items[] = [
                'key'          => $key,
                'product_id'   => (int) ($item['product_id'] ?? 0),
                'variation_id' => (int) ($item['variation_id'] ?? 0),
                'quantity'     => (int) ($item['quantity'] ?? 0),
                'max'          => self::max_qty($item['data'] ?? null),
            ];
        }

        wp_send_json_success([
            'count' => WC()->cart->get_cart_contents_count(),
            'items' => $items,
        ]);
    }

    /** افزودن محصول ساده یا واریانت */
    public static function add(): void {
        nocache_headers();
        self::guard_mutation();

        if (!Session::ensure_cart()) {
            wp_send_json_error(['message' => 'no cart'], 500);
        }

        $product_id   = absint($_POST['product_id'] ?? 0);
        $variation_id = absint($_POST['variation_id'] ?? 0);
        $quantity     = max(1, (int) ($_POST['quantity'] ?? 1));

        $variation = [];
        foreach ((array) ($_POST['variation'] ?? []) as $k => $v) {
            $variation[sanitize_key($k)] = sanitize_text_field(wp_unslash((string) $v));
        }

        if (!$product_id) {
            wp_send_json_error(['message' => __('محصول نامعتبر است.', 'almasara-fast-cart')], 400);
        }

        $added = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation);

        if (false === $added) {
            wp_send_json_error([
                'message' => self::first_error_notice() ?: __('امکان افزودن این محصول نیست.', 'almasara-fast-cart'),
            ], 400);
        }

        Session::persist();

        $cart_item = WC()->cart->get_cart()[$added] ?? null;

        wp_send_json_success([
            'key'       => $added,
            'quantity'  => (int) ($cart_item['quantity'] ?? $quantity),
            'max'       => self::max_qty($cart_item['data'] ?? null),
            'count'     => WC()->cart->get_cart_contents_count(),
            'fragments' => self::fragments(),
        ]);
    }

    /** تغییر تعداد یا حذف (quantity <= 0 → حذف) */
    public static function update(): void {
        nocache_headers();
        self::guard_mutation();

        if (!Session::ensure_cart()) {
            wp_send_json_error(['message' => 'no cart'], 500);
        }

        $key      = sanitize_text_field(wp_unslash($_POST['key'] ?? ''));
        $quantity = (int) ($_POST['quantity'] ?? 0);

        $cart = WC()->cart->get_cart();
        if (!isset($cart[$key])) {
            wp_send_json_error(['message' => __('این آیتم در سبد نیست.', 'almasara-fast-cart')], 404);
        }

        if ($quantity <= 0) {
            WC()->cart->remove_cart_item($key);
            Session::persist();
            wp_send_json_success([
                'removed'   => true,
                'count'     => WC()->cart->get_cart_contents_count(),
                'fragments' => self::fragments(),
            ]);
        }

        $max    = self::max_qty($cart[$key]['data'] ?? null);
        $at_max = false;
        if ($max > 0 && $quantity >= $max) {
            $quantity = $max;
            $at_max   = true;
        }

        WC()->cart->set_quantity($key, $quantity, true);
        Session::persist();

        wp_send_json_success([
            'removed'   => false,
            'quantity'  => $quantity,
            'max'       => $max,
            'at_max'    => $at_max,
            'count'     => WC()->cart->get_cart_contents_count(),
            'fragments' => self::fragments(),
        ]);
    }

    /** اولین پیام خطای ووکامرس */
    private static function first_error_notice(): string {
        if (!function_exists('wc_get_notices')) {
            return '';
        }
        $notices = wc_get_notices('error');
        wc_clear_notices();
        return !empty($notices[0]['notice']) ? wp_strip_all_tags($notices[0]['notice']) : '';
    }
}
