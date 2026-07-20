<?php
namespace Almasara_Fast_Cart;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * راه‌اندازی سشن/سبد ووکامرس در بستر REST.
 *
 * دو نکته حیاتی که در بستر REST خودکار اتفاق نمی‌افتند (برخلاف wc-ajax):
 *
 * 1) کوکی سشن مهمان: ووکامرس برای بازدیدکننده‌ی بدون سشن، کوکی
 *    wp_woocommerce_session را فقط در جریان فرانت می‌سازد. اگر اینجا صریحاً
 *    نسازیم، افزودنِ مهمان «موفق» برمی‌گردد ولی سبدش به هیچ سشنی وصل نیست
 *    و عملاً گم می‌شود.
 *
 * 2) کوکی‌های سبد (woocommerce_items_in_cart و ...): در فرانت روی shutdown
 *    ست می‌شوند؛ در REST آن موقع هدرها رفته‌اند. باید داخل callback و قبل از
 *    ارسال پاسخ صدا شوند — این همان چیزی است که بجِ کلاینت (که از همین کوکی
 *    هیدریت می‌شود) را همیشه تازه نگه می‌دارد.
 */
final class Session {

    /** لود سبد اگر هنوز لود نشده (REST به‌طور پیش‌فرض سبد ندارد) */
    public static function ensure_cart(): bool {
        if (!function_exists('wc_get_product')) {
            return false;
        }
        if (null === WC()->cart && function_exists('wc_load_cart')) {
            wc_load_cart();
        }
        return null !== WC()->cart;
    }

    /** بعد از هر mutation صدا شود — قبل از برگشتن پاسخ */
    public static function persist(): void {
        if (WC()->session && !WC()->session->has_session()) {
            WC()->session->set_customer_session_cookie(true);
        }
        if (WC()->cart) {
            WC()->cart->maybe_set_cart_cookies();
        }
    }
}
