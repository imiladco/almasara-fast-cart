<?php
namespace Almasara_Fast_Cart;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * یکپارچه‌سازی المنتور برای افزونه سبد سریع.
 *
 * ویجت‌های سبد فقط زمانی ثبت می‌شوند که المنتور فعال باشد؛ لایه خوش‌بینانه
 * (fast-cart.js) مستقل از المنتور همیشه کار می‌کند. کل کارِ سبد در همین
 * افزونه می‌ماند.
 */
final class Elementor {

    public static function init(): void {
        add_action('elementor/elements/categories_registered', [self::class, 'category']);
        add_action('elementor/widgets/register', [self::class, 'register_widgets']);
        add_action('elementor/frontend/after_register_scripts', [self::class, 'register_assets']);
        add_action('elementor/frontend/after_register_styles', [self::class, 'register_assets']);
        add_action('elementor/editor/after_enqueue_styles', [self::class, 'enqueue_editor']);
    }

    public static function category($elements_manager): void {
        $elements_manager->add_category('almasara-cart', [
            'title' => __('سبد الماسارا', 'almasara-fast-cart'),
            'icon'  => 'eicon-cart-medium',
        ]);
    }

    public static function register_widgets($widgets_manager): void {
        require_once AMFC_PATH . 'includes/widgets/add-to-cart.php';
        $widgets_manager->register(new Widgets\Add_To_Cart());
    }

    public static function register_assets(): void {
        wp_register_style(
            'amfc-atc',
            AMFC_URL . 'assets/css/atc-widget.css',
            [],
            AMFC_VERSION
        );
        wp_register_script(
            'amfc-atc',
            AMFC_URL . 'assets/js/atc-widget.js',
            [],
            AMFC_VERSION,
            true
        );
    }

    public static function enqueue_editor(): void {
        self::register_assets();
        wp_enqueue_style('amfc-atc');
    }
}
