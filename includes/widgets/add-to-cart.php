<?php
namespace Almasara_Fast_Cart\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ویجت «افزودن به سبد» — بخشی از افزونه سبد سریع الماسارا.
 *
 * مارک‌آپ استاندارد ووکامرس تولید می‌کند، پس:
 * - افزودن واقعی را اسکریپت بومی wc-add-to-cart انجام می‌دهد (همه هوک‌ها فایر)
 * - لایه خوش‌بینانه همین افزونه (fast-cart.js) بج/toast آنی می‌سازد
 * همه در یک افزونه، بدون سیم‌کشی بین افزونه‌ها.
 */
class Add_To_Cart extends Widget_Base {

    public function get_name(): string {
        return 'amfc-add-to-cart';
    }

    public function get_title(): string {
        return __('افزودن به سبد الماسارا', 'almasara-fast-cart');
    }

    public function get_icon(): string {
        return 'eicon-cart-medium';
    }

    public function get_categories(): array {
        return ['almasara-cart', 'woocommerce-elements'];
    }

    public function get_keywords(): array {
        return ['سبد', 'خرید', 'cart', 'add to cart', 'دکمه', 'الماسارا'];
    }

    public function get_style_depends(): array {
        return ['amfc-atc'];
    }

    public function get_script_depends(): array {
        return ['wc-add-to-cart', 'amfc-atc'];
    }

    /* ---------------------------------------------------------------------
     * کنترل‌ها
     * ------------------------------------------------------------------- */

    protected function register_controls(): void {
        $this->register_content_controls();
        $this->register_quantity_content_controls();

        $this->register_layout_style_controls();
        $this->register_button_style_controls();
        $this->register_quantity_style_controls();
    }

    private function register_content_controls(): void {
        $this->start_controls_section('section_content', [
            'label' => __('دکمه', 'almasara-fast-cart'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('product_id', [
            'label'       => __('شناسه محصول', 'almasara-fast-cart'),
            'type'        => Controls_Manager::NUMBER,
            'description' => __('خالی = محصول جاری. برای دکمه‌ی یک محصول خاص، شناسه‌اش را وارد کنید.', 'almasara-fast-cart'),
            'dynamic'     => ['active' => true],
        ]);

        $this->add_control('button_text', [
            'label'       => __('متن دکمه', 'almasara-fast-cart'),
            'type'        => Controls_Manager::TEXT,
            'default'     => __('افزودن به سبد خرید', 'almasara-fast-cart'),
            'placeholder' => __('افزودن به سبد خرید', 'almasara-fast-cart'),
            'dynamic'     => ['active' => true],
            'label_block' => true,
        ]);

        $this->add_control('added_text', [
            'label'       => __('متن پس از افزودن', 'almasara-fast-cart'),
            'type'        => Controls_Manager::TEXT,
            'default'     => __('به سبد اضافه شد', 'almasara-fast-cart'),
        ]);

        $this->add_control('show_icon', [
            'label'   => __('نمایش آیکون', 'almasara-fast-cart'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('icon_image', [
            'label'       => __('آیکون سفارشی', 'almasara-fast-cart'),
            'type'        => Controls_Manager::MEDIA,
            'media_types' => ['image', 'svg'],
            'description' => __('خالی = آیکون سبد پیش‌فرض. SVG به‌صورت inline و رنگ‌پذیر رندر می‌شود.', 'almasara-fast-cart'),
            'condition'   => ['show_icon' => 'yes'],
        ]);

        $this->add_control('icon_position', [
            'label'     => __('جای آیکون', 'almasara-fast-cart'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'start',
            'options'   => [
                'start' => __('ابتدای متن', 'almasara-fast-cart'),
                'end'   => __('انتهای متن', 'almasara-fast-cart'),
            ],
            'condition' => ['show_icon' => 'yes'],
        ]);

        $this->add_control('show_price', [
            'label'   => __('نمایش قیمت روی دکمه', 'almasara-fast-cart'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => '',
        ]);

        $this->add_control('hide_wc_view_cart', [
            'label'       => __('مخفی کردن لینک «مشاهده سبد» ووکامرس', 'almasara-fast-cart'),
            'type'        => Controls_Manager::SWITCHER,
            'default'     => 'yes',
        ]);

        $this->end_controls_section();
    }

    private function register_quantity_content_controls(): void {
        $this->start_controls_section('section_quantity', [
            'label' => __('تعداد', 'almasara-fast-cart'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_quantity', [
            'label'   => __('انتخابگر تعداد', 'almasara-fast-cart'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('quantity_style', [
            'label'     => __('نوع', 'almasara-fast-cart'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'stepper',
            'options'   => [
                'stepper' => __('دکمه‌ای (− عدد +)', 'almasara-fast-cart'),
                'input'   => __('فیلد عددی ساده', 'almasara-fast-cart'),
            ],
            'condition' => ['show_quantity' => 'yes'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: چیدمان ---------------- */

    private function register_layout_style_controls(): void {
        $this->start_controls_section('section_style_layout', [
            'label' => __('چیدمان', 'almasara-fast-cart'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('direction', [
            'label'     => __('جهت تعداد و دکمه', 'almasara-fast-cart'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'row',
            'options'   => [
                'row'    => __('کنار هم', 'almasara-fast-cart'),
                'column' => __('روی هم', 'almasara-fast-cart'),
            ],
            'selectors' => ['{{WRAPPER}} .amfc-atc' => 'flex-direction: {{VALUE}};'],
        ]);

        $this->add_responsive_control('gap', [
            'label'      => __('فاصله', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 60]],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amfc-atc' => 'gap: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('button_grow', [
            'label'        => __('دکمه فضای باقی‌مانده را پر کند', 'almasara-fast-cart'),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => '1',
            'selectors'    => ['{{WRAPPER}} .amfc-atc__btn' => 'flex-grow: {{VALUE}};'],
        ]);

        $this->add_responsive_control('align', [
            'label'     => __('تراز کل ویجت', 'almasara-fast-cart'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => [
                'flex-start' => ['title' => __('راست', 'almasara-fast-cart'), 'icon' => 'eicon-align-start-h'],
                'center'     => ['title' => __('وسط', 'almasara-fast-cart'), 'icon' => 'eicon-align-center-h'],
                'flex-end'   => ['title' => __('چپ', 'almasara-fast-cart'), 'icon' => 'eicon-align-end-h'],
                'stretch'    => ['title' => __('کشیده', 'almasara-fast-cart'), 'icon' => 'eicon-align-stretch-h'],
            ],
            'selectors' => ['{{WRAPPER}} .amfc-atc' => 'justify-content: {{VALUE}};'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: دکمه ---------------- */

    private function register_button_style_controls(): void {
        $this->start_controls_section('section_style_button', [
            'label' => __('دکمه', 'almasara-fast-cart'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'btn_typography',
            'selector' => '{{WRAPPER}} .amfc-atc__btn',
        ]);

        $this->add_responsive_control('btn_padding', [
            'label'      => __('پدینگ', 'almasara-fast-cart'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .amfc-atc__btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('btn_radius', [
            'label'      => __('رادیوس', 'almasara-fast-cart'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [
                '{{WRAPPER}} .amfc-atc__btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('icon_size', [
            'label'      => __('اندازه آیکون', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 8, 'max' => 60]],
            'selectors'  => ['{{WRAPPER}} .amfc-atc__icon' => '--amfc-atc-icon: {{SIZE}}{{UNIT}};'],
            'condition'  => ['show_icon' => 'yes'],
        ]);

        $this->add_responsive_control('icon_gap', [
            'label'      => __('فاصله آیکون از متن', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 30]],
            'selectors'  => ['{{WRAPPER}} .amfc-atc__btn' => '--amfc-atc-icongap: {{SIZE}}{{UNIT}};'],
            'condition'  => ['show_icon' => 'yes'],
        ]);

        $this->start_controls_tabs('btn_tabs');

        $this->start_controls_tab('btn_normal', ['label' => __('عادی', 'almasara-fast-cart')]);
        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'     => 'btn_bg',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .amfc-atc__btn',
        ]);
        $this->add_control('btn_color', [
            'label'     => __('رنگ متن و آیکون', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__btn' => 'color: {{VALUE}};'],
        ]);
        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'btn_border',
            'selector' => '{{WRAPPER}} .amfc-atc__btn',
        ]);
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'btn_shadow',
            'selector' => '{{WRAPPER}} .amfc-atc__btn',
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('btn_hover', ['label' => __('هاور', 'almasara-fast-cart')]);
        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'     => 'btn_bg_hover',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .amfc-atc__btn:hover',
        ]);
        $this->add_control('btn_color_hover', [
            'label'     => __('رنگ متن و آیکون', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__btn:hover' => 'color: {{VALUE}};'],
        ]);
        $this->add_control('btn_border_color_hover', [
            'label'     => __('رنگ حاشیه', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__btn:hover' => 'border-color: {{VALUE}};'],
        ]);
        $this->add_control('btn_transform_hover', [
            'label'      => __('جابه‌جایی عمودی', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => -10, 'max' => 10]],
            'selectors'  => ['{{WRAPPER}} .amfc-atc__btn:hover' => 'transform: translateY({{SIZE}}px);'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('btn_added', ['label' => __('افزوده‌شد', 'almasara-fast-cart')]);
        $this->add_control('btn_bg_added', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#1eaa59',
            'selectors' => ['{{WRAPPER}} .amfc-atc__btn.amfc-added' => 'background-color: {{VALUE}}; border-color: {{VALUE}};'],
        ]);
        $this->add_control('btn_color_added', [
            'label'     => __('رنگ متن', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => ['{{WRAPPER}} .amfc-atc__btn.amfc-added' => 'color: {{VALUE}};'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control('btn_transition', [
            'label'     => __('مدت انیمیشن (میلی‌ثانیه)', 'almasara-fast-cart'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => ['px' => ['min' => 0, 'max' => 1000]],
            'default'   => ['size' => 250],
            'separator' => 'before',
            'selectors' => ['{{WRAPPER}} .amfc-atc__btn' => 'transition: all {{SIZE}}ms ease;'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: تعداد ---------------- */

    private function register_quantity_style_controls(): void {
        $this->start_controls_section('section_style_quantity', [
            'label'     => __('تعداد', 'almasara-fast-cart'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_quantity' => 'yes'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qty_typography',
            'selector' => '{{WRAPPER}} .amfc-atc__qty, {{WRAPPER}} .amfc-atc__qty-input',
        ]);

        $this->add_responsive_control('qty_height', [
            'label'      => __('ارتفاع', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 28, 'max' => 80]],
            'selectors'  => ['{{WRAPPER}} .amfc-atc__qty' => 'height: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('qty_btn_width', [
            'label'      => __('عرض دکمه‌های +/−', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 24, 'max' => 70]],
            'default'    => ['size' => 40, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amfc-atc__step' => 'width: {{SIZE}}{{UNIT}};'],
            'condition'  => ['quantity_style' => 'stepper'],
        ]);

        $this->add_control('qty_color', [
            'label'     => __('رنگ متن', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amfc-atc__qty' => 'color: {{VALUE}};',
                '{{WRAPPER}} .amfc-atc__qty-input' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('qty_bg', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__qty' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_control('qty_step_color', [
            'label'     => __('رنگ دکمه‌های +/−', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__step' => 'color: {{VALUE}};'],
            'condition' => ['quantity_style' => 'stepper'],
        ]);

        $this->add_control('qty_step_color_hover', [
            'label'     => __('رنگ +/− در هاور', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__step:hover' => 'color: {{VALUE}};'],
            'condition' => ['quantity_style' => 'stepper'],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'      => 'qty_border',
            'selector'  => '{{WRAPPER}} .amfc-atc__qty',
            'separator' => 'before',
        ]);

        $this->add_responsive_control('qty_radius', [
            'label'      => __('رادیوس', 'almasara-fast-cart'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [
                '{{WRAPPER}} .amfc-atc__qty' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->end_controls_section();
    }

    /* ---------------------------------------------------------------------
     * رندر
     * ------------------------------------------------------------------- */

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $product  = $this->resolve_product($settings);

        $is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();

        if (!$product) {
            if ($is_editor) {
                echo '<div class="amfc-notice">' . esc_html__('محصولی پیدا نشد. این ویجت را در صفحه/قالب محصول استفاده کنید یا شناسه محصول را وارد کنید.', 'almasara-fast-cart') . '</div>';
            }
            return;
        }

        $wrapper_classes = ['amfc-atc'];
        if ('yes' === $settings['hide_wc_view_cart']) {
            $wrapper_classes[] = 'amfc-atc--hide-viewcart';
        }

        $simple_addable = $product->is_purchasable() && $product->is_in_stock()
            && !$product->is_type('variable') && !$product->is_type('grouped') && !$product->is_type('external');

        echo '<div class="' . esc_attr(implode(' ', $wrapper_classes)) . '">';

        if ($simple_addable) {
            $this->render_quantity($settings, $product);
            $this->render_button($settings, $product);
        } else {
            printf(
                '<a class="amfc-atc__btn amfc-atc__btn--link" href="%s">%s%s</a>',
                esc_url($product->add_to_cart_url()),
                $this->get_icon_html($settings),
                '<span class="amfc-atc__text">' . esc_html($product->add_to_cart_text()) . '</span>'
            );
        }

        echo '</div>';
    }

    private function render_quantity(array $settings, $product): void {
        if ('yes' !== $settings['show_quantity']) {
            return;
        }

        $min = 1;
        $max = $product->managing_stock() && !$product->backorders_allowed()
            ? (int) $product->get_stock_quantity()
            : 0;

        $stepper = 'stepper' === $settings['quantity_style'];

        echo '<div class="amfc-atc__qty' . ($stepper ? ' amfc-atc__qty--stepper' : '') . '">';

        if ($stepper) {
            echo '<button type="button" class="amfc-atc__step amfc-atc__step--minus" tabindex="-1" aria-label="' . esc_attr__('کاهش', 'almasara-fast-cart') . '">−</button>';
        }

        printf(
            '<input type="number" class="amfc-atc__qty-input" value="%d" min="%d"%s step="1" inputmode="numeric" aria-label="%s" />',
            $min,
            $min,
            $max > 0 ? ' max="' . esc_attr($max) . '"' : '',
            esc_attr__('تعداد', 'almasara-fast-cart')
        );

        if ($stepper) {
            echo '<button type="button" class="amfc-atc__step amfc-atc__step--plus" tabindex="-1" aria-label="' . esc_attr__('افزایش', 'almasara-fast-cart') . '">+</button>';
        }

        echo '</div>';
    }

    private function render_button(array $settings, $product): void {
        $text = '' !== trim((string) $settings['button_text'])
            ? $settings['button_text']
            : $product->single_add_to_cart_text();

        $this->add_render_attribute('btn', [
            'class'            => ['amfc-atc__btn', 'ajax_add_to_cart', 'add_to_cart_button', 'product_type_simple'],
            'type'             => 'button',
            'data-product_id'  => (string) $product->get_id(),
            'data-product_sku' => (string) $product->get_sku(),
            'data-quantity'    => '1',
            'data-added-text'  => (string) $settings['added_text'],
            'aria-label'       => $product->add_to_cart_description(),
            'rel'              => 'nofollow',
        ]);

        $icon  = $this->get_icon_html($settings);
        $start = 'start' === $settings['icon_position'];

        $price = '';
        if ('yes' === $settings['show_price']) {
            $price = '<span class="amfc-atc__price">' . wp_kses_post($product->get_price_html()) . '</span>';
        }

        echo '<button ' . $this->get_render_attribute_string('btn') . '>';
        if ($start) {
            echo $icon; // phpcs:ignore
        }
        echo '<span class="amfc-atc__text">' . esc_html($text) . '</span>';
        if (!$start) {
            echo $icon; // phpcs:ignore
        }
        echo $price; // phpcs:ignore
        echo '</button>';
    }

    private function get_icon_html(array $settings): string {
        if ('yes' !== $settings['show_icon']) {
            return '';
        }

        $inner = '';
        if (!empty($settings['icon_image']['url'])) {
            $url    = $settings['icon_image']['url'];
            $is_svg = 'svg' === strtolower(pathinfo(wp_parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
            if ($is_svg && !empty($settings['icon_image']['id'])) {
                $inner = $this->get_inline_svg((int) $settings['icon_image']['id']);
            }
            if ('' === $inner) {
                $inner = '<img src="' . esc_url($url) . '" alt="">';
            }
        } else {
            $inner = '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>';
        }

        return '<span class="amfc-atc__icon">' . $inner . '</span>';
    }

    /** محصول: شناسه دستی، صفحه محصول جاری، وگرنه آخرین محصول برای پیش‌نمایش ادیتور */
    private function resolve_product(array $settings) {
        if (!function_exists('wc_get_product')) {
            return false;
        }
        if (!empty($settings['product_id'])) {
            return wc_get_product((int) $settings['product_id']);
        }

        $post_id = get_the_ID();
        if ($post_id && 'product' === get_post_type($post_id)) {
            return wc_get_product($post_id);
        }

        if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            $latest = wc_get_products(['limit' => 1, 'status' => 'publish', 'orderby' => 'date', 'order' => 'DESC']);
            return $latest ? $latest[0] : false;
        }

        return false;
    }

    /** خواندن و پاک‌سازی امنیتی فایل SVG برای درج inline */
    private function get_inline_svg(int $attachment_id): string {
        $path = get_attached_file($attachment_id);
        if (!$path || !file_exists($path)) {
            return '';
        }
        $svg = file_get_contents($path);
        if (false === $svg || false === stripos($svg, '<svg')) {
            return '';
        }
        $svg = preg_replace('/<\?xml.*?\?>/is', '', $svg);
        $svg = preg_replace('/<!DOCTYPE.*?>/is', '', $svg);
        $svg = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $svg);
        $svg = preg_replace('/<foreignObject\b[^>]*>.*?<\/foreignObject>/is', '', $svg);
        $svg = preg_replace('/\son\w+\s*=\s*(["\']).*?\1/is', '', $svg);
        $svg = preg_replace('/\s(?:xlink:)?href\s*=\s*(["\'])\s*(?:javascript|data):.*?\1/is', '', $svg);
        return trim($svg);
    }
}
