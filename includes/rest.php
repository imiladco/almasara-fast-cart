<?php
namespace Almasara_Fast_Cart;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * endpointهای سبک سبد (namespace اختصاصی almasara-cart/v1).
 *
 * همه عملیات از توابع بومی WooCommerce عبور می‌کنند (WC()->cart->add_to_cart،
 * set_quantity، remove_cart_item) پس همه‌ی هوک‌ها فایر می‌شوند و درستیِ
 * کوپن/مالیات/موجودی حفظ می‌ماند. خروجی‌ها per-session و بدون کش‌اند.
 *
 * امنیت افزودن/خواندن مثل خودِ ووکامرس بدون nonce است (لینک add-to-cart
 * ووکامرس هم nonce ندارد)؛ برای mutation یک بررسی same-origin سبک داریم تا
 * روی صفحات کش‌شده هم بدون مشکل nonce کار کند.
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

    /** بررسی سبک same-origin برای عملیات نوشتن (سازگار با کش) */
    public static function can_mutate(\WP_REST_Request $request): bool {
        $referer = wp_get_referer();
        if (!$referer) {
            $referer = (string) $request->get_header('referer');
        }
        if (!$referer) {
            return true; // برخی مرورگرها referer نمی‌فرستند؛ سخت‌گیری نمی‌کنیم
        }
        return wp_parse_url($referer, PHP_URL_HOST) === wp_parse_url(home_url(), PHP_URL_HOST);
    }

    private static function ensure_cart(): bool {
        if (!function_exists('wc_get_product')) {
            return false;
        }
        if (function_exists('wc_load_cart') && null === WC()->cart) {
            wc_load_cart();
        }
        return null !== WC()->cart;
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
        if (!self::ensure_cart()) {
            return new \WP_Error('cart_unavailable', 'Cart unavailable.', ['status' => 500]);
        }
        return self::no_store(rest_ensure_response([
            'count'         => WC()->cart->get_cart_contents_count(),
            'subtotal_html' => WC()->cart->get_cart_subtotal(),
            'is_empty'      => WC()->cart->is_empty(),
        ]));
    }

    /** اقلام سبد برای تشخیص «در سبد بودن» هر محصول/واریانت (ضد کش) */
    public static function items(\WP_REST_Request $request) {
        if (!self::ensure_cart()) {
            return new \WP_Error('cart_unavailable', 'Cart unavailable.', ['status' => 500]);
        }

        $items = [];
        foreach (WC()->cart->get_cart() as $key => $item) {
            $product = $item['data'] ?? null;
            $items[] = [
                'key'          => $key,
                'product_id'   => (int) ($item['product_id'] ?? 0),
                'variation_id' => (int) ($item['variation_id'] ?? 0),
                'quantity'     => (int) ($item['quantity'] ?? 0),
                'max'          => self::max_qty($product),
            ];
        }

        return self::no_store(rest_ensure_response([
            'count' => WC()->cart->get_cart_contents_count(),
            'items' => $items,
        ]));
    }

    /** افزودن ساده یا واریانت */
    public static function add(\WP_REST_Request $request) {
        if (!self::ensure_cart()) {
            return new \WP_Error('cart_unavailable', 'Cart unavailable.', ['status' => 500]);
        }

        $product_id   = absint($request->get_param('product_id'));
        $variation_id = absint($request->get_param('variation_id'));
        $quantity     = max(1, (int) $request->get_param('quantity'));
        $variation    = (array) $request->get_param('variation');

        $variation = array_map('sanitize_text_field', wp_unslash($variation));
        $variation_clean = [];
        foreach ($variation as $k => $v) {
            $variation_clean[sanitize_key($k)] = $v;
        }

        if (!$product_id) {
            return new \WP_Error('bad_product', __('محصول نامعتبر است.', 'almasara-fast-cart'), ['status' => 400]);
        }

        // انباشت پیام‌های خطای ووکامرس (ناموجود، انتخاب واریانت و ...)
        $added = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation_clean);

        if (false === $added) {
            $notice = self::first_error_notice();
            return new \WP_Error('add_failed', $notice ?: __('امکان افزودن این محصول نیست.', 'almasara-fast-cart'), ['status' => 400]);
        }

        $cart_item = WC()->cart->get_cart()[$added] ?? null;
        $product   = $cart_item['data'] ?? null;

        return self::no_store(rest_ensure_response([
            'success'  => true,
            'key'      => $added,
            'quantity' => (int) ($cart_item['quantity'] ?? $quantity),
            'max'      => self::max_qty($product),
            'count'    => WC()->cart->get_cart_contents_count(),
            'subtotal' => WC()->cart->get_cart_subtotal(),
        ]));
    }

    /** تغییر تعداد یا حذف (quantity <= 0 → حذف) */
    public static function update(\WP_REST_Request $request) {
        if (!self::ensure_cart()) {
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
            return self::no_store(rest_ensure_response([
                'removed'  => true,
                'count'    => WC()->cart->get_cart_contents_count(),
                'subtotal' => WC()->cart->get_cart_subtotal(),
            ]));
        }

        $product = $cart[$key]['data'] ?? null;
        $max     = self::max_qty($product);
        $at_max  = false;
        if ($max > 0 && $quantity >= $max) {
            $quantity = $max;
            $at_max   = true;
        }

        WC()->cart->set_quantity($key, $quantity, true);

        return self::no_store(rest_ensure_response([
            'removed'  => false,
            'quantity' => $quantity,
            'max'      => $max,
            'at_max'   => $at_max,
            'count'    => WC()->cart->get_cart_contents_count(),
            'subtotal' => WC()->cart->get_cart_subtotal(),
        ]));
    }

    /** اولین پیام خطای ووکامرس (اگر باشد) */
    private static function first_error_notice(): string {
        if (!function_exists('wc_get_notices')) {
            return '';
        }
        $notices = wc_get_notices('error');
        wc_clear_notices();
        if (!empty($notices[0]['notice'])) {
            return wp_strip_all_tags($notices[0]['notice']);
        }
        return '';
    }
}
