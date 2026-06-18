=== Nudge - Free Shipping Progress Bar for WooCommerce ===
Contributors: motylanogha
Tags: woocommerce, free shipping, cart, progress bar, conversions
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Requires Plugins: woocommerce
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A free-shipping progress bar that tells WooCommerce shoppers how much more to add to qualify for free delivery.

== Description ==

Nudge shows shoppers how far their cart is from your free-shipping threshold and
how much more they need to add to reach it. The message updates as the cart
changes, so "free shipping over $50" stops being fine print and becomes a number
the customer can act on.

By default the threshold comes straight from WooCommerce. Nudge looks at your
enabled free-shipping methods across all shipping zones and uses the lowest
minimum order amount it finds, so you don't maintain the figure in two places. If
you'd rather set it yourself, switch to Manual mode and type a fixed amount; that
amount is also used as a fallback when Automatic mode finds no qualifying method.

When there's nothing useful to show (the bar is disabled, the cart is empty, or no
threshold is configured) Nudge renders nothing instead of an empty or
always-finished bar.

= What it does =

* Reads the free-shipping threshold automatically from your active WooCommerce
  free-shipping methods, or takes a fixed amount you set by hand.
* Re-renders with the cart on both the classic Cart/Checkout pages and the
  Cart/Checkout Blocks. A small script (no jQuery of its own) animates the width
  between updates.
* Exposes a real `role="progressbar"` with `aria-valuenow`/`min`/`max` and a
  readable text message for screen readers; honours `prefers-reduced-motion`.
* Reserves the bar's height before paint, so adding it doesn't shift the layout.
* Styles the bar with `--nudge-*` CSS custom properties and adapts to dark colour
  schemes, so themes can recolour it without editing markup.
* Lets you write the progress and success messages, with an `{amount}` token in
  the progress message for the remaining total.
* Ships a POT file, removes its options on uninstall, and declares HPOS and
  Cart/Checkout Blocks compatibility.

Source code and bug reports live on GitHub: https://github.com/wppoland/nudge

== Installation ==

1. Upload the plugin to `/wp-content/plugins/nudge`, or install via Plugins → Add New.
2. Activate it. WooCommerce must be active.
3. Go to **WooCommerce → Nudge**, enable the bar, and choose where it shows.

== Frequently Asked Questions ==

= Does it require WooCommerce? =

Yes. Nudge does nothing until WooCommerce is active.

= Where does the free-shipping amount come from? =

In Automatic mode, Nudge reads the minimum order amount from your enabled
WooCommerce free-shipping methods and uses the smallest one across your shipping
zones. In Manual mode you set a fixed amount yourself. If Automatic mode finds no
method with a minimum order amount, it uses the manual amount instead.

= What shows when no free-shipping goal is configured? =

Nothing. Rather than render an empty or always-complete bar, Nudge skips output
entirely until there's a real threshold to count down to.

= Does it work with the Cart and Checkout blocks? =

Yes. It renders on the classic Cart/Checkout templates and on the WooCommerce
Cart/Checkout blocks, and declares HPOS and Cart/Checkout Blocks compatibility.

= Can I change the wording and colours? =

Yes. The progress and success messages are editable on the settings screen, and
the bar's colours and sizing are `--nudge-*` CSS custom properties your theme can
override.

== Screenshots ==

1. The free-shipping progress bar on the cart.
2. The Nudge settings screen.

== External Services ==

Nudge does not connect to any external service. It does not send analytics, register a licence, load remote fonts or scripts, or make any HTTP request off your server. Everything it needs — your free-shipping threshold and cart totals — comes from WooCommerce on the same site, and the bar's stylesheet and small animation script are served from the plugin folder, not a CDN. The only data Nudge stores is two WordPress options on your own database (`nudge_settings` for your configuration and `nudge_db_version` for upgrades), both removed when you delete the plugin.

== Changelog ==

= 0.1.0 =
* First release: free-shipping progress bar for the cart and checkout, with an automatic or manual threshold, live updates as the cart changes, editable messages, dark-mode and reduced-motion support, and a settings screen under WooCommerce → Nudge.
</content>
</invoke>
