<?php
/**
 * Constants needed by PHPStan to analyse the plugin without bootstrapping WordPress.
 *
 * @package Nudge
 */

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) {
        define('ABSPATH', '/tmp/wordpress/');
    }
    if (! defined('NUDGE_DIR')) {
        define('NUDGE_DIR', '/tmp/nudge/');
    }
    if (! defined('NUDGE_URL')) {
        define('NUDGE_URL', 'https://example.test/wp-content/plugins/nudge/');
    }
}

namespace Nudge {
    if (! defined('Nudge\\VERSION')) {
        define('Nudge\\VERSION', '0.1.0');
    }
    if (! defined('Nudge\\PLUGIN_FILE')) {
        define('Nudge\\PLUGIN_FILE', '/tmp/nudge/nudge.php');
    }
}
