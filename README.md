# Nudge — Free Shipping Progress Bar for WooCommerce

A free, wp.org-ready WooCommerce plugin that shows customers an accessible
progress bar toward free shipping — "Add {amount} more to get free shipping!" —
and a success state when the goal is met. Self-contained: no external runtime
dependencies.

## What it does

* Reads the free-shipping threshold automatically from your WooCommerce
  free-shipping method's minimum order amount (smallest across shipping zones),
  with a manual fixed-amount fallback.
* Renders on the cart and checkout (classic + Cart/Checkout Blocks) and via the
  `[nudge_bar]` shortcode.
* Updates live with the cart using a tiny dependency-free script (no jQuery of
  its own), animating smoothly and honouring `prefers-reduced-motion`.
* Accessible `role="progressbar"` with `aria-valuenow/min/max` and a readable
  text alternative; zero layout shift; dark-mode aware; themeable via CSS custom
  properties.
* Settings under **WooCommerce → Nudge**: enable, threshold source, messages
  (with the `{amount}` token), bar colours, and placement.

## Architecture

* `nudge.php` — bootstrap. Declares HPOS/Blocks compatibility, boots on `init:0`
  and fires `do_action('nudge/booted', Plugin::instance())` from `Plugin::boot()`.
* `src/Plugin.php` + `src/Container.php` — singleton + minimal DI container.
* `src/Service/ThresholdResolver.php` — resolves the free-shipping goal and cart
  progress.
* `src/Service/ProgressBarService.php` — hooks, asset enqueue, shortcode and
  rendering.
* `src/Admin/Settings.php` — settings screen with inline help and live preview.
* `templates/progress-bar.php` — the storefront markup.
* `config/{services,hooks,defaults}.php` — wiring and defaults.

## Development

```bash
composer install      # dev toolchain only (no runtime deps)
composer cs           # PHPCS (WordPress security/i18n subset)
composer analyse      # PHPStan level 6
```

CI runs PHP lint (8.1–8.3), PHPCS, PHPStan and the official WordPress Plugin
Check via the shared `wppoland/workflows` reusable workflow.

## PRO

Premium features (per-zone thresholds, floating mini-cart bar, multiple reward
tiers, dismissible, custom templates) live in the separate **nudge-pro** add-on,
which boots on the `nudge/booted` action.
