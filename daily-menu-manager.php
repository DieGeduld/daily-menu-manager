<?php
namespace DailyMenuManager;

/**
 * Plugin Name: Daily Menu Manager
 * Plugin URI: https://yourwebsite.com/daily-menu-manager
 * Description: Manage daily menus and their orders efficiently.
 * Version: 1.4.0
 * Author: Fabian Wolf
 * Author URI: https://yourwebsite.com
 * Text Domain: daily-menu-manager
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access not allowed!');
}

if (!defined('DMM_VERSION')) {
    define('DMM_VERSION', '1.4.0');
}
if (!defined('DMM_PLUGIN_DIR')) {
    define('DMM_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('DMM_PLUGIN_FILE')) {
    define('DMM_PLUGIN_FILE', __FILE__);
}
if (!defined('DMM_PLUGIN_BASENAME')) {
    define('DMM_PLUGIN_BASENAME', plugin_basename(__FILE__));
}


// Load Bootstrap
require_once __DIR__ . '/includes/Bootstrap.php';

// Initialize plugin
Bootstrap::init();