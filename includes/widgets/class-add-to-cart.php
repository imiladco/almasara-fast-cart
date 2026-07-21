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
        $this->register_price_content_controls();
        $this->register_quantity_content_controls();
        $this->register_incart_content_controls();
        $this->register_sticky_content_controls();

        $this->register_layout_style_controls();
        $this->register_sticky_style_controls();
        $this->register_button_style_controls();
        $this->register_price_layout_style_controls();
        $this->register_price_now_style_controls();
        $this->register_price_old_style_controls();
        $this->register_discount_style_controls();
        $this->register_quantity_style_controls();
        $this->register_variations_style_controls();
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

        $this->add_control('reset_text', [
            'label'   => __('متن «حذف انتخاب» (واریانت)', 'almasara-fast-cart'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('حذف انتخاب', 'almasara-fast-cart'),
        ]);

        $this->end_controls_section();
    }

    /* ---------------- محتوا: قیمت ---------------- */

    private function register_price_content_controls(): void {
        $this->start_controls_section('section_price_content', [
            'label' => __('قیمت', 'almasara-fast-cart'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_price', [
            'label'   => __('نمایش قیمت', 'almasara-fast-cart'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('show_regular_price', [
            'label'       => __('نمایش قیمت پیش از تخفیف', 'almasara-fast-cart'),
            'type'        => Controls_Manager::SWITCHER,
            'default'     => 'yes',
            'description' => __('قیمت قدیم فقط هنگامی نمایش داده می‌شود که محصول فروش ویژه داشته باشد.', 'almasara-fast-cart'),
            'condition'   => ['show_price' => 'yes'],
        ]);

        $this->add_control('show_discount_badge', [
            'label'     => __('بج درصد تخفیف', 'almasara-fast-cart'),
            'type'      => Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'condition' => ['show_price' => 'yes'],
        ]);

        $this->add_control('heading_currency', [
            'label'     => __('واحد پول', 'almasara-fast-cart'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['show_price' => 'yes'],
        ]);

        $this->add_control('currency_text', [
            'label'       => __('متن واحد پول', 'almasara-fast-cart'),
            'type'        => Controls_Manager::TEXT,
            'placeholder' => __('تومان', 'almasara-fast-cart'),
            'description' => __('خالی = نماد پیش‌فرض ووکامرس.', 'almasara-fast-cart'),
            'dynamic'     => ['active' => true],
            'condition'   => ['show_price' => 'yes'],
        ]);

        $this->add_control('currency_on_now', [
            'label'     => __('واحد پول برای قیمت فعلی', 'almasara-fast-cart'),
            'type'      => Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'condition' => ['show_price' => 'yes'],
        ]);

        $this->add_control('currency_on_old', [
            'label'     => __('واحد پول برای قیمت پیشین', 'almasara-fast-cart'),
            'type'      => Controls_Manager::SWITCHER,
            'condition' => ['show_price' => 'yes', 'show_regular_price' => 'yes'],
        ]);

        $this->add_control('free_text', [
            'label'       => __('متن «رایگان»', 'almasara-fast-cart'),
            'type'        => Controls_Manager::TEXT,
            'default'     => __('رایگان', 'almasara-fast-cart'),
            'description' => __('وقتی قیمت صفر باشد، این متن به‌جای عدد و واحد پول نمایش داده می‌شود. برای نمایش خودِ صفر، خالی بگذارید.', 'almasara-fast-cart'),
            'separator'   => 'before',
            'condition'   => ['show_price' => 'yes'],
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

    /* ---------------- محتوا: نوار چسبان موبایل ---------------- */

    private function register_sticky_content_controls(): void {
        $this->start_controls_section('section_sticky', [
            'label' => __('نوار چسبان (موبایل)', 'almasara-fast-cart'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('sticky_mobile', [
            'label'       => __('چسبیدن به پایین نمایشگر', 'almasara-fast-cart'),
            'type'        => Controls_Manager::SWITCHER,
            'description' => __('در موبایل، نوار (قیمت + دکمه/کنترل «در سبد») به پایین نمایشگر می‌چسبد؛ انتخابگرهای محصول متغیر سر جای خود در صفحه می‌مانند. استایل نوار در تب استایل → «نوار چسبان» است.', 'almasara-fast-cart'),
        ]);

        $this->add_control('sticky_notice', [
            'label'       => __('اطلاعیه «کالا اضافه شده»', 'almasara-fast-cart'),
            'type'        => Controls_Manager::SWITCHER,
            'default'     => 'yes',
            'description' => __('بعد از افزودن، نوار سبز تأیید با انیمیشن از پایین بالای نوار ظاهر و بعد از چند ثانیه به پایین محو می‌شود.', 'almasara-fast-cart'),
            'condition'   => ['sticky_mobile' => 'yes'],
        ]);

        $this->add_control('notice_text', [
            'label'     => __('متن اطلاعیه', 'almasara-fast-cart'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('کالا اضافه شده', 'almasara-fast-cart'),
            'condition' => ['sticky_mobile' => 'yes', 'sticky_notice' => 'yes'],
        ]);

        $this->add_control('notice_link_text', [
            'label'     => __('متن دکمه سبد', 'almasara-fast-cart'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('برو به سبد خرید', 'almasara-fast-cart'),
            'condition' => ['sticky_mobile' => 'yes', 'sticky_notice' => 'yes'],
        ]);

        $this->add_control('notice_duration', [
            'label'     => __('مدت نمایش (ثانیه)', 'almasara-fast-cart'),
            'type'      => Controls_Manager::NUMBER,
            'default'   => 4,
            'min'       => 1,
            'max'       => 15,
            'condition' => ['sticky_mobile' => 'yes', 'sticky_notice' => 'yes'],
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
                '{{WRAPPER}} .amfc-atc'             => 'gap: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .amfc-atc__variations' => 'gap: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .amfc-atc__bar-main'   => 'gap: {{SIZE}}{{UNIT}};',
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

    /* ---------------- استایل: نوار چسبان موبایل ---------------- */

    /**
     * همه کنترل‌ها متغیر CSS روی ریشه ست می‌کنند؛ خود ظاهر نوار فقط داخل
     * media query موبایل مصرف می‌شود تا هیچ اثری روی دسکتاپ نگذارد.
     */
    private function register_sticky_style_controls(): void {
        $this->start_controls_section('section_style_sticky', [
            'label'     => __('نوار چسبان (موبایل)', 'almasara-fast-cart'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['sticky_mobile' => 'yes'],
        ]);

        $this->add_control('bar_bg', [
            'label'     => __('رنگ پس‌زمینه نوار', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc' => '--amfc-bar-bg: {{VALUE}};'],
        ]);

        $this->add_control('bar_border_color', [
            'label'     => __('رنگ خط بالای نوار', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc' => '--amfc-bar-border: {{VALUE}};'],
        ]);

        $this->add_control('bar_shadow_color', [
            'label'     => __('رنگ سایه نوار', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc' => '--amfc-bar-shadow: 0 -8px 28px {{VALUE}};'],
        ]);

        $this->add_control('bar_padding', [
            'label'     => __('پدینگ نوار', 'almasara-fast-cart'),
            'type'      => Controls_Manager::DIMENSIONS,
            'size_units' => ['px'],
            'selectors' => [
                '{{WRAPPER}} .amfc-atc' => '--amfc-bar-pt: {{TOP}}{{UNIT}}; --amfc-bar-pr: {{RIGHT}}{{UNIT}}; --amfc-bar-pb: {{BOTTOM}}{{UNIT}}; --amfc-bar-pl: {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_control('bar_radius', [
            'label'       => __('گردی گوشه‌های نوار', 'almasara-fast-cart'),
            'type'        => Controls_Manager::DIMENSIONS,
            'size_units'  => ['px'],
            'description' => __('برای حالت کلاسیک فقط دو گوشه بالا را گرد کنید.', 'almasara-fast-cart'),
            'selectors'   => [
                '{{WRAPPER}} .amfc-atc' => '--amfc-bar-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_control('bar_offset_bottom', [
            'label'       => __('فاصله از پایین نمایشگر', 'almasara-fast-cart'),
            'type'        => Controls_Manager::SLIDER,
            'size_units'  => ['px'],
            'range'       => ['px' => ['min' => 0, 'max' => 160]],
            'description' => __('اگر منوی ثابت پایین موبایل دارید، به‌اندازه ارتفاع آن فاصله بدهید تا نوار بالای منو بنشیند.', 'almasara-fast-cart'),
            'selectors'   => ['{{WRAPPER}} .amfc-atc' => '--amfc-bar-b: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('bar_offset_x', [
            'label'      => __('فاصله از کناره‌ها', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 60]],
            'selectors'  => ['{{WRAPPER}} .amfc-atc' => '--amfc-bar-x: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('bar_zindex', [
            'label'       => __('z-index', 'almasara-fast-cart'),
            'type'        => Controls_Manager::NUMBER,
            'description' => __('اگر منوی پایین پوسته روی نوار می‌افتد، این عدد را بزرگ‌تر کنید.', 'almasara-fast-cart'),
            'selectors'   => ['{{WRAPPER}} .amfc-atc' => '--amfc-bar-z: {{VALUE}};'],
        ]);

        $this->add_control('heading_notice_style', [
            'label'     => __('اطلاعیه «کالا اضافه شده»', 'almasara-fast-cart'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['sticky_notice' => 'yes'],
        ]);

        $this->add_control('notice_bg', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc' => '--amfc-ntc-bg: {{VALUE}};'],
            'condition' => ['sticky_notice' => 'yes'],
        ]);

        $this->add_control('notice_color', [
            'label'     => __('رنگ متن و آیکون', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc' => '--amfc-ntc-color: {{VALUE}};'],
            'condition' => ['sticky_notice' => 'yes'],
        ]);

        $this->add_control('notice_link_color', [
            'label'     => __('رنگ متن دکمه سبد', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc' => '--amfc-ntc-link: {{VALUE}};'],
            'condition' => ['sticky_notice' => 'yes'],
        ]);

        $this->add_control('notice_link_bg', [
            'label'     => __('پس‌زمینه دکمه سبد', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc' => '--amfc-ntc-link-bg: {{VALUE}};'],
            'condition' => ['sticky_notice' => 'yes'],
        ]);

        $this->add_control('notice_radius', [
            'label'      => __('گردی گوشه اطلاعیه', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'selectors'  => ['{{WRAPPER}} .amfc-atc' => '--amfc-ntc-radius: {{SIZE}}{{UNIT}};'],
            'condition'  => ['sticky_notice' => 'yes'],
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

        $this->add_responsive_control('btn_height', [
            'label'      => __('حداقل ارتفاع', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 36, 'max' => 90]],
            'selectors'  => ['{{WRAPPER}} .amfc-atc__btn' => 'min-height: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('btn_loader_color', [
            'label'     => __('رنگ لودر دکمه', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__btn-loader' => 'color: {{VALUE}};'],
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
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'btn_shadow_hover',
            'selector' => '{{WRAPPER}} .amfc-atc__btn:hover',
        ]);
        $this->add_control('btn_transform_hover', [
            'label'      => __('جابه‌جایی عمودی', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => -10, 'max' => 10]],
            'selectors'  => ['{{WRAPPER}} .amfc-atc__btn:hover' => 'transform: translateY({{SIZE}}px);'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('btn_disabled', ['label' => __('غیرفعال', 'almasara-fast-cart')]);
        $this->add_control('btn_disabled_note', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => __('محصول متغیر تا انتخاب کامل واریانت، دکمه غیرفعال است.', 'almasara-fast-cart'),
            'content_classes' => 'elementor-descriptor',
        ]);
        $this->add_control('btn_bg_disabled', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__btn:disabled' => 'background: {{VALUE}};'],
        ]);
        $this->add_control('btn_color_disabled', [
            'label'     => __('رنگ متن و آیکون', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__btn:disabled' => 'color: {{VALUE}};'],
        ]);
        $this->add_control('btn_opacity_disabled', [
            'label'     => __('شفافیت', 'almasara-fast-cart'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => ['px' => ['min' => 0.1, 'max' => 1, 'step' => 0.05]],
            'selectors' => ['{{WRAPPER}} .amfc-atc__btn:disabled' => 'opacity: {{SIZE}};'],
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

        $this->add_control('qty_border_focus', [
            'label'     => __('رنگ حاشیه در فوکوس', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__qty:focus-within' => 'border-color: {{VALUE}};'],
        ]);

        $this->add_responsive_control('qty_radius', [
            'label'      => __('رادیوس', 'almasara-fast-cart'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [
                '{{WRAPPER}} .amfc-atc__qty' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('qty_input_width', [
            'label'      => __('عرض فیلد عدد', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 30, 'max' => 120]],
            'selectors'  => ['{{WRAPPER}} .amfc-atc__qty-input' => 'width: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('heading_steps', [
            'label'     => __('دکمه‌های − و +', 'almasara-fast-cart'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['quantity_style' => 'stepper'],
        ]);

        $this->add_responsive_control('step_width', [
            'label'     => __('عرض دکمه‌ها', 'almasara-fast-cart'),
            'type'      => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'     => ['px' => ['min' => 24, 'max' => 70]],
            'selectors' => ['{{WRAPPER}} .amfc-atc__step' => 'width: {{SIZE}}{{UNIT}};'],
            'condition' => ['quantity_style' => 'stepper'],
        ]);

        $this->add_control('step_color', [
            'label'     => __('رنگ دکمه‌ها', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__step' => 'color: {{VALUE}};'],
            'condition' => ['quantity_style' => 'stepper'],
        ]);

        $this->add_control('step_bg_hover', [
            'label'     => __('پس‌زمینه دکمه‌ها در هاور', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__step:hover' => 'background-color: {{VALUE}};'],
            'condition' => ['quantity_style' => 'stepper'],
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

        $this->add_responsive_control('attrs_columns', [
            'label'                => __('چیدمان ستونی', 'almasara-fast-cart'),
            'type'                 => Controls_Manager::CHOOSE,
            'default'              => '1',
            'options'              => [
                '1' => ['title' => __('تک‌ستونه', 'almasara-fast-cart'), 'icon' => 'eicon-editor-list-ul'],
                '2' => ['title' => __('دوستونه', 'almasara-fast-cart'), 'icon' => 'eicon-gallery-grid'],
            ],
            'selectors_dictionary' => [
                '1' => 'grid-template-columns: 1fr;',
                '2' => 'grid-template-columns: repeat(2, 1fr);',
            ],
            'selectors'            => ['{{WRAPPER}} .amfc-atc__attrs' => '{{VALUE}}'],
        ]);

        $this->add_responsive_control('attr_gap', [
            'label'      => __('فاصله بین واریانت‌ها', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amfc-atc__attrs' => 'gap: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('attr_label_gap', [
            'label'      => __('فاصله عنوان تا دراپ‌داون', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 30]],
            'selectors'  => ['{{WRAPPER}} .amfc-atc__attr' => 'gap: {{SIZE}}{{UNIT}};'],
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

        $this->add_responsive_control('select_arrow_size', [
            'label'      => __('اندازه فلش', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 4, 'max' => 14]],
            'selectors'  => ['{{WRAPPER}} .amfc-atc__attr' => '--amfc-arrow-s: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('select_padding', [
            'label'      => __('پدینگ', 'almasara-fast-cart'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .amfc-atc__attr select' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'select_border',
            'selector' => '{{WRAPPER}} .amfc-atc__attr select',
        ]);

        $this->add_control('select_border_focus', [
            'label'     => __('رنگ حاشیه در فوکوس', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__attr select:focus' => 'border-color: {{VALUE}}; outline: none;'],
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

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'reset_typo',
            'label'    => __('تایپوگرافی', 'almasara-fast-cart'),
            'selector' => '{{WRAPPER}} .amfc-atc__reset',
        ]);

        $this->add_responsive_control('reset_padding', [
            'label'      => __('پدینگ', 'almasara-fast-cart'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .amfc-atc__reset' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('reset_radius', [
            'label'      => __('رادیوس', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 30]],
            'selectors'  => ['{{WRAPPER}} .amfc-atc__reset' => 'border-radius: {{SIZE}}{{UNIT}};'],
        ]);

        $this->start_controls_tabs('reset_tabs');

        $this->start_controls_tab('reset_normal', ['label' => __('عادی', 'almasara-fast-cart')]);
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
        $this->end_controls_tab();

        $this->start_controls_tab('reset_hover', ['label' => __('هاور', 'almasara-fast-cart')]);
        $this->add_control('reset_color_hover', [
            'label'     => __('رنگ متن', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__reset:hover' => 'color: {{VALUE}};'],
        ]);
        $this->add_control('reset_bg_hover', [
            'label'     => __('رنگ پس‌زمینه', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__reset:hover' => 'background-color: {{VALUE}};'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    /* ---------------- استایل: قیمت ---------------- */

    private function register_price_layout_style_controls(): void {
        $this->start_controls_section('section_style_price', [
            'label'     => __('قیمت — چیدمان', 'almasara-fast-cart'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_price' => 'yes'],
        ]);

        $this->add_responsive_control('price_direction', [
            'label'                => __('جهت چیدمان دو قیمت', 'almasara-fast-cart'),
            'type'                 => Controls_Manager::CHOOSE,
            'default'              => 'row',
            'options'              => [
                'row'         => ['title' => __('افقی', 'almasara-fast-cart'), 'icon' => 'eicon-arrow-left'],
                'row-reverse' => ['title' => __('افقی معکوس', 'almasara-fast-cart'), 'icon' => 'eicon-arrow-right'],
                'column'      => ['title' => __('عمودی', 'almasara-fast-cart'), 'icon' => 'eicon-arrow-down'],
            ],
            'selectors_dictionary' => [
                'row'         => 'flex-direction: row;',
                'row-reverse' => 'flex-direction: row-reverse;',
                'column'      => 'flex-direction: column;',
            ],
            'selectors'            => ['{{WRAPPER}} .amfc-atc__price-box' => '{{VALUE}}'],
        ]);

        $this->add_control('old_position', [
            'label'                => __('جای قیمت پیشین', 'almasara-fast-cart'),
            'type'                 => Controls_Manager::CHOOSE,
            'default'              => 'before',
            'options'              => [
                'before' => ['title' => __('پیش از قیمت فعلی', 'almasara-fast-cart'), 'icon' => 'eicon-order-start'],
                'after'  => ['title' => __('پس از قیمت فعلی', 'almasara-fast-cart'), 'icon' => 'eicon-order-end'],
            ],
            'selectors_dictionary' => [
                'before' => 'order: 0;',
                'after'  => 'order: 4;',
            ],
            'selectors'            => ['{{WRAPPER}} .amfc-atc__price--old' => '{{VALUE}}'],
            'condition'            => ['show_regular_price' => 'yes'],
        ]);

        $this->add_responsive_control('price_justify', [
            'label'       => __('توزیع در راستای چیدمان', 'almasara-fast-cart'),
            'type'        => Controls_Manager::CHOOSE,
            'default'     => 'flex-start',
            'options'     => [
                'flex-start'    => ['title' => __('شروع', 'almasara-fast-cart'), 'icon' => 'eicon-flex eicon-justify-start-h'],
                'center'        => ['title' => __('وسط', 'almasara-fast-cart'), 'icon' => 'eicon-flex eicon-justify-center-h'],
                'flex-end'      => ['title' => __('پایان', 'almasara-fast-cart'), 'icon' => 'eicon-flex eicon-justify-end-h'],
                'space-between' => ['title' => __('دو سرِ کادر', 'almasara-fast-cart'), 'icon' => 'eicon-flex eicon-justify-space-between-h'],
            ],
            'description' => __('با «دو سرِ کادر» بج تخفیف و قیمت به دو طرف عرض کادر می‌چسبند — مثل دیزاین.', 'almasara-fast-cart'),
            'selectors'   => ['{{WRAPPER}} .amfc-atc__price-box' => 'justify-content: {{VALUE}};'],
        ]);

        $this->add_responsive_control('price_align_v', [
            'label'     => __('تراز عرضی', 'almasara-fast-cart'),
            'type'      => Controls_Manager::CHOOSE,
            'default'   => 'center',
            'options'   => [
                'flex-start' => ['title' => __('بالا', 'almasara-fast-cart'), 'icon' => 'eicon-v-align-top'],
                'center'     => ['title' => __('وسط', 'almasara-fast-cart'), 'icon' => 'eicon-v-align-middle'],
                'baseline'   => ['title' => __('خط کرسی', 'almasara-fast-cart'), 'icon' => 'eicon-align-stretch-v'],
                'flex-end'   => ['title' => __('پایین', 'almasara-fast-cart'), 'icon' => 'eicon-v-align-bottom'],
            ],
            'selectors' => ['{{WRAPPER}} .amfc-atc__price-box' => 'align-items: {{VALUE}};'],
        ]);

        $this->add_responsive_control('price_gap', [
            'label'      => __('فاصله بین دو قیمت', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'default'    => ['size' => 10, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amfc-atc__price-box' => 'gap: {{SIZE}}{{UNIT}};'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: قیمت فعلی ---------------- */

    private function register_price_now_style_controls(): void {
        $this->start_controls_section('section_style_price_now', [
            'label'     => __('قیمت فعلی', 'almasara-fast-cart'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_price' => 'yes'],
        ]);

        $this->add_control('heading_now_layout', [
            'label'     => __('چیدمان عدد و واحد پول', 'almasara-fast-cart'),
            'type'      => Controls_Manager::HEADING,
            'condition' => ['currency_on_now' => 'yes'],
        ]);

        $this->add_control('now_unit_position', [
            'label'                => __('جایگاه واحد پول', 'almasara-fast-cart'),
            'type'                 => Controls_Manager::CHOOSE,
            'default'              => 'after',
            'options'              => [
                'after'  => ['title' => __('پس از عدد', 'almasara-fast-cart'), 'icon' => 'eicon-order-end'],
                'before' => ['title' => __('پیش از عدد', 'almasara-fast-cart'), 'icon' => 'eicon-order-start'],
            ],
            'selectors_dictionary' => [
                'after'  => 'flex-direction: row;',
                'before' => 'flex-direction: row-reverse;',
            ],
            'selectors'            => ['{{WRAPPER}} .amfc-atc__price--now' => '{{VALUE}}'],
            'condition'            => ['currency_on_now' => 'yes'],
        ]);

        $this->add_responsive_control('now_unit_align', [
            'label'     => __('تراز عمودی عدد و واحد', 'almasara-fast-cart'),
            'type'      => Controls_Manager::CHOOSE,
            'default'   => 'baseline',
            'options'   => [
                'flex-start' => ['title' => __('بالا', 'almasara-fast-cart'), 'icon' => 'eicon-v-align-top'],
                'center'     => ['title' => __('وسط', 'almasara-fast-cart'), 'icon' => 'eicon-v-align-middle'],
                'baseline'   => ['title' => __('خط کرسی', 'almasara-fast-cart'), 'icon' => 'eicon-align-stretch-v'],
                'flex-end'   => ['title' => __('پایین', 'almasara-fast-cart'), 'icon' => 'eicon-v-align-bottom'],
            ],
            'selectors' => ['{{WRAPPER}} .amfc-atc__price--now' => 'align-items: {{VALUE}};'],
            'condition' => ['currency_on_now' => 'yes'],
        ]);

        $this->add_responsive_control('now_unit_gap', [
            'label'      => __('فاصله عدد تا واحد پول', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 30]],
            'default'    => ['size' => 3, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .amfc-atc__price--now' => 'gap: {{SIZE}}{{UNIT}};'],
            'condition'  => ['currency_on_now' => 'yes'],
        ]);

        $this->add_control('heading_now_box', [
            'label'     => __('کادر قیمت', 'almasara-fast-cart'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_responsive_control('now_padding', [
            'label'      => __('پدینگ بلوک', 'almasara-fast-cart'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .amfc-atc__price--now' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('now_radius', [
            'label'      => __('گردی گوشه بلوک', 'almasara-fast-cart'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [
                '{{WRAPPER}} .amfc-atc__price--now' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'     => 'now_bg',
            'label'    => __('نوع پس‌زمینه', 'almasara-fast-cart'),
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .amfc-atc__price--now',
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'now_border',
            'label'    => __('نوع حاشیه', 'almasara-fast-cart'),
            'selector' => '{{WRAPPER}} .amfc-atc__price--now',
        ]);

        $this->add_control('heading_now_style', [
            'label'     => __('استایل کلی قیمت و واحد', 'almasara-fast-cart'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'price_typo',
            'label'    => __('تایپوگرافی', 'almasara-fast-cart'),
            'selector' => '{{WRAPPER}} .amfc-atc__price--now',
        ]);

        $this->add_control('price_color', [
            'label'     => __('رنگ', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__price--now' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('now_unit_custom', [
            'label'       => __('استایل منحصربه‌فرد واحد پول', 'almasara-fast-cart'),
            'type'        => Controls_Manager::SWITCHER,
            'description' => __('با فعال‌کردن، می‌توانید برای واحد پول این قیمت استایل جداگانه‌ای جدا از عدد تعیین کنید.', 'almasara-fast-cart'),
            'separator'   => 'before',
            'condition'   => ['currency_on_now' => 'yes'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'      => 'currency_typo',
            'label'     => __('تایپوگرافی واحد پول', 'almasara-fast-cart'),
            'selector'  => '{{WRAPPER}} .amfc-atc__price--now .amfc-atc__unit',
            'condition' => ['now_unit_custom' => 'yes'],
        ]);

        $this->add_control('currency_color', [
            'label'     => __('رنگ واحد پول', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__price--now .amfc-atc__unit' => 'color: {{VALUE}};'],
            'condition' => ['now_unit_custom' => 'yes'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: قیمت پیش از تخفیف ---------------- */

    private function register_price_old_style_controls(): void {
        $this->start_controls_section('section_style_price_old', [
            'label'     => __('قیمت پیش از تخفیف', 'almasara-fast-cart'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_price' => 'yes', 'show_regular_price' => 'yes'],
        ]);

        $this->add_responsive_control('old_unit_gap', [
            'label'      => __('فاصله عدد تا واحد پول', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 30]],
            'selectors'  => ['{{WRAPPER}} .amfc-atc__price--old' => 'gap: {{SIZE}}{{UNIT}};'],
            'condition'  => ['currency_on_old' => 'yes'],
        ]);

        $this->add_responsive_control('old_padding', [
            'label'      => __('پدینگ بلوک', 'almasara-fast-cart'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .amfc-atc__price--old' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('old_radius', [
            'label'      => __('گردی گوشه بلوک', 'almasara-fast-cart'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [
                '{{WRAPPER}} .amfc-atc__price--old' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'     => 'old_bg',
            'label'    => __('نوع پس‌زمینه', 'almasara-fast-cart'),
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .amfc-atc__price--old',
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'old_border',
            'label'    => __('نوع حاشیه', 'almasara-fast-cart'),
            'selector' => '{{WRAPPER}} .amfc-atc__price--old',
        ]);

        $this->add_control('old_strike', [
            'label'       => __('خط‌خورده', 'almasara-fast-cart'),
            'type'        => Controls_Manager::SWITCHER,
            'default'     => 'yes',
            'description' => __('روی کل قیمت پیشین (عدد و واحد) خط کشیده می‌شود.', 'almasara-fast-cart'),
            'separator'   => 'before',
            'selectors'   => ['{{WRAPPER}} .amfc-atc__price--old' => 'text-decoration: line-through;'],
        ]);

        $this->add_control('heading_old_style', [
            'label'     => __('استایل کلی قیمت و واحد', 'almasara-fast-cart'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'regular_typo',
            'label'    => __('تایپوگرافی', 'almasara-fast-cart'),
            'selector' => '{{WRAPPER}} .amfc-atc__price--old',
        ]);

        $this->add_control('regular_color', [
            'label'     => __('رنگ', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__price--old' => 'color: {{VALUE}};'],
        ]);

        $this->end_controls_section();
    }

    /* ---------------- استایل: بج تخفیف ---------------- */

    private function register_discount_style_controls(): void {
        $this->start_controls_section('section_style_discount', [
            'label'     => __('بج تخفیف', 'almasara-fast-cart'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_price' => 'yes', 'show_discount_badge' => 'yes'],
        ]);

        $this->add_control('discount_position', [
            'label'                => __('موقعیت بج', 'almasara-fast-cart'),
            'type'                 => Controls_Manager::CHOOSE,
            'default'              => 'start',
            'options'              => [
                'start' => ['title' => __('ابتدای ردیف', 'almasara-fast-cart'), 'icon' => 'eicon-order-start'],
                'end'   => ['title' => __('انتهای ردیف', 'almasara-fast-cart'), 'icon' => 'eicon-order-end'],
            ],
            'selectors_dictionary' => [
                'start' => 'order: -1;',
                'end'   => 'order: 10;',
            ],
            'selectors'            => ['{{WRAPPER}} .amfc-atc__discount' => '{{VALUE}}'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'discount_typo',
            'selector' => '{{WRAPPER}} .amfc-atc__discount',
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

        $this->add_responsive_control('discount_padding', [
            'label'      => __('پدینگ', 'almasara-fast-cart'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .amfc-atc__discount' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('discount_radius', [
            'label'      => __('رادیوس', 'almasara-fast-cart'),
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

        $this->add_responsive_control('incart_gap', [
            'label'      => __('فاصله متن‌ها تا کنترل', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 60]],
            'selectors'  => ['{{WRAPPER}} .amfc-atc__incart' => 'gap: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('heading_incart_texts', [
            'label'     => __('عنوان و لینک', 'almasara-fast-cart'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
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

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'view_link_typo',
            'label'    => __('تایپوگرافی لینک مشاهده سبد', 'almasara-fast-cart'),
            'selector' => '{{WRAPPER}} .amfc-atc__incart-link',
        ]);

        $this->add_control('view_link_color', [
            'label'     => __('رنگ لینک مشاهده سبد', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__incart-link' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('view_link_color_hover', [
            'label'     => __('رنگ لینک در هاور', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__incart-link:hover' => 'color: {{VALUE}};'],
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

        $this->add_responsive_control('control_padding', [
            'label'      => __('پدینگ افقی', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'selectors'  => ['{{WRAPPER}} .amfc-atc__control' => 'padding-inline: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('ctl_size', [
            'label'      => __('اندازه دکمه‌های − و +', 'almasara-fast-cart'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 24, 'max' => 64]],
            'selectors'  => ['{{WRAPPER}} .amfc-atc__ctl' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('ctl_bg_hover', [
            'label'     => __('پس‌زمینه دکمه‌ها در هاور', 'almasara-fast-cart'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .amfc-atc__ctl:hover' => 'background-color: {{VALUE}};'],
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

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'max_typo',
            'label'    => __('تایپوگرافی متن حداکثر', 'almasara-fast-cart'),
            'selector' => '{{WRAPPER}} .amfc-atc__ctl-max',
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

        // کانفیگ قیمت برای JS: قیمت واریانت سمت کلاینت با همین تنظیمات ساخته می‌شود
        $price_cfg = [
            'unit'    => $this->currency_text($settings),
            'unitNow' => 'yes' === ($settings['currency_on_now'] ?? 'yes'),
            'unitOld' => 'yes' === ($settings['currency_on_old'] ?? ''),
            'old'     => 'yes' === ($settings['show_regular_price'] ?? 'yes'),
            'badge'   => 'yes' === ($settings['show_discount_badge'] ?? 'yes'),
            'free'    => trim((string) ($settings['free_text'] ?? '')),
        ];

        $classes = 'amfc-atc amfc-atc--' . $type;
        if ('yes' === ($settings['sticky_mobile'] ?? '')) {
            $classes .= ' amfc-atc--stickym';
        }

        printf(
            '<div class="%1$s" data-product="%2$d" data-type="%3$s" data-max-text="%4$s" data-price-cfg="%5$s" data-notice-ms="%6$d">',
            esc_attr($classes),
            (int) $product->get_id(),
            esc_attr($type),
            esc_attr($settings['max_text']),
            esc_attr(wp_json_encode($price_cfg)),
            max(1, (int) ($settings['notice_duration'] ?? 4)) * 1000
        );

        if ($product->is_type('variable')) {
            $this->render_variable($settings, $product);
        } else {
            $this->render_simple($settings, $product);
        }

        echo '</div>';
    }

    /** محصول ساده: نوار = قیمت + ردیف افزودن + کنترل «در سبد» + اطلاعیه */
    private function render_simple(array $settings, $product): void {
        echo '<div class="amfc-atc__bar">';
        $this->render_notice($settings);
        echo '<div class="amfc-atc__bar-main">';
        if ('yes' === $settings['show_price']) {
            echo $this->price_box_html($product, $settings); // phpcs:ignore
        }
        echo '<div class="amfc-atc__addrow">';
        $this->render_quantity($settings, $product);
        $this->render_button($settings, $product, false);
        echo '</div>';
        $this->render_incart_control($settings);
        echo '</div></div>';
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

        // wrap واریانت خودِ نوار است: قیمت + ردیف افزودن + کنترل «در سبد» + اطلاعیه
        echo '<div class="single_variation_wrap amfc-atc__bar">';
        $this->render_notice($settings);
        echo '<div class="amfc-atc__bar-main">';
        if ('yes' === $settings['show_price']) {
            echo '<div class="amfc-atc__price-box" data-role="price"></div>';
        }
        echo '<div class="woocommerce-variation-add-to-cart variations_button amfc-atc__addrow">';
        echo '<input type="hidden" name="variation_id" class="variation_id" value="0" />';
        $this->render_quantity($settings, $product);
        $this->render_button($settings, $product, true);
        echo '</div>';
        $this->render_incart_control($settings);
        echo '</div>';
        echo '</div>';

        echo '</form>';
    }

    /** اطلاعیه «کالا اضافه شده» — فقط در نوار چسبان موبایل نمایش داده می‌شود */
    private function render_notice(array $settings): void {
        if ('yes' !== ($settings['sticky_mobile'] ?? '') || 'yes' !== ($settings['sticky_notice'] ?? 'yes')) {
            return;
        }
        ?>
        <div class="amfc-atc__notice" aria-live="polite">
            <div class="amfc-atc__notice-in">
                <span class="amfc-atc__notice-msg">
                    <svg viewBox="0 0 24 24" width="1.3em" height="1.3em" fill="currentColor" aria-hidden="true"><path d="M12 1.8 14.4 3.6l2.9-.5 1.1 2.8 2.8 1.1-.5 2.9L22.2 12l-1.5 2.4.5 2.9-2.8 1.1-1.1 2.8-2.9-.5L12 22.2l-2.4-1.5-2.9.5-1.1-2.8-2.8-1.1.5-2.9L1.8 12l1.5-2.4-.5-2.9 2.8-1.1 1.1-2.8 2.9.5Z"/><path d="m8.5 12 2.3 2.3 4.7-4.6" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <?php echo esc_html($settings['notice_text'] ?? __('کالا اضافه شده', 'almasara-fast-cart')); ?>
                </span>
                <a class="amfc-atc__notice-link" href="<?php echo esc_url(wc_get_cart_url()); ?>">
                    <?php echo esc_html($settings['notice_link_text'] ?? __('برو به سبد خرید', 'almasara-fast-cart')); ?>
                    <svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg>
                </a>
            </div>
        </div>
        <?php
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

    /** جعبه قیمت محصول ساده (نسخه واریانت را JS با همین کلاس‌ها و همین تنظیمات می‌سازد) */
    private function price_box_html($product, array $settings): string {
        $regular  = (float) wc_get_price_to_display($product, ['price' => $product->get_regular_price()]);
        $active   = (float) wc_get_price_to_display($product);
        $on_sale  = $product->is_on_sale() && $regular > $active && $regular > 0;
        $unit     = '<span class="amfc-atc__unit">' . esc_html($this->currency_text($settings)) . '</span>';
        $free     = trim((string) ($settings['free_text'] ?? ''));

        $out = '<div class="amfc-atc__price-box" data-role="price">';

        if ($on_sale && 'yes' === ($settings['show_discount_badge'] ?? 'yes')) {
            $pct  = (int) round(($regular - $active) / $regular * 100);
            $out .= '<span class="amfc-atc__discount">' . $this->fa((string) $pct) . '<svg viewBox="0 0 24 24" width="0.9em" height="0.9em" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M9 15 15 9M9.5 9.5h.01M14.5 14.5h.01"/></svg></span>';
        }

        if ($on_sale && 'yes' === ($settings['show_regular_price'] ?? 'yes')) {
            $out .= '<del class="amfc-atc__price amfc-atc__price--old"><span class="amfc-atc__num">' . $this->fa(number_format($regular)) . '</span>';
            if ('yes' === ($settings['currency_on_old'] ?? '')) {
                $out .= $unit;
            }
            $out .= '</del>';
        }

        $out .= '<span class="amfc-atc__price amfc-atc__price--now">';
        if ($active <= 0 && '' !== $free) {
            $out .= '<span class="amfc-atc__num amfc-atc__num--free">' . esc_html($free) . '</span>';
        } else {
            $out .= '<span class="amfc-atc__num">' . $this->fa(number_format($active)) . '</span>';
            if ('yes' === ($settings['currency_on_now'] ?? 'yes')) {
                $out .= $unit;
            }
        }
        $out .= '</span></div>';

        return $out;
    }

    /** متن واحد پول: تنظیم ویجت یا نماد پیش‌فرض ووکامرس */
    private function currency_text(array $settings): string {
        $text = trim((string) ($settings['currency_text'] ?? ''));
        if ('' !== $text) {
            return $text;
        }
        $symbol = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '';
        return html_entity_decode((string) $symbol, ENT_QUOTES, 'UTF-8');
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
