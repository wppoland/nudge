<?php
/**
 * Free-shipping progress bar (storefront).
 *
 * Accessible: the track is a role="progressbar" with aria-valuenow/min/max, and
 * the human-readable message is the text alternative announced to screen
 * readers. The fill width is set inline so the bar is correct on first paint
 * (no layout shift); the bundled script animates subsequent updates. Colours are
 * passed as CSS custom properties so themes can override them.
 *
 * @package Nudge
 *
 * @var string $context        Render context: cart|checkout|inline|shortcode.
 * @var int    $percent        Progress towards the goal, 0–100.
 * @var bool   $reached        Whether the free-shipping goal is met.
 * @var string $message        Pre-built message HTML (may contain wc_price markup).
 * @var string $bar_color      Fill colour (hex).
 * @var string $bar_bg_color   Track colour (hex).
 * @var string $success_color  Fill colour when the goal is reached (hex).
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are local to the template include scope, not true globals.

$context = isset($context) ? sanitize_html_class((string) $context) : 'cart';
$percent = isset($percent) ? max(0, min(100, (int) $percent)) : 0;
$reached = ! empty($reached);
$message = isset($message) ? (string) $message : '';

$style = sprintf(
    '--nudge-fill:%1$s;--nudge-track:%2$s;--nudge-success:%3$s;',
    esc_attr(sanitize_hex_color((string) ($bar_color ?? '')) ?: '#2271b1'),
    esc_attr(sanitize_hex_color((string) ($bar_bg_color ?? '')) ?: '#e2e4e7'),
    esc_attr(sanitize_hex_color((string) ($success_color ?? '')) ?: '#1a7f37'),
);

$wrapperClasses = 'nudge nudge--' . $context . ($reached ? ' is-complete' : '');
?>
<div class="<?php echo esc_attr($wrapperClasses); ?>" style="<?php echo esc_attr($style); ?>" data-nudge>
    <p class="nudge__message" data-nudge-message>
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
