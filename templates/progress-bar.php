<?php
/**
 * Free-shipping progress bar (storefront).
 *
 * Accessible: the track is a role="progressbar" with aria-valuenow/min/max, and
 * the human-readable message is a polite live region (role="status",
 * aria-live="polite", aria-atomic="true") so screen-reader users hear the new
 * sentence — e.g. "Add €8 more to get free shipping" — when WooCommerce
 * re-renders the bar after a cart change. It announces only the meaningful
 * message (the whole short sentence, atomically), never per-keystroke or
 * per-poll, because the text changes only when the cart total does. The fill
 * width is set inline so the bar is correct on first paint (no layout shift);
 * the bundled script animates subsequent updates. Colours are CSS custom
 * properties on the stylesheet, so themes can override them.
 *
 * @package Nudge
 *
 * @var string $context  Render context: cart|checkout|inline.
 * @var int    $percent  Progress towards the goal, 0–100.
 * @var bool   $reached  Whether the free-shipping goal is met.
 * @var string $message  Pre-built message HTML (may contain wc_price markup).
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are local to the template include scope, not true globals.

$context = isset($context) ? sanitize_html_class((string) $context) : 'cart';
$percent = isset($percent) ? max(0, min(100, (int) $percent)) : 0;
$reached = ! empty($reached);
$message = isset($message) ? (string) $message : '';

$wrapperClasses = 'nudge nudge--' . $context . ($reached ? ' is-complete' : '');
?>
<div class="<?php echo esc_attr($wrapperClasses); ?>" data-nudge>
    <p
        class="nudge__message"
        data-nudge-message
        role="status"
        aria-live="polite"
        aria-atomic="true"
    >
        <?php echo wp_kses_post($message); ?>
    </p>
    <div
        class="nudge__track"
        role="progressbar"
        aria-valuemin="0"
        aria-valuemax="100"
        aria-valuenow="<?php echo esc_attr((string) $percent); ?>"
        aria-label="<?php esc_attr_e('Progress towards free shipping', 'nudge'); ?>"
    >
        <span
            class="nudge__fill"
            data-nudge-fill
            style="width:<?php echo esc_attr((string) $percent); ?>%;"
        ></span>
    </div>
</div>
