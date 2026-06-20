<?php

declare(strict_types=1);

namespace Nudge\Service;

defined('ABSPATH') || exit;

use Nudge\Contract\HasHooks;

/**
 * Renders the free-shipping progress bar on the cart and checkout.
 *
 * The bar is computed server-side and re-rendered by WooCommerce whenever the
 * cart totals update (it lives inside the cart/checkout totals fragment). A
 * tiny, dependency-free script listens for WooCommerce's `updated_cart_totals`
 * and `updated_checkout` events to animate the width smoothly between renders.
 *
 * Renders on the cart and checkout (classic templates and the Cart/Checkout
 * Blocks).
 *
 * Robustness: when the feature is disabled, the cart is empty, or no
 * free-shipping threshold is configured, the bar is hidden rather than rendered
 * in a broken or always-complete state.
 */
final class ProgressBarService implements HasHooks
{
    private const OPTION = 'nudge_settings';

    private const ASSET_HANDLE = 'nudge';

    private ThresholdResolver $resolver;

    /** Guards against enqueueing the stylesheet more than once per request. */
    private bool $assetsEnqueued = false;

    public function __construct(ThresholdResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function registerHooks(): void
    {
        $settings = $this->settings();

        if (empty($settings['enabled'])) {
            return;
        }

        add_action('wp_enqueue_scripts', [$this, 'registerAssets']);

        if (! empty($settings['show_on_cart'])) {
            // Renders above the cart totals on the classic cart page.
            add_action('woocommerce_before_cart_totals', [$this, 'renderCartBar']);
            // Cart/Checkout Blocks: a neutral hook that both blocks honour.
            add_action('woocommerce_cart_totals_after_order_total', [$this, 'renderInlineBar']);
        }

        if (! empty($settings['show_on_checkout'])) {
            add_action('woocommerce_before_checkout_form', [$this, 'renderCheckoutBar'], 5);
            add_action('woocommerce_review_order_after_order_total', [$this, 'renderInlineBar']);
        }
    }

    /**
     * Register the stylesheet and the small progressive-enhancement script.
     * Only registered here; actually enqueued lazily when the bar renders, so a
     * page without a bar never loads the assets.
     */
    public function registerAssets(): void
    {
        wp_register_style(
            self::ASSET_HANDLE,
            NUDGE_URL . 'assets/css/nudge.css',
            [],
            \Nudge\VERSION,
        );

        wp_register_script(
            self::ASSET_HANDLE,
            NUDGE_URL . 'assets/js/nudge.js',
            [],
            \Nudge\VERSION,
            ['in_footer' => true, 'strategy' => 'defer'],
        );
    }

    private function enqueueAssets(): void
    {
        if ($this->assetsEnqueued) {
            return;
        }

        // registerAssets() runs on wp_enqueue_scripts; if a render fires before
        // that (unlikely), register on demand so enqueue still succeeds.
        if (! wp_style_is(self::ASSET_HANDLE, 'registered')) {
            $this->registerAssets();
        }

        wp_enqueue_style(self::ASSET_HANDLE);
        wp_enqueue_script(self::ASSET_HANDLE);

        $this->assetsEnqueued = true;
    }

    /**
     * Render wrapper for the classic cart page (adds outer spacing class).
     */
    public function renderCartBar(): void
    {
        echo $this->buildBar('cart'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- buildBar() returns fully-escaped, self-rendered template markup.
    }

    /**
     * Render wrapper for the checkout page.
     */
    public function renderCheckoutBar(): void
    {
        echo $this->buildBar('checkout'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- buildBar() returns fully-escaped, self-rendered template markup.
    }

    /**
     * Render wrapper for inline (inside-totals) placements.
     */
    public function renderInlineBar(): void
    {
        echo $this->buildBar('inline'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- buildBar() returns fully-escaped, self-rendered template markup.
    }

    /**
     * Build the bar markup for a given context, or an empty string when there
     * is nothing meaningful to show.
     */
    private function buildBar(string $context): string
    {
        // Cart must be available and not empty.
        if (! function_exists('WC') || ! WC()->cart instanceof \WC_Cart || WC()->cart->is_empty()) {
            return '';
        }

        $settings  = $this->settings();
        $threshold = $this->resolver->threshold($settings);

        // No configured free-shipping goal — hide rather than show a broken bar.
        if ($threshold <= 0.0) {
            return '';
        }

        $total     = $this->resolver->cartTotal();
        $remaining = max(0.0, $threshold - $total);
        $reached   = $remaining <= 0.0;
        $percent   = $threshold > 0.0 ? min(100, (int) round(($total / $threshold) * 100)) : 0;

        $remainingHtml = wc_price($remaining);
        $message       = $reached
            ? (string) ($settings['message_success'] ?? '')
            : str_replace('{amount}', $remainingHtml, (string) ($settings['message_progress'] ?? ''));

        $this->enqueueAssets();

        /** @var array{context:string,percent:int,reached:bool,message:string,threshold:float,total:float,remaining:float,tier_markers?:int[]} $barContext */
        $barContext = apply_filters('nudge/bar_context', [
            'context'   => $context,
            'percent'   => $percent,
            'reached'   => $reached,
            'message'   => $message,
            'threshold' => $threshold,
            'total'     => $total,
            'remaining' => $remaining,
        ], $settings);

        /**
         * Fires after the bar context is resolved and before the template renders.
         *
         * @param array<string, mixed> $barContext Progress data for the bar.
         * @param array<string, mixed> $settings   Merged nudge settings.
         */
        do_action('nudge/bar_rendered', $barContext, $settings);

        ob_start();
        $this->renderTemplate('progress-bar', $barContext);

        return (string) ob_get_clean();
    }

    /**
     * @param array<string, mixed> $context
     */
    private function renderTemplate(string $template, array $context): void
    {
        $file = NUDGE_DIR . 'templates/' . $template . '.php';

        if (! is_readable($file)) {
            return;
        }

        extract($context, EXTR_SKIP);
        require $file;
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
