<?php
namespace Almasara_Fast_Cart;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * یکپارچه‌سازی المنتور.
 *
 * ویجت‌ها فقط با المنتورِ فعال ثبت می‌شوند؛ لایه خوش‌بینانه (fast-cart.js)
 * مستقل از المنتور همیشه کار می‌کند.
 */
final class Elementor_Integration {

    public static function init(): void {
        add_action('elementor/elements/categories_registered', [self::class, 'category']);
        add_action('elementor/widgets/register', [self::class, 'register_widgets']);
        add_action('elementor/frontend/after_register_styles', [self::class, 'register_style']);
        add_action('elementor/frontend/after_register_scripts', [self::class, 'register_script']);
        add_action('elementor/editor/after_enqueue_styles', [self::class, 'enqueue_editor']);
    }

    public static function category($elements_manager): void {
        $elements_manager->add_category('almasara-cart', [
            'title' => __('سبد الماسارا', 'almasara-fast-cart'),
            'icon'  => 'eicon-cart-medium',
        ]);
    }

    public static function register_widgets($widgets_manager): void {
        require_once AMFC_PATH . 'includes/widgets/class-add-to-cart.php';
        $widgets_manager->register(new Widgets\Add_To_Cart());
    }

    public static function register_style(): void {
        wp_register_style('amfc-atc', AMFC_URL . 'assets/css/atc-widget.css', [], AMFC_VERSION);
    }

    public static function register_script(): void {
        wp_register_script('amfc-atc', AMFC_URL . 'assets/js/atc-widget.js', ['amfc'], AMFC_VERSION, true);
    }

    public static function enqueue_editor(): void {
        self::register_style();
        wp_enqueue_style('amfc-atc');
    }
}
