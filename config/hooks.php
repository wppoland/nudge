<?php
/**
 * Boot order: services listed here are resolved from the container and have
 * their registerHooks() called during Plugin::boot(). Each must implement
 * Nudge\Contract\HasHooks.
 *
 * @package Nudge
 *
 * @return array<class-string>
 */

declare(strict_types=1);

use Nudge\Admin\Settings;
use Nudge\Service\ProgressBarService;

defined('ABSPATH') || exit;

return is_admin()
    ? [
        ProgressBarService::class,
        Settings::class,
    ]
    : [
        ProgressBarService::class,
    ];
