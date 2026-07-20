<?php
namespace Almasara_Fast_Cart;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * دارایی‌های فرانت: لایه خوش‌بینانه (همیشه) + کانفیگ کلاینت.
 *
 * عمداً نانس REST به کلاینت نمی‌دهیم: نانس داخل صفحه کش‌شده منجمد و بعد از
 * انقضا باعث 403 می‌شود. هویت با کوکی سشن ووکامرس منتقل می‌شود (مثل wc-ajax).
 */
final class Assets {

    public static function init(): void {
        add_action('wp_enqueue_scripts', [self::class, 'enqueue']);
    }

    public static function enqueue(): void {
        if (is_admin()) {
            return;
        }

        $settings = Settings::get();

        wp_enqueue_style('amfc', AMFC_URL . 'assets/css/fast-cart.css', [], AMFC_VERSION);
        wp_enqueue_script('amfc', AMFC_URL . 'assets/js/fast-cart.js', [], AMFC_VERSION, true);

        wp_localize_script('amfc', 'AMFC', [
            'restBase'      => esc_url_raw(rest_url('almasara-cart/v1')),
            'countSelector' => $settings['count_selector'],
            'cartUrl'       => esc_url(wc_get_cart_url()),
            'toast'         => [
                'enabled' => 'yes' === $settings['toast_enabled'],
                'text'    => $settings['toast_text'],
            ],
            'prefetch'      => 'yes' === $settings['prefetch_enabled'],
            'i18n'          => [
                'added'       => __('به سبد خرید اضافه شد', 'almasara-fast-cart'),
                'addFailed'   => __('خطا در افزودن به سبد', 'almasara-fast-cart'),
                'netError'    => __('ارتباط با سرور برقرار نشد', 'almasara-fast-cart'),
            ],
        ]);
    }
}
