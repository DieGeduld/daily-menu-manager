<?php

namespace DailyMenuManager;

/**
 * Plugin Name: Daily Menu Manager
 * Plugin URI: https://unkonventionell/daily-menu-manager
 * Description: Manage daily menus and their orders efficiently.
 * Version: 1.7.0
 * Author: Fabian Wolf
 * Author URI: https://unkonventionell
 * Text Domain: daily-menu-manager
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit('Direct access not allowed!');
}
if (!defined('DMM_VERSION')) {
    define('DMM_VERSION', '1.7.0');
}
if (!defined('DMM_PLUGIN_DIR')) {
    define('DMM_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('DMM_PLUGIN_URL')) {
    define('DMM_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('DMM_PLUGIN_FILE')) {
    define('DMM_PLUGIN_FILE', __FILE__);
}
if (!defined('DMM_PLUGIN_BASENAME')) {
    define('DMM_PLUGIN_BASENAME', plugin_basename(__FILE__));
}
if (!defined('DMM_TEXT_DOMAIN')) {
    define('DMM_TEXT_DOMAIN', 'daily-menu-manager');
}

// Load Bootstrap
require_once __DIR__ . '/includes/Bootstrap.php';

// Initialize plugin
Bootstrap::init();
