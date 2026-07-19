<?php
/**
 * Plugin Name:       Almasara Fast Cart
 * Description:       بهبود سرعتِ افزودن به سبد خرید ووکامرس با UI خوش‌بینانه (لایه بهبود روی مکانیزم بومی، نه جایگزین آن).
 * Version:           0.2.0
 * Author:            Almasara
 * Text Domain:       almasara-fast-cart
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * WC requires at least: 7.0
 * WC tested up to:   9.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AMFC_VERSION', '0.2.0');
define('AMFC_FILE', __FILE__);
define('AMFC_PATH', plugin_dir_path(__FILE__));
define('AMFC_URL', plugin_dir_url(__FILE__));

/**
 * اعلام سازگاری با HPOS (انبار سفارش پرسرعت ووکامرس) تا هشدار ناسازگاری نگیریم.
 */
add_action('before_woocommerce_init', static function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', AMFC_FILE, true);
    }
});

/**
 * بوت‌استرپ بعد از لود شدن افزونه‌ها تا از فعال بودن ووکامرس مطمئن شویم.
 */
add_action('plugins_loaded', static function () {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', static function () {
            printf(
                '<div class="notice notice-warning"><p>%s</p></div>',
                esc_html__('افزونه «سبد سریع الماسارا» برای کار کردن به ووکامرس نیاز دارد.', 'almasara-fast-cart')
            );
        });
        return;
    }

    // ترجمه‌ها روی init (نه زودتر) تا نوتیس _load_textdomain_just_in_time ندهد
    add_action('init', static function () {
        load_plugin_textdomain('almasara-fast-cart', false, dirname(plugin_basename(AMFC_FILE)) . '/languages');
    });

    require_once AMFC_PATH . 'includes/settings.php';
    require_once AMFC_PATH . 'includes/rest.php';
    require_once AMFC_PATH . 'includes/elementor.php';

    \Almasara_Fast_Cart\Settings::init();
    \Almasara_Fast_Cart\Rest::init();
    \Almasara_Fast_Cart\Elementor::init(); // ویجت المنتور فقط اگر المنتور فعال باشد ثبت می‌شود

    add_action('wp_enqueue_scripts', 'amfc_enqueue_assets');
});

/**
 * بارگذاری دارایی‌های فرانت. بدون وابستگی به jQuery (vanilla).
 */
function amfc_enqueue_assets(): void {
    if (is_admin()) {
        return;
    }

    $settings = \Almasara_Fast_Cart\Settings::get();

    wp_enqueue_style(
        'amfc',
        AMFC_URL . 'assets/css/fast-cart.css',
        [],
        AMFC_VERSION
    );

    wp_enqueue_script(
        'amfc',
        AMFC_URL . 'assets/js/fast-cart.js',
        [],
        AMFC_VERSION,
        true
    );

    wp_localize_script('amfc', 'AMFC', [
        'summaryUrl'    => esc_url_raw(rest_url('almasara-cart/v1/summary')),
        'nonce'         => wp_create_nonce('wp_rest'),
        'countSelector' => $settings['count_selector'],
        'cartUrl'       => esc_url(wc_get_cart_url()),
        'toast'         => [
            'enabled' => 'yes' === $settings['toast_enabled'],
            'text'    => $settings['toast_text'],
        ],
        'prefetch'      => 'yes' === $settings['prefetch_enabled'],
        'i18n'          => [
            'added' => __('به سبد خرید اضافه شد', 'almasara-fast-cart'),
        ],
    ]);
}
