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
 * ویجت «افزودن به سبد» — افزونه سبد سریع الماسارا.
 *
 * محصولات ساده و متغیر (افزودن مستقیم واریانت)، قیمت با بج تخفیف، و
 * جایگزینی خودکار دکمه با کنترل «در سبد شما» وقتی محصول/واریانت در سبد است.
 *
 * نکات فنی مهم:
 * - کانتینر سلکت‌های واریانت باید کلاس «variations» داشته باشد؛ اسکریپت
 *   بومی wc-add-to-cart-variation سلکت‌ها را با «.variations select» پیدا
 *   می‌کند و بدون آن found_variation هرگز فایر نمی‌شود.
 * - JSON واریانت‌ها باید دستی و با wc_esc_json چاپ شود؛ عبورش از
 *   render attributes المنتور باعث escape دوباره و شکستن JSON می‌شود.
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
        return ['سبد', 'خرید', 'cart', 'add to cart', 'واریانت', 'الماسارا'];
    }

    public function get_style_depends(): array {
        return ['amfc-atc'];
    }

    public function get_script_depends(): array {
        return ['wc-add-to-cart-variation', 'amfc-atc'];
    }

    /* =====================================================================
     * کنترل‌ها
     * =================================================================== */

    protected function register_controls(): void {
        $this->register_content_controls();
        $this->register_quantity_content_controls();
        $this->register_incart_content_controls();

        $this->register_layout_style_controls();
        $this->register_button_style_controls();
        $this->register_quantity_style_controls();
        $this->register_variations_style_controls();
        $this->register_price_style_controls();
        $this->register_incart_style_controls();
    }

    private function register_content_controls(): void {
        $this->start_controls_section('section_content', [
            'label' => __('دکمه', 'almasara-fast-cart'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('product_id', [
            'label'       => __('شناسه محصول', 'almasara-fast-cart'),
            'type'        => Controls_Manager::NUMBER,
            'description' => __('خالی = محصول جاری.', 'almasara-fast-cart'),
            'dynamic'     => ['active' => true],
        ]);

        $this->add_control('button_text', [
            'label'       => __('متن دکمه', 'almasara-fast-cart'),
            'type'        => Controls_Manager::TEXT,
            'default'     => __('افزودن به سبد خرید', 'almasara-fast-cart'),
            'dynamic'     => ['active' => true],
            'label_block' => true,
        ]);

        $this->add_control('show_icon', [
            'label' => __('نمایش آیکون', 'almasara-fast-cart'),
            'type'  => Controls_Manager::SWITCHER,
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
            'label'   => __('نمایش قیمت', 'almasara-fast-cart'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('reset_text', [
            'label'   => __('متن «حذف انتخاب» (واریانت)', 'almasara-fast-cart'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('حذف انتخاب', 'almasara-fast-cart'),
        ]);

        $this->end_controls_section();
    }

    private function register_quantity_content_controls(): void {
        $this->start_controls_section('section_quantity', [
            'label' => __('تعداد (هنگام افزودن)', 'almasara-fast-cart'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_quantity', [
            'label' => __('انتخابگر تعداد', 'almasara-fast-cart'),
            'type'  => Controls_Manager::SWITCHER,
        ]);

        $this->add_control('quantity_style', [
            'label'     => __('نوع', 'almasara-fast-cart'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'stepper',
            'options'   => [
                'stepper' => __('دکمه‌ای (− عدد +)', 'almasara-fast-cart'),
                'input'   => __('فیلد عددی', 'almasara-fast-cart'),
            ],
            'condition' => ['show_quantity' => 'yes'],
        ]);

        $this->end_controls_section();
    }

    private function register_incart_content_controls(): void {
        $this->start_controls_section('section_incart', [
            'label' => __('حالت «در سبد»', 'almasara-fast-cart'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('incart_title', [
            'label'   => __('عنوان', 'almasara-fast-cart'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('در سبد شما', 'almasara-fast-cart'),
        ]);

        $this->add_control('view_cart_text', [
            'label'   => __('متن لینک مشاهده سبد', 'almasara-fast-cart'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('مشاهده سبد خرید', 'almasara-fast-cart'),
        ]);

        $this->add_control('max_text', [
            'label'   => __('متن حداکثر', 'almasara-fast-cart'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('حداکثر', 'almasara-fast-cart'),
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: چیدمان ---------------- */

    private function register_layout_style_controls(): void {
        $this->start_controls_section('section_style_layout', [
            'label' => __('چیدمان', 'almasara-fast-cart'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('block_gap', [
            'label'      => __('فاصله عمودی بخش‌ها', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 60]],
            'default'    => ['size' => 16, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .amfc-atc'                     => 'gap: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .amfc-atc__variations'         => 'gap: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .amfc-atc .single_variation_wrap' => 'gap: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('addrow_gap', [
            'label'      => __('فاصله تعداد و دکمه', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amfc-atc__addrow' => 'gap: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('button_grow', [
            'label'        => __('دکمه تمام عرض', 'almasara-fast-cart'),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => '1',
            'selectors'    => ['{{WRAPPER}} .amfc-atc__btn' => 'flex-grow: {{VALUE}};'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: دکمه ---------------- */

    private function register_button_style_controls(): void {
        $this->start_controls_section('section_style_button', [
            'label' => __('دکمه افزودن', 'almasara-fast-cart'),
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

        $this->end_controls_tabs();

        $this->add_control('btn_transition', [
            'label'     => __('مدت انیمیشن (ms)', 'almasara-fast-cart'),
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
            'label'     => __('انتخابگر تعداد', 'almasara-fast-cart'),
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

        $this->add_control('qty_color', [
            'label'     => __('رنگ متن', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .amfc-atc__qty'       => 'color: {{VALUE}};',
                '{{WRAPPER}} .amfc-atc__qty-input' => 'color: {{VALUE}};',
                '{{WRAPPER}} .amfc-atc__step'      => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('qty_bg', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__qty' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'qty_border',
            'selector' => '{{WRAPPER}} .amfc-atc__qty',
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

    /* ---------------- استایل: واریانت‌ها ---------------- */

    private function register_variations_style_controls(): void {
        $this->start_controls_section('section_style_variations', [
            'label' => __('واریانت‌ها', 'almasara-fast-cart'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'attr_label_typo',
            'label'    => __('تایپوگرافی عنوان', 'almasara-fast-cart'),
            'selector' => '{{WRAPPER}} .amfc-atc__attr-label',
        ]);

        $this->add_control('attr_label_color', [
            'label'     => __('رنگ عنوان', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__attr-label' => 'color: {{VALUE}};'],
        ]);

        $this->add_responsive_control('attr_gap', [
            'label'      => __('فاصله بین واریانت‌ها', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amfc-atc__attrs' => 'gap: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('heading_select', [
            'label'     => __('دراپ‌داون', 'almasara-fast-cart'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'select_typo',
            'selector' => '{{WRAPPER}} .amfc-atc__attr select',
        ]);

        $this->add_responsive_control('select_height', [
            'label'      => __('ارتفاع', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 32, 'max' => 80]],
            'selectors'  => ['{{WRAPPER}} .amfc-atc__attr select' => 'height: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('select_color', [
            'label'     => __('رنگ متن', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__attr select' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('select_bg', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__attr select' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_control('select_arrow_color', [
            'label'     => __('رنگ فلش', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__attr' => '--amfc-arrow: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'select_border',
            'selector' => '{{WRAPPER}} .amfc-atc__attr select',
        ]);

        $this->add_responsive_control('select_radius', [
            'label'      => __('رادیوس', 'almasara-fast-cart'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px'],
            'selectors'  => [
                '{{WRAPPER}} .amfc-atc__attr select' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_control('heading_reset', [
            'label'     => __('دکمه «حذف انتخاب»', 'almasara-fast-cart'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('reset_color', [
            'label'     => __('رنگ متن', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__reset' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('reset_bg', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__reset' => 'background-color: {{VALUE}};'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: قیمت ---------------- */

    private function register_price_style_controls(): void {
        $this->start_controls_section('section_style_price', [
            'label'     => __('قیمت', 'almasara-fast-cart'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_price' => 'yes'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'price_typo',
            'label'    => __('تایپوگرافی قیمت نهایی', 'almasara-fast-cart'),
            'selector' => '{{WRAPPER}} .amfc-atc__final',
        ]);

        $this->add_control('price_color', [
            'label'     => __('رنگ قیمت نهایی', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__final' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('currency_color', [
            'label'     => __('رنگ واحد پول', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__currency' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('regular_color', [
            'label'     => __('رنگ قیمت خط‌خورده', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__regular' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('heading_discount', [
            'label'     => __('بج تخفیف', 'almasara-fast-cart'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('discount_color', [
            'label'     => __('رنگ متن', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__discount' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('discount_bg', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__discount' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_responsive_control('discount_radius', [
            'label'      => __('رادیوس بج', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 30]],
            'selectors'  => ['{{WRAPPER}} .amfc-atc__discount' => 'border-radius: {{SIZE}}{{UNIT}};'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: حالت «در سبد» ---------------- */

    private function register_incart_style_controls(): void {
        $this->start_controls_section('section_style_incart', [
            'label' => __('حالت «در سبد»', 'almasara-fast-cart'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'incart_title_typo',
            'label'    => __('تایپوگرافی عنوان', 'almasara-fast-cart'),
            'selector' => '{{WRAPPER}} .amfc-atc__incart-title',
        ]);

        $this->add_control('incart_title_color', [
            'label'     => __('رنگ عنوان', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__incart-title' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('view_link_color', [
            'label'     => __('رنگ لینک مشاهده سبد', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__incart-link' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('heading_control', [
            'label'     => __('باکس کنترل تعداد', 'almasara-fast-cart'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('control_bg', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__control' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'control_border',
            'selector' => '{{WRAPPER}} .amfc-atc__control',
        ]);

        $this->add_responsive_control('control_radius', [
            'label'      => __('رادیوس', 'almasara-fast-cart'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px'],
            'selectors'  => [
                '{{WRAPPER}} .amfc-atc__control' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'control_shadow',
            'selector' => '{{WRAPPER}} .amfc-atc__control',
        ]);

        $this->add_responsive_control('control_height', [
            'label'      => __('ارتفاع', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 40, 'max' => 90]],
            'selectors'  => ['{{WRAPPER}} .amfc-atc__control' => 'height: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'      => 'num_typo',
            'label'     => __('تایپوگرافی عدد', 'almasara-fast-cart'),
            'selector'  => '{{WRAPPER}} .amfc-atc__ctl-value',
            'separator' => 'before',
        ]);

        $this->add_control('num_color', [
            'label'     => __('رنگ عدد', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__ctl-value' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('inc_color', [
            'label'     => __('رنگ دکمه +', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__ctl--inc' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('minus_color', [
            'label'     => __('رنگ دکمه −', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__ctl-minus' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('trash_color', [
            'label'     => __('رنگ آیکون حذف', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__ctl-trash' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('loader_color', [
            'label'     => __('رنگ لودر', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__ctl-loader' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('max_color', [
            'label'     => __('رنگ متن حداکثر', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__ctl-max' => 'color: {{VALUE}};'],
        ]);

        $this->end_controls_section();
    }

    /* =====================================================================
     * رندر
     * =================================================================== */

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $product  = $this->resolve_product($settings);

        if (!$product) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="amfc-notice">' . esc_html__('محصولی پیدا نشد. این ویجت را در صفحه/قالب محصول استفاده کنید یا شناسه محصول را وارد کنید.', 'almasara-fast-cart') . '</div>';
            }
            return;
        }

        $type = $product->get_type();

        printf(
            '<div class="amfc-atc amfc-atc--%1$s" data-product="%2$d" data-type="%1$s" data-max-text="%3$s">',
            esc_attr($type),
            (int) $product->get_id(),
            esc_attr($settings['max_text'])
        );

        if ($product->is_type('variable')) {
            $this->render_variable($settings, $product);
        } else {
            $this->render_simple($settings, $product);
        }

        $this->render_incart_control($settings);

        echo '</div>';
    }

    /** محصول ساده: قیمت + ردیف افزودن */
    private function render_simple(array $settings, $product): void {
        if ('yes' === $settings['show_price']) {
            echo $this->price_box_html($product); // phpcs:ignore
        }
        echo '<div class="amfc-atc__addrow">';
        $this->render_quantity($settings, $product);
        $this->render_button($settings, $product, false);
        echo '</div>';
    }

    /** محصول متغیر: فرم واریانت (سازگار با اسکریپت بومی WC) + قیمت داینامیک */
    private function render_variable(array $settings, $product): void {
        $attributes = $product->get_variation_attributes();
        $available  = $product->get_available_variations();

        // فرم دستی چاپ می‌شود: JSON واریانت‌ها نباید از esc_attr المنتور رد شود
        printf(
            '<form class="variations_form amfc-atc__variations" action="%s" method="post" enctype="multipart/form-data" data-product_id="%d" data-product_variations="%s">',
            esc_url($product->get_permalink()),
            (int) $product->get_id(),
            wc_esc_json(wp_json_encode($available)) // phpcs:ignore WordPress.Security.EscapeOutput
        );

        // کلاس «variations» الزامی است: اسکریپت WC سلکت‌ها را با .variations select پیدا می‌کند
        echo '<div class="variations amfc-atc__attrs">';
        foreach ($attributes as $attribute_name => $options) {
            echo '<div class="amfc-atc__attr">';
            echo '<label class="amfc-atc__attr-label">' . esc_html(wc_attribute_label($attribute_name)) . '</label>';

            $request_key = 'attribute_' . sanitize_title($attribute_name);
            $selected    = isset($_REQUEST[$request_key]) // phpcs:ignore WordPress.Security.NonceVerification
                ? wc_clean(wp_unslash($_REQUEST[$request_key])) // phpcs:ignore WordPress.Security.NonceVerification
                : $product->get_variation_default_attribute($attribute_name);

            wc_dropdown_variation_attribute_options([
                'options'          => $options,
                'attribute'        => $attribute_name,
                'product'          => $product,
                'selected'         => $selected,
                'show_option_none' => __('یک گزینه را انتخاب کنید', 'almasara-fast-cart'),
            ]);
            echo '</div>';
        }
        echo '</div>';

        printf(
            '<a class="amfc-atc__reset reset_variations" href="#" aria-label="%s">%s</a>',
            esc_attr($settings['reset_text']),
            esc_html($settings['reset_text'])
        );

        echo '<div class="single_variation_wrap">';
        if ('yes' === $settings['show_price']) {
            echo '<div class="amfc-atc__price-box" data-role="price"></div>';
        }
        echo '<div class="woocommerce-variation-add-to-cart variations_button amfc-atc__addrow">';
        echo '<input type="hidden" name="variation_id" class="variation_id" value="0" />';
        $this->render_quantity($settings, $product);
        $this->render_button($settings, $product, true);
        echo '</div>';
        echo '</div>';

        echo '</form>';
    }

    /** انتخابگر تعداد هنگام افزودن */
    private function render_quantity(array $settings, $product): void {
        if ('yes' !== $settings['show_quantity']) {
            return;
        }
        $max     = $this->max_qty($product);
        $stepper = 'stepper' === $settings['quantity_style'];

        echo '<div class="amfc-atc__qty' . ($stepper ? ' amfc-atc__qty--stepper' : '') . '">';
        if ($stepper) {
            echo '<button type="button" class="amfc-atc__step amfc-atc__step--minus" tabindex="-1" aria-label="' . esc_attr__('کاهش', 'almasara-fast-cart') . '">−</button>';
        }
        printf(
            '<input type="number" class="amfc-atc__qty-input" name="quantity" value="1" min="1"%s step="1" inputmode="numeric" aria-label="%s" />',
            $max > 0 ? ' max="' . esc_attr($max) . '"' : '',
            esc_attr__('تعداد', 'almasara-fast-cart')
        );
        if ($stepper) {
            echo '<button type="button" class="amfc-atc__step amfc-atc__step--plus" tabindex="-1" aria-label="' . esc_attr__('افزایش', 'almasara-fast-cart') . '">+</button>';
        }
        echo '</div>';
    }

    /** دکمه افزودن با لودر داخلی */
    private function render_button(array $settings, $product, bool $is_variable): void {
        $text = '' !== trim((string) $settings['button_text'])
            ? $settings['button_text']
            : $product->single_add_to_cart_text();

        $icon  = $this->get_icon_html($settings);
        $start = 'start' === $settings['icon_position'];

        printf('<button type="%s" class="amfc-atc__btn">', $is_variable ? 'submit' : 'button');
        echo '<span class="amfc-atc__btn-in">';
        if ($start) {
            echo $icon; // phpcs:ignore
        }
        echo '<span class="amfc-atc__text">' . esc_html($text) . '</span>';
        if (!$start) {
            echo $icon; // phpcs:ignore
        }
        echo '</span>';
        echo '<span class="amfc-atc__btn-loader" hidden aria-hidden="true"></span>';
        echo '</button>';
    }

    /** کنترل «در سبد شما» — مخفی تا وقتی JS تأیید کند در سبد است */
    private function render_incart_control(array $settings): void {
        ?>
        <div class="amfc-atc__incart" hidden>
            <div class="amfc-atc__incart-info">
                <span class="amfc-atc__incart-title"><?php echo esc_html($settings['incart_title']); ?></span>
                <a class="amfc-atc__incart-link" href="<?php echo esc_url(wc_get_cart_url()); ?>"><?php echo esc_html($settings['view_cart_text']); ?></a>
            </div>
            <div class="amfc-atc__control" data-key="" data-qty="1" data-max="0">
                <button type="button" class="amfc-atc__ctl amfc-atc__ctl--dec" aria-label="<?php echo esc_attr__('کاهش', 'almasara-fast-cart'); ?>">
                    <span class="amfc-atc__ctl-minus" aria-hidden="true">−</span>
                    <span class="amfc-atc__ctl-trash" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="1.1em" height="1.1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6M14 11v6"/></svg>
                    </span>
                </button>
                <span class="amfc-atc__ctl-num">
                    <span class="amfc-atc__ctl-value">۱</span>
                    <span class="amfc-atc__ctl-loader" hidden aria-hidden="true"></span>
                    <span class="amfc-atc__ctl-max" hidden><?php echo esc_html($settings['max_text']); ?></span>
                </span>
                <button type="button" class="amfc-atc__ctl amfc-atc__ctl--inc" aria-label="<?php echo esc_attr__('افزایش', 'almasara-fast-cart'); ?>">+</button>
            </div>
        </div>
        <?php
    }

    /* ---------------- کمکی‌ها ---------------- */

    /** جعبه قیمت محصول ساده (نسخه واریانت را JS با همین کلاس‌ها می‌سازد) */
    private function price_box_html($product): string {
        $regular = (float) wc_get_price_to_display($product, ['price' => $product->get_regular_price()]);
        $active  = (float) wc_get_price_to_display($product);
        $on_sale = $product->is_on_sale() && $regular > $active && $regular > 0;

        $out = '<div class="amfc-atc__price-box" data-role="price">';
        if ($on_sale) {
            $pct  = (int) round(($regular - $active) / $regular * 100);
            $out .= '<span class="amfc-atc__discount">' . $this->fa((string) $pct) . '<svg viewBox="0 0 24 24" width="0.9em" height="0.9em" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M9 15 15 9M9.5 9.5h.01M14.5 14.5h.01"/></svg></span>';
            $out .= '<del class="amfc-atc__regular">' . $this->fa(number_format($regular)) . '</del>';
        }
        $out .= '<span class="amfc-atc__final">' . $this->fa(number_format($active)) . '</span>';
        $out .= '<span class="amfc-atc__currency">' . esc_html__('تومان', 'almasara-fast-cart') . '</span>';
        $out .= '</div>';
        return $out;
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

    private function max_qty($product): int {
        if ($product->managing_stock() && !$product->backorders_allowed()) {
            return max(0, (int) $product->get_stock_quantity());
        }
        return 0;
    }

    private function fa(string $str): string {
        return strtr($str, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }

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

    /** خواندن و پاک‌سازی امنیتی SVG برای درج inline */
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
