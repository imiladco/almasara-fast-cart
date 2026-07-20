<?php
namespace Almasara_Fast_Cart;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * هماهنگ‌کننده افزونه: بارگذاری ماژول‌ها و راه‌اندازی‌شان.
 * هر ماژول یک مسئولیت دارد و در فایل خودش زندگی می‌کند.
 */
final class Plugin {

    public static function boot(): void {
        require_once AMFC_PATH . 'includes/class-session.php';
        require_once AMFC_PATH . 'includes/class-settings.php';
        require_once AMFC_PATH . 'includes/class-ajax.php';
        require_once AMFC_PATH . 'includes/class-assets.php';
        require_once AMFC_PATH . 'includes/class-elementor.php';

        // ترجمه‌ها روی init (نه زودتر) تا نوتیس _load_textdomain_just_in_time ندهد
        add_action('init', static function () {
            load_plugin_textdomain('almasara-fast-cart', false, dirname(plugin_basename(AMFC_FILE)) . '/languages');
        });

        Settings::init();
        Ajax::init();
        Assets::init();
        Elementor_Integration::init();
    }
}
