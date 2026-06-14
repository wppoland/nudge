=== Nudge - Free Shipping Progress Bar for WooCommerce ===
Contributors: wppoland
Tags: woocommerce, free shipping, cart, progress bar, conversions
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Requires Plugins: woocommerce
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

An accessible free-shipping progress bar that nudges WooCommerce shoppers to add more to their cart and unlock free delivery.

== Description ==

Nudge shows your customers exactly how close they are to free shipping — and how
much more they need to add to unlock it. As the cart changes, the bar updates
live, turning "free shipping over $50" from fine print into a clear, motivating
goal that lifts average order value.

The bar reads your free-shipping goal automatically from your WooCommerce
free-shipping method's minimum order amount. No free-shipping method configured
yet? Set a fixed amount instead. When there is no goal to show, or the cart is
empty, Nudge hides itself rather than render a broken bar.

= Highlights =

* **Automatic threshold** — reads the minimum order amount from your active
  WooCommerce free-shipping method (the smallest one across your shipping zones),
  with a manual fallback.
* **Live updates** — the bar re-renders with the cart and animates smoothly,
  using a tiny dependency-free script (no jQuery of its own).
* **Cart and checkout** — shows on both, classic and Cart/Checkout Blocks, plus a
  `[nudge_bar]` shortcode for anywhere.
* **Accessible** — a real `role="progressbar"` with `aria-valuenow/min/max` and a
  readable text alternative; respects `prefers-reduced-motion`.
* **No layout shift** — the track reserves its height up front (zero CLS).
* **Themeable** — colours are CSS custom properties; dark-mode aware.
* **Customisable messages** — use the `{amount}` token in the progress message
  and a separate success message when the goal is reached.
* Translation ready (POT included), clean uninstall, HPOS + Blocks compatible.

= The [nudge_bar] shortcode =

Place the bar anywhere with `[nudge_bar]`. It renders the current cart's progress
toward free shipping and hides itself when the cart is empty or no goal is set.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/nudge`, or install via Plugins → Add New.
2. Activate it. WooCommerce must be active.
3. Go to **WooCommerce → Nudge**, enable the bar, and choose where it shows.

== Frequently Asked Questions ==

= Does it require WooCommerce? =

Yes.

= Where does the free-shipping amount come from? =

In Automatic mode, Nudge reads the minimum order amount from your enabled
WooCommerce free-shipping method (using the smallest amount across your shipping
zones). In Manual mode you set a fixed amount. If Automatic finds no qualifying
method, it falls back to the manual amount.

= What happens when no free-shipping goal is configured? =

Nudge hides the bar rather than showing a broken or always-complete one.

= Does it work with the Cart and Checkout blocks? =

Yes. It renders on both the classic templates and the WooCommerce Cart/Checkout
blocks, and declares HPOS and Cart/Checkout Blocks compatibility.

= Can I place the bar somewhere custom? =

Yes — use the `[nudge_bar]` shortcode in any page, post or widget.

== Screenshots ==

1. The free-shipping progress bar on the cart.
2. The Nudge settings screen with live preview.

== Changelog ==

= 0.1.0 =
* Initial release: accessible free-shipping progress bar for cart, checkout and the [nudge_bar] shortcode, with automatic or manual threshold, live updates, customisable messages and colours, dark-mode and reduced-motion support, and a settings screen with live preview.
