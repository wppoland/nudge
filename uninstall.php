<?php
/**
 * Uninstall cleanup for Nudge.
 *
 * Runs when the plugin is deleted from wp-admin. Removes the options Nudge
 * creates. No custom tables or post meta are created by Nudge.
 *
 * @package Nudge
 */

declare(strict_types=1);

defined('WP_UNINSTALL_PLUGIN') || exit;

delete_option('nudge_settings');
delete_option('nudge_db_version');
