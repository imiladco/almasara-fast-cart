<?php
/**
 * Plugin Name:       Almasara Fast Cart
 * Description:       سبد خرید سریع ووکامرس: افزودن خوش‌بینانه، ویجت المنتوری با واریانت، بج آنی — لایه بهبود روی مکانیزم بومی، نه جایگزین آن.
 * Version:           1.6.0
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

define('AMFC_VERSION', '1.6.0');
define('AMFC_FILE', __FILE__);
define('AMFC_PATH', plugin_dir_path(__FILE__));
define('AMFC_URL', plugin_dir_url(__FILE__));

// اعلام سازگاری با HPOS (انبار سفارش پرسرعت ووکامرس)
add_action('before_woocommerce_init', static function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', AMFC_FILE, true);
    }
});

// بوت بعد از لود افزونه‌ها تا از فعال بودن ووکامرس مطمئن شویم
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

    require_once AMFC_PATH . 'includes/class-plugin.php';
    \Almasara_Fast_Cart\Plugin::boot();
});
