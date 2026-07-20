<?php
namespace Almasara_Fast_Cart;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * تثبیت سشن/کوکی‌های سبد بعد از هر تغییر.
 *
 * پاسخ‌های ما با wp_send_json خارج می‌شوند؛ یعنی هوک shutdown (جایی که
 * ووکامرس معمولاً کوکی‌های سبد را ست می‌کند) بعد از ارسال هدرها اجرا
 * می‌شود و دیگر نمی‌تواند کوکی بنویسد. پس دو کار باید صریحاً داخل
 * callback و قبل از ارسال پاسخ انجام شود:
 *
 * 1) کوکی سشن مهمان (wp_woocommerce_session): برای بازدیدکننده‌ی بدون
 *    سشن، اگر نسازیم افزودن «موفق» برمی‌گردد ولی سبدش به هیچ سشنی وصل
 *    نیست و گم می‌شود.
 *
 * 2) کوکی‌های سبد (woocommerce_items_in_cart و ...): بجِ کلاینت از همین
 *    کوکی هیدریت می‌شود و باید همیشه تازه باشد.
 */
final class Session {

    /** لود سبد اگر هنوز لود نشده (گارد ایمنی برای کانتکست‌های غیرفرانت) */
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
