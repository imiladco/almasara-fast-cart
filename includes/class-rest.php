<?php
namespace Almasara_Fast_Cart;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * endpointهای سبک سبد — namespace اختصاصی almasara-cart/v1.
 *
 * همه عملیات از توابع بومی WooCommerce عبور می‌کنند (add_to_cart،
 * set_quantity، remove_cart_item) پس همه هوک‌ها فایر می‌شوند و درستیِ
 * کوپن/مالیات/موجودی حفظ می‌ماند.
 *
 * عمداً بدون نانس REST کار می‌کنیم (مثل wc-ajax خود ووکامرس): نانسِ
 * جاسازی‌شده در صفحه‌ی کش‌شده بعد از انقضا همه‌چیز را 403 می‌کند. هویت از
 * کوکی سشن ووکامرس می‌آید و برای mutation یک بررسی same-origin سبک داریم.
 */
final class Rest {

    const NS = 'almasara-cart/v1';

    public static function init(): void {
        add_action('rest_api_init', [self::class, 'register']);
    }

    public static function register(): void {
        register_rest_route(self::NS, '/summary', [
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => [self::class, 'summary'],
        ]);

        register_rest_route(self::NS, '/items', [
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => [self::class, 'items'],
        ]);

        register_rest_route(self::NS, '/add', [
            'methods'             => 'POST',
            'permission_callback' => [self::class, 'can_mutate'],
            'callback'            => [self::class, 'add'],
        ]);

        register_rest_route(self::NS, '/update', [
            'methods'             => 'POST',
            'permission_callback' => [self::class, 'can_mutate'],
            'callback'            => [self::class, 'update'],
        ]);
    }

    /** بررسی same-origin برای نوشتن‌ها (سازگار با کش صفحه، بدون نانس) */
    public static function can_mutate(\WP_REST_Request $request): bool {
        $fetch_site = strtolower((string) $request->get_header('sec-fetch-site'));
        if (in_array($fetch_site, ['same-origin', 'same-site'], true)) {
            return true;
        }
        if ('cross-site' === $fetch_site) {
            return false;
        }

        $referer = (string) $request->get_header('referer');
        if ('' === $referer) {
            return true; // بعضی مرورگرها/افزونه‌ها referer نمی‌فرستند
        }
        return wp_parse_url($referer, PHP_URL_HOST) === wp_parse_url(home_url(), PHP_URL_HOST);
    }

    private static function no_store($response) {
        $response->header('Cache-Control', 'no-store, private, max-age=0');
        return $response;
    }

    /** حداکثر تعداد مجاز برای یک محصول/واریانت (۰ = بدون سقف) */
    private static function max_qty($product): int {
        if (!$product) {
            return 0;
        }
        if ($product->managing_stock() && !$product->backorders_allowed()) {
            return max(0, (int) $product->get_stock_quantity());
        }
        return 0;
    }

    /* ------------------------------------------------------------------ */

    public static function summary(\WP_REST_Request $request) {
        if (!Session::ensure_cart()) {
            return new \WP_Error('cart_unavailable', 'Cart unavailable.', ['status' => 500]);
        }
        return self::no_store(rest_ensure_response([
            'count'         => WC()->cart->get_cart_contents_count(),
            'subtotal_html' => WC()->cart->get_cart_subtotal(),
            'is_empty'      => WC()->cart->is_empty(),
        ]));
    }

    /** اقلام سبد برای تشخیص «در سبد بودن» هر محصول/واریانت */
    public static function items(\WP_REST_Request $request) {
        if (!Session::ensure_cart()) {
            return new \WP_Error('cart_unavailable', 'Cart unavailable.', ['status' => 500]);
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

        return self::no_store(rest_ensure_response([
            'count' => WC()->cart->get_cart_contents_count(),
            'items' => $items,
        ]));
    }

    /** افزودن محصول ساده یا واریانت */
    public static function add(\WP_REST_Request $request) {
        if (!Session::ensure_cart()) {
            return new \WP_Error('cart_unavailable', 'Cart unavailable.', ['status' => 500]);
        }

        $product_id   = absint($request->get_param('product_id'));
        $variation_id = absint($request->get_param('variation_id'));
        $quantity     = max(1, (int) $request->get_param('quantity'));

        $variation = [];
        foreach ((array) $request->get_param('variation') as $k => $v) {
            $variation[sanitize_key($k)] = sanitize_text_field(wp_unslash((string) $v));
        }

        if (!$product_id) {
            return new \WP_Error('bad_product', __('محصول نامعتبر است.', 'almasara-fast-cart'), ['status' => 400]);
        }

        $added = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation);

        if (false === $added) {
            return new \WP_Error(
                'add_failed',
                self::first_error_notice() ?: __('امکان افزودن این محصول نیست.', 'almasara-fast-cart'),
                ['status' => 400]
            );
        }

        Session::persist();

        $cart_item = WC()->cart->get_cart()[$added] ?? null;

        return self::no_store(rest_ensure_response([
            'success'  => true,
            'key'      => $added,
            'quantity' => (int) ($cart_item['quantity'] ?? $quantity),
            'max'      => self::max_qty($cart_item['data'] ?? null),
            'count'    => WC()->cart->get_cart_contents_count(),
            'subtotal' => WC()->cart->get_cart_subtotal(),
        ]));
    }

    /** تغییر تعداد یا حذف (quantity <= 0 → حذف) */
    public static function update(\WP_REST_Request $request) {
        if (!Session::ensure_cart()) {
            return new \WP_Error('cart_unavailable', 'Cart unavailable.', ['status' => 500]);
        }

        $key      = sanitize_text_field((string) $request->get_param('key'));
        $quantity = (int) $request->get_param('quantity');

        $cart = WC()->cart->get_cart();
        if (!isset($cart[$key])) {
            return new \WP_Error('not_found', __('این آیتم در سبد نیست.', 'almasara-fast-cart'), ['status' => 404]);
        }

        if ($quantity <= 0) {
            WC()->cart->remove_cart_item($key);
            Session::persist();
            return self::no_store(rest_ensure_response([
                'removed'  => true,
                'count'    => WC()->cart->get_cart_contents_count(),
                'subtotal' => WC()->cart->get_cart_subtotal(),
            ]));
        }

        $max    = self::max_qty($cart[$key]['data'] ?? null);
        $at_max = false;
        if ($max > 0 && $quantity >= $max) {
            $quantity = $max;
            $at_max   = true;
        }

        WC()->cart->set_quantity($key, $quantity, true);
        Session::persist();

        return self::no_store(rest_ensure_response([
            'removed'  => false,
            'quantity' => $quantity,
            'max'      => $max,
            'at_max'   => $at_max,
            'count'    => WC()->cart->get_cart_contents_count(),
            'subtotal' => WC()->cart->get_cart_subtotal(),
        ]));
    }

    /** اولین پیام خطای ووکامرس (ناموجود، انتخاب ناقص واریانت و ...) */
    private static function first_error_notice(): string {
        if (!function_exists('wc_get_notices')) {
            return '';
        }
        $notices = wc_get_notices('error');
        wc_clear_notices();
        return !empty($notices[0]['notice']) ? wp_strip_all_tags($notices[0]['notice']) : '';
    }
}
