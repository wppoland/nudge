<?php
/**
 * Service wiring. Returns a closure that registers every service in the
 * container. Keep services thin and self-contained — Nudge has no external
 * runtime dependencies.
 *
 * @package Nudge
 */

declare(strict_types=1);

use Nudge\Admin\Settings;
use Nudge\Container;
use Nudge\Migrator;
use Nudge\Service\ThresholdResolver;
use Nudge\Service\ProgressBarService;

defined('ABSPATH') || exit;

return static function (Container $c): void {
    $c->singleton(Migrator::class, static fn (): Migrator => new Migrator());

    $c->singleton(ThresholdResolver::class, static fn (): ThresholdResolver => new ThresholdResolver());

    $c->singleton(ProgressBarService::class, static fn (): ProgressBarService => new ProgressBarService(
        $c->get(ThresholdResolver::class),
    ));

    // Admin (only needed in wp-admin context).
    if (is_admin()) {
        $c->singleton(Settings::class, static fn (): Settings => new Settings());
    }
};
