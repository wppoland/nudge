# Nudge

Nudge shows your WooCommerce shoppers an accessible progress bar toward free shipping — "Add {amount} more to get free shipping!" — and a success message once they qualify, turning fine print into a clear incentive to add more to the cart.

## Features

- Reads the free-shipping threshold automatically from your WooCommerce free-shipping method (the smallest minimum order amount across shipping zones), with a manual fixed-amount fallback.
- Shows on the cart and checkout, both classic templates and the Cart/Checkout blocks, plus a `[nudge_bar]` shortcode for anywhere.
- Updates live as the cart changes, animating smoothly and honouring `prefers-reduced-motion`. No jQuery of its own.
- Accessible `role="progressbar"` with a readable text alternative and zero layout shift; dark-mode aware and themeable via CSS custom properties.
- Settings under WooCommerce → Nudge: enable, threshold source, messages (with the `{amount}` token), colours and placement.

## Installation

1. Upload the plugin to `/wp-content/plugins/nudge`, or install it from Plugins → Add New.
2. Activate it. WooCommerce must be active.
3. Go to WooCommerce → Nudge, enable the bar, and choose where it appears.

## Frequently Asked Questions

**Does it require WooCommerce?**
Yes.

**What happens when no free-shipping goal is configured?**
Nudge hides the bar rather than showing a broken or always-complete one.

Built by WPPoland — https://plogins.com

License: GPL-2.0-or-later
