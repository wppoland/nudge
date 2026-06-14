<?php

declare(strict_types=1);

namespace Nudge\Admin;

defined('ABSPATH') || exit;

use Nudge\Contract\HasHooks;

/**
 * Admin settings page registered as a WooCommerce submenu ("WooCommerce →
 * Nudge").
 *
 * Stores settings in the `nudge_settings` option (array): enable, threshold
 * source (auto vs manual) and the manual amount, the progress/success messages
 * (with the {amount} token), bar colours, and where the bar shows. All output is
 * escaped; all input is sanitised and clamped on save.
 */
final class Settings implements HasHooks
{
    private const OPTION = 'nudge_settings';
    private const PAGE   = 'nudge-settings';

    private const SOURCES = ['auto', 'manual'];

    /** Incremented to give each inline-help control a unique id/anchor. */
    private int $helpSeq = 0;

    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(string $hook): void
    {
        if ($hook !== 'woocommerce_page_' . self::PAGE) {
            return;
        }

        wp_enqueue_style(
            'nudge-admin',
            NUDGE_URL . 'assets/css/admin.css',
            [],
            \Nudge\VERSION,
        );

        wp_enqueue_script(
            'nudge-admin',
            NUDGE_URL . 'assets/js/admin.js',
            [],
            \Nudge\VERSION,
            ['in_footer' => true, 'strategy' => 'defer'],
        );
    }

    public function addMenuPage(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Nudge — Free Shipping Bar', 'nudge'),
            __('Nudge', 'nudge'),
            'manage_woocommerce',
            self::PAGE,
            [$this, 'renderPage'],
        );
    }

    public function registerSettings(): void
    {
        register_setting(
            self::PAGE,
            self::OPTION,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitize'],
            ],
        );

        add_filter(
            'option_page_capability_' . self::PAGE,
            static fn (): string => 'manage_woocommerce',
        );
    }

    public function renderPage(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        $settings = $this->settings();
        ?>
        <div class="wrap nudge-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="nudge-intro">
                <h2><?php esc_html_e('Nudge shoppers toward free shipping', 'nudge'); ?></h2>
                <p>
                    <?php esc_html_e('A friendly progress bar that shows customers how close they are to free shipping — and how much more to add to unlock it. It updates live as the cart changes. Configure it below; the live preview on the right reflects your choices.', 'nudge'); ?>
                </p>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields(self::PAGE); ?>

                <div class="nudge-layout">
                    <div class="nudge-settings">
                        <div class="nudge-card">
                            <h2><?php esc_html_e('General', 'nudge'); ?></h2>
                            <table class="form-table" role="presentation">
                                <tbody>
                                    <tr>
                                        <th scope="row">
                                            <?php esc_html_e('Enable Nudge', 'nudge'); ?>
                                            <?php $this->help(__('The master switch. When off, no bar renders anywhere and the bar assets are not loaded — zero front-end impact.', 'nudge')); ?>
                                        </th>
                                        <td>
                                            <label for="nudge_enabled">
                                                <input type="checkbox" id="nudge_enabled" name="<?php echo esc_attr(self::OPTION); ?>[enabled]" value="1" <?php checked((bool) ($settings['enabled'] ?? false), true); ?> />
                                                <?php esc_html_e('Show the free-shipping progress bar.', 'nudge'); ?>
                                            </label>
                                        </td>
                                    </tr>
                                    <?php
                                    $this->checkboxRow('show_on_cart', __('Cart page', 'nudge'), __('Show the bar on the cart.', 'nudge'), $settings, __('Displays the bar on the classic cart page and the Cart block.', 'nudge'));
                                    $this->checkboxRow('show_on_checkout', __('Checkout page', 'nudge'), __('Show the bar on checkout.', 'nudge'), $settings, __('Displays the bar on the classic checkout and the Checkout block.', 'nudge'));
                                    ?>
                                </tbody>
                            </table>
                            <p class="description">
                                <?php
                                printf(
                                    /* translators: %s: shortcode wrapped in <code>. */
                                    esc_html__('Want the bar elsewhere? Drop %s into any page, post or widget.', 'nudge'),
                                    '<code>[nudge_bar]</code>',
                                );
                                ?>
                            </p>
                        </div>

                        <div class="nudge-card">
                            <h2><?php esc_html_e('Free-shipping threshold', 'nudge'); ?></h2>
                            <table class="form-table" role="presentation">
                                <tbody>
                                    <tr>
                                        <th scope="row">
                                            <label for="nudge_threshold_source"><?php esc_html_e('Threshold source', 'nudge'); ?></label>
                                            <?php $this->help(__('Auto reads the minimum order amount from your WooCommerce free-shipping method (the smallest one across your shipping zones). Manual lets you set a fixed amount instead. In Auto mode the manual amount is used as a fallback when no free-shipping method is configured.', 'nudge')); ?>
                                        </th>
                                        <td>
                                            <select id="nudge_threshold_source" name="<?php echo esc_attr(self::OPTION); ?>[threshold_source]">
                                                <?php
                                                $current      = (string) ($settings['threshold_source'] ?? 'auto');
                                                $sourceLabels = [
                                                    'auto'   => __('Automatic (from free-shipping method)', 'nudge'),
                                                    'manual' => __('Manual (fixed amount)', 'nudge'),
                                                ];
                                                foreach (self::SOURCES as $source) :
                                                    ?>
                                                    <option value="<?php echo esc_attr($source); ?>" <?php selected($current, $source); ?>>
                                                        <?php echo esc_html($sourceLabels[$source] ?? $source); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="nudge_manual_threshold"><?php esc_html_e('Manual amount', 'nudge'); ?></label>
                                            <?php $this->help(__('The free-shipping goal used in Manual mode, and as the fallback in Auto mode. Enter a number in your store currency.', 'nudge')); ?>
                                        </th>
                                        <td>
                                            <input type="number" min="0" step="0.01" id="nudge_manual_threshold" name="<?php echo esc_attr(self::OPTION); ?>[manual_threshold]" value="<?php echo esc_attr((string) ($settings['manual_threshold'] ?? 50)); ?>" class="regular-text" />
                                            <p class="description"><?php esc_html_e('In your store currency, before shipping and taxes.', 'nudge'); ?></p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="nudge-card">
                            <h2><?php esc_html_e('Messages', 'nudge'); ?></h2>
                            <p class="description">
                                <?php
                                printf(
                                    /* translators: %s: the {amount} token wrapped in <code>. */
                                    esc_html__('Use %s in the progress message — it is replaced with the remaining amount, formatted in your store currency.', 'nudge'),
                                    '<code>{amount}</code>',
                                );
                                ?>
                            </p>
                            <table class="form-table" role="presentation">
                                <tbody>
                                    <tr>
                                        <th scope="row">
                                            <label for="nudge_message_progress"><?php esc_html_e('Progress message', 'nudge'); ?></label>
                                            <?php $this->help(__('Shown while the customer has not yet reached the goal. Include {amount} to show how much more they need.', 'nudge')); ?>
                                        </th>
                                        <td>
                                            <input type="text" id="nudge_message_progress" name="<?php echo esc_attr(self::OPTION); ?>[message_progress]" value="<?php echo esc_attr((string) ($settings['message_progress'] ?? '')); ?>" class="large-text" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="nudge_message_success"><?php esc_html_e('Success message', 'nudge'); ?></label>
                                            <?php $this->help(__('Shown once the customer has reached the free-shipping goal.', 'nudge')); ?>
                                        </th>
                                        <td>
                                            <input type="text" id="nudge_message_success" name="<?php echo esc_attr(self::OPTION); ?>[message_success]" value="<?php echo esc_attr((string) ($settings['message_success'] ?? '')); ?>" class="large-text" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="nudge-card">
                            <h2><?php esc_html_e('Appearance', 'nudge'); ?></h2>
                            <table class="form-table" role="presentation">
                                <tbody>
                                    <?php
                                    $this->colorRow('bar_color', __('Bar colour', 'nudge'), $settings, '#2271b1', __('The fill colour of the progress bar while the goal is in progress.', 'nudge'));
                                    $this->colorRow('bar_bg_color', __('Track colour', 'nudge'), $settings, '#e2e4e7', __('The colour of the empty track behind the fill.', 'nudge'));
                                    $this->colorRow('success_color', __('Success colour', 'nudge'), $settings, '#1a7f37', __('The fill colour once free shipping is unlocked.', 'nudge'));
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <?php submit_button(); ?>
                    </div>

                    <?php $this->renderPreviewPanel($settings); ?>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Sticky live-preview panel. JS keeps it in sync; without JS it shows the
     * saved state so the panel is never blank.
     *
     * @param array<string, mixed> $settings
     */
    private function renderPreviewPanel(array $settings): void
    {
        $style = sprintf(
            '--nudge-fill:%1$s;--nudge-track:%2$s;--nudge-success:%3$s;',
            esc_attr(sanitize_hex_color((string) ($settings['bar_color'] ?? '')) ?: '#2271b1'),
            esc_attr(sanitize_hex_color((string) ($settings['bar_bg_color'] ?? '')) ?: '#e2e4e7'),
            esc_attr(sanitize_hex_color((string) ($settings['success_color'] ?? '')) ?: '#1a7f37'),
        );
        ?>
        <aside class="nudge-card nudge-preview" aria-label="<?php esc_attr_e('Progress bar preview', 'nudge'); ?>">
            <h2><?php esc_html_e('Live preview', 'nudge'); ?></h2>
            <p class="nudge-preview__hint"><?php esc_html_e('A sample of how your bar will look.', 'nudge'); ?></p>
            <div class="nudge nudge--preview" data-nudge-preview style="<?php echo esc_attr($style); ?>">
                <p class="nudge__message" data-nudge-preview-message>
                    <?php echo esc_html(str_replace('{amount}', '$15.00', (string) ($settings['message_progress'] ?? ''))); ?>
                </p>
                <div class="nudge__track" role="presentation">
                    <span class="nudge__fill" data-nudge-preview-fill style="width:65%;"></span>
                </div>
            </div>
        </aside>
        <?php
    }

    /**
     * Render an accessible inline-help "?" popover (native Popover API with a
     * scripted fallback supplied by assets/js/admin.js).
     */
    private function help(string $text): void
    {
        $id = 'nudge-help-' . (++$this->helpSeq);
        ?>
        <button type="button" class="nudge-help" aria-label="<?php esc_attr_e('More information', 'nudge'); ?>" aria-describedby="<?php echo esc_attr($id); ?>" aria-expanded="false" popovertarget="<?php echo esc_attr($id); ?>">?</button>
        <div id="<?php echo esc_attr($id); ?>" class="nudge-tip" role="tooltip" popover hidden>
            <?php echo esc_html($text); ?>
        </div>
        <?php
    }

    /**
     * Render a single checkbox row.
     *
     * @param array<string, mixed> $settings
     */
    private function checkboxRow(string $key, string $label, string $help, array $settings, string $tip = ''): void
    {
        $id = 'nudge_' . $key;
        ?>
        <tr>
            <th scope="row">
                <?php echo esc_html($label); ?>
                <?php if ($tip !== '') { $this->help($tip); } ?>
            </th>
            <td>
                <label for="<?php echo esc_attr($id); ?>">
                    <input type="checkbox" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr(self::OPTION); ?>[<?php echo esc_attr($key); ?>]" value="1" <?php checked((bool) ($settings[$key] ?? false), true); ?> />
                    <?php echo esc_html($help); ?>
                </label>
            </td>
        </tr>
        <?php
    }

    /**
     * Render a colour-picker row.
     *
     * @param array<string, mixed> $settings
     */
    private function colorRow(string $key, string $label, array $settings, string $default, string $tip = ''): void
    {
        $id    = 'nudge_' . $key;
        $value = sanitize_hex_color((string) ($settings[$key] ?? '')) ?: $default;
        ?>
        <tr>
            <th scope="row">
                <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label>
                <?php if ($tip !== '') { $this->help($tip); } ?>
            </th>
            <td>
                <input type="color" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr(self::OPTION); ?>[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($value); ?>" />
            </td>
        </tr>
        <?php
    }

    /**
     * Sanitises, validates and clamps the submitted settings before save.
     *
     * @param mixed $raw
     * @return array<string, mixed>
     */
    public function sanitize(mixed $raw): array
    {
        if (! is_array($raw)) {
            $raw = [];
        }

        $source = isset($raw['threshold_source']) ? sanitize_key((string) $raw['threshold_source']) : 'auto';
        if (! in_array($source, self::SOURCES, true)) {
            $source = 'auto';
        }

        $sanitized = [
            'enabled'          => ! empty($raw['enabled']),
            'show_on_cart'     => ! empty($raw['show_on_cart']),
            'show_on_checkout' => ! empty($raw['show_on_checkout']),

            'threshold_source' => $source,
            'manual_threshold' => max(0.0, (float) wc_format_decimal((string) ($raw['manual_threshold'] ?? '0'))),

            'message_progress' => $this->text($raw, 'message_progress'),
            'message_success'  => $this->text($raw, 'message_success'),

            'bar_color'     => sanitize_hex_color((string) ($raw['bar_color'] ?? '')) ?: '#2271b1',
            'bar_bg_color'  => sanitize_hex_color((string) ($raw['bar_bg_color'] ?? '')) ?: '#e2e4e7',
            'success_color' => sanitize_hex_color((string) ($raw['success_color'] ?? '')) ?: '#1a7f37',
        ];

        return (array) apply_filters('nudge_sanitize_settings', $sanitized, $raw);
    }

    /**
     * Sanitise a single text field, preserving the {amount} token.
     *
     * @param array<string, mixed> $raw
     */
    private function text(array $raw, string $key): string
    {
        return isset($raw[$key]) ? sanitize_text_field((string) $raw[$key]) : '';
    }

    /**
     * Stored settings merged over packaged defaults.
     *
     * @return array<string, mixed>
     */
    private function settings(): array
    {
        $stored = get_option(self::OPTION, []);

        if (! is_array($stored)) {
            $stored = [];
        }

        /** @var array<string, mixed> $defaults */
        $defaults = require NUDGE_DIR . 'config/defaults.php';

        return array_merge($defaults, $stored);
    }
}
