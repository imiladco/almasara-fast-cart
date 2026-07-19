<?php
namespace Almasara_Fast_Cart;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * endpoint سبک خلاصه سبد.
 *
 * فقط وضعیت سبدِ سشنِ کاربرِ جاری را برمی‌گرداند (بدون هیچ پارامتری) و
 * هرگز کش نمی‌شود؛ چون داده‌اش per-user است. شمارنده اصلی از کوکی بومی
 * ووکامرس هیدریت می‌شود؛ این endpoint برای جمع کل و مینی‌کارت است.
 */
final class Rest {

    public static function init(): void {
        add_action('rest_api_init', [self::class, 'register']);
    }

    public static function register(): void {
        register_rest_route('almasara-cart/v1', '/summary', [
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => [self::class, 'summary'],
        ]);
    }

    public static function summary(\WP_REST_Request $request) {
        // در بستر REST، سبد به‌طور خودکار لود نمی‌شود؛ در صورت نیاز لودش کن.
        if (function_exists('wc_load_cart') && (null === WC()->cart)) {
            wc_load_cart();
        }

        if (null === WC()->cart) {
            return new \WP_Error('cart_unavailable', 'Cart unavailable.', ['status' => 500]);
        }

        $data = [
            'count'         => WC()->cart->get_cart_contents_count(),
            'subtotal_html' => WC()->cart->get_cart_subtotal(),
            'is_empty'      => WC()->cart->is_empty(),
        ];

        $response = rest_ensure_response($data);
        // هرگز کش نشود (نه مرورگر، نه CDN، نه افزونه کش)
        $response->header('Cache-Control', 'no-store, private, max-age=0');

        return $response;
    }
}
