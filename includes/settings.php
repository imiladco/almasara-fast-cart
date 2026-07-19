<?php
namespace Almasara_Fast_Cart;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * تنظیمات افزونه — زیرمنوی ووکامرس.
 *
 * «سلکتور شمارنده» قرارداد بین افزونه و پوسته است: افزونه عددِ تازه را در
 * هر عنصری که این سلکتور را دارد می‌نویسد. پیش‌فرض روی کلاس قراردادیِ
 * .amfc-count است؛ در پوسته این کلاس را به محل‌های شمارنده سبد بدهید یا
 * سلکتور واقعی پوسته را همین‌جا وارد کنید.
 */
final class Settings {

    const OPTION = 'amfc_settings';

    public static function defaults(): array {
        return [
            'count_selector'   => '.amfc-count,[data-amfc-count]',
            'toast_enabled'    => 'yes',
            'toast_text'       => __('به سبد خرید اضافه شد', 'almasara-fast-cart'),
            'prefetch_enabled' => 'yes',
        ];
    }

    public static function get(): array {
        $saved = get_option(self::OPTION, []);
        return wp_parse_args(is_array($saved) ? $saved : [], self::defaults());
    }

    public static function init(): void {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_init', [self::class, 'register']);
    }

    public static function menu(): void {
        add_submenu_page(
            'woocommerce',
            __('سبد سریع الماسارا', 'almasara-fast-cart'),
            __('سبد سریع', 'almasara-fast-cart'),
            'manage_woocommerce',
            'amfc-settings',
            [self::class, 'render']
        );
    }

    public static function register(): void {
        register_setting('amfc_group', self::OPTION, [
            'type'              => 'array',
            'sanitize_callback' => [self::class, 'sanitize'],
            'default'           => self::defaults(),
        ]);
    }

    public static function sanitize($input): array {
        $out = self::defaults();
        if (!is_array($input)) {
            return $out;
        }
        if (isset($input['count_selector'])) {
            $out['count_selector'] = sanitize_text_field($input['count_selector']);
        }
        $out['toast_enabled']    = (isset($input['toast_enabled']) && 'yes' === $input['toast_enabled']) ? 'yes' : 'no';
        $out['prefetch_enabled'] = (isset($input['prefetch_enabled']) && 'yes' === $input['prefetch_enabled']) ? 'yes' : 'no';
        if (isset($input['toast_text'])) {
            $out['toast_text'] = sanitize_text_field($input['toast_text']);
        }
        return $out;
    }

    public static function render(): void {
        $s = self::get();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('سبد سریع الماسارا', 'almasara-fast-cart'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('amfc_group'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="amfc_count_selector"><?php esc_html_e('سلکتور شمارنده سبد', 'almasara-fast-cart'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr(self::OPTION); ?>[count_selector]" id="amfc_count_selector" type="text" class="regular-text" value="<?php echo esc_attr($s['count_selector']); ?>" />
                            <p class="description"><?php esc_html_e('عنصرهایی که تعداد سبد باید در آن‌ها نوشته شود. کلاس amfc-count را در پوسته به این عناصر بدهید یا سلکتور واقعی پوسته را وارد کنید.', 'almasara-fast-cart'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('اعلان (Toast)', 'almasara-fast-cart'); ?></th>
                        <td>
                            <label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[toast_enabled]" value="yes" <?php checked('yes', $s['toast_enabled']); ?> /> <?php esc_html_e('نمایش اعلان هنگام افزودن', 'almasara-fast-cart'); ?></label>
                            <p><input name="<?php echo esc_attr(self::OPTION); ?>[toast_text]" type="text" class="regular-text" value="<?php echo esc_attr($s['toast_text']); ?>" /></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('پیش‌بارگذاری صفحه سبد', 'almasara-fast-cart'); ?></th>
                        <td>
                            <label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[prefetch_enabled]" value="yes" <?php checked('yes', $s['prefetch_enabled']); ?> /> <?php esc_html_e('Speculation Rules برای باز شدن آنی صفحه سبد', 'almasara-fast-cart'); ?></label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
