<?php

declare(strict_types=1);

namespace Nudge\Admin;

defined('ABSPATH') || exit;

use Nudge\Contract\HasHooks;

/**
 * Admin settings page registered as a WooCommerce submenu ("WooCommerce →
 * Nudge").
 *
 * Stores settings in the `nudge_settings` option (array): enable, where the bar
 * shows, the threshold source (auto vs manual) and the manual amount, and the
 * progress/success messages (with the {amount} token). All output is escaped;
 * all input is sanitised and clamped on save.
 */
final class Settings implements HasHooks
{
    private const OPTION = 'nudge_settings';
    private const PAGE   = 'nudge-settings';

    private const SOURCES = ['auto', 'manual'];

    /** Settings page hook suffix, captured so we can scope assets to it. */
    private string $hookSuffix = '';

    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function addMenuPage(): void
    {
        $hook = add_submenu_page(
            'woocommerce',
            __('Nudge — Free Shipping Bar', 'nudge'),
            __('Nudge', 'nudge'),
            'manage_woocommerce',
            self::PAGE,
            [$this, 'renderPage'],
        );

        $this->hookSuffix = is_string($hook) ? $hook : '';
    }

    /**
     * Load the admin stylesheet only on the Nudge settings screen — never
     * across wp-admin.
     */
    public function enqueueAssets(string $hookSuffix): void
    {
        if ('' === $this->hookSuffix || $hookSuffix !== $this->hookSuffix) {
            return;
        }

        wp_enqueue_style(
            'nudge-admin',
            NUDGE_URL . 'assets/css/admin.css',
            [],
            \Nudge\VERSION,
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

        /** @var array<string, mixed> $defaults */
        $defaults     = require NUDGE_DIR . 'config/defaults.php';
        $progressCopy = (string) ($settings['message_progress'] ?? '');
        $successCopy  = (string) ($settings['message_success'] ?? '');
        ?>
        <div class="wrap nudge-settings">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <p class="nudge-settings__lead">
                <?php esc_html_e('Nudge shows customers how close they are to free shipping and exactly how much more to add to unlock it. It updates live as the cart changes. The defaults work out of the box — adjust below only if you want to.', 'nudge'); ?>
            </p>

            <form method="post" action="options.php">
                <?php settings_fields(self::PAGE); ?>

                <section class="nudge-section">
                    <h2 class="nudge-section__title"><?php esc_html_e('General', 'nudge'); ?></h2>
                    <p class="nudge-section__intro"><?php esc_html_e('Turn the bar on and choose where shoppers see it. Showing it on both cart and checkout keeps the goal in front of them through the whole purchase.', 'nudge'); ?></p>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><?php esc_html_e('Enable Nudge', 'nudge'); ?></th>
                                <td>
                                    <label for="nudge_enabled">
                                        <input type="checkbox" id="nudge_enabled" name="<?php echo esc_attr(self::OPTION); ?>[enabled]" value="1" <?php checked((bool) ($settings['enabled'] ?? false), true); ?> />
                                        <?php esc_html_e('Show the free-shipping progress bar to shoppers.', 'nudge'); ?>
                                    </label>
                                    <p class="description"><?php esc_html_e('When off, the bar and its assets never load — no styles or scripts are added to the front end.', 'nudge'); ?></p>
                                </td>
                            </tr>
                            <?php
                            $this->checkboxRow('show_on_cart', __('Cart page', 'nudge'), __('Show the bar on the cart page, above the totals.', 'nudge'), $settings);
                            $this->checkboxRow('show_on_checkout', __('Checkout page', 'nudge'), __('Show the bar on checkout, so the goal stays visible while they pay.', 'nudge'), $settings);
                            ?>
                        </tbody>
                    </table>
                </section>

                <section class="nudge-section">
                    <h2 class="nudge-section__title"><?php esc_html_e('Free-shipping threshold', 'nudge'); ?></h2>
                    <p class="nudge-section__intro"><?php esc_html_e('Where the “you reach free shipping at…” amount comes from. Automatic keeps the bar in sync with your real shipping rules, so it is never wrong after a price change.', 'nudge'); ?></p>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="nudge_threshold_source"><?php esc_html_e('Threshold source', 'nudge'); ?></label>
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
                                    <p class="description"><?php esc_html_e('Automatic reads the minimum order amount from your WooCommerce free-shipping method — the smallest one across your shipping zones — so the bar updates itself when you change that rule. The manual amount below is used as a fallback when no such method is found.', 'nudge'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="nudge_manual_threshold"><?php esc_html_e('Manual amount', 'nudge'); ?></label>
                                </th>
                                <td>
                                    <input type="number" min="0" step="0.01" id="nudge_manual_threshold" name="<?php echo esc_attr(self::OPTION); ?>[manual_threshold]" value="<?php echo esc_attr((string) ($settings['manual_threshold'] ?? 50)); ?>" class="regular-text" placeholder="<?php echo esc_attr((string) ($defaults['manual_threshold'] ?? 50)); ?>" />
                                    <p class="description"><?php esc_html_e('In your store currency, before shipping and taxes. Used when the source is Manual, or as the fallback for Automatic. Default: 50.', 'nudge'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section class="nudge-section">
                    <h2 class="nudge-section__title"><?php esc_html_e('Messages', 'nudge'); ?></h2>
                    <p class="nudge-section__intro">
                        <?php
                        printf(
                            /* translators: %s: the {amount} token, shown as a styled chip. */
                            esc_html__('What the bar says before and after the goal is reached. Write %s where you want the remaining amount — it is replaced with the value formatted in your store currency (e.g. $12.00).', 'nudge'),
                            '<span class="nudge-token">{amount}</span>',
                        );
                        ?>
                    </p>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="nudge_message_progress"><?php esc_html_e('Progress message', 'nudge'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="nudge_message_progress" name="<?php echo esc_attr(self::OPTION); ?>[message_progress]" value="<?php echo esc_attr($progressCopy); ?>" class="large-text" placeholder="<?php echo esc_attr((string) ($defaults['message_progress'] ?? '')); ?>" />
                                    <p class="nudge-preview">
                                        <span class="nudge-preview__label"><?php esc_html_e('Shoppers see:', 'nudge'); ?></span>
                                        <?php echo esc_html(str_replace('{amount}', '$12.00', '' !== $progressCopy ? $progressCopy : (string) ($defaults['message_progress'] ?? ''))); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="nudge_message_success"><?php esc_html_e('Success message', 'nudge'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="nudge_message_success" name="<?php echo esc_attr(self::OPTION); ?>[message_success]" value="<?php echo esc_attr($successCopy); ?>" class="large-text" placeholder="<?php echo esc_attr((string) ($defaults['message_success'] ?? '')); ?>" />
                                    <p class="nudge-preview">
                                        <span class="nudge-preview__label"><?php esc_html_e('Shoppers see:', 'nudge'); ?></span>
                                        <?php echo esc_html('' !== $successCopy ? $successCopy : (string) ($defaults['message_success'] ?? '')); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render a single checkbox row.
     *
     * @param array<string, mixed> $settings
     */
    private function checkboxRow(string $key, string $label, string $help, array $settings): void
    {
        $id = 'nudge_' . $key;
        ?>
        <tr>
            <th scope="row"><?php echo esc_html($label); ?></th>
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

        return [
            'enabled'          => ! empty($raw['enabled']),
            'show_on_cart'     => ! empty($raw['show_on_cart']),
            'show_on_checkout' => ! empty($raw['show_on_checkout']),

            'threshold_source' => $source,
            'manual_threshold' => max(0.0, (float) wc_format_decimal((string) ($raw['manual_threshold'] ?? '0'))),

            'message_progress' => $this->text($raw, 'message_progress'),
            'message_success'  => $this->text($raw, 'message_success'),
        ];
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
