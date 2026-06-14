<?php
/**
 * Autoloading: prefer Composer's vendor autoloader (the optimized classmap).
 * Fall back to a minimal PSR-4 autoloader so the plugin still boots if vendor/
 * is somehow absent. Nudge is self-contained — it has no runtime Composer
 * dependencies, so the fallback alone is sufficient.
 *
 * @package Nudge
 */

declare(strict_types=1);

namespace Nudge;

defined('ABSPATH') || exit;

$nudge_composer = __DIR__ . '/vendor/autoload.php';
if (is_readable($nudge_composer)) {
    require_once $nudge_composer;
    return;
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'Nudge\\';
    $len    = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative = substr($class, $len);
    $file     = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';

    if (is_readable($file)) {
        require_once $file;
    }
});
