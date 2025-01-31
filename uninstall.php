<?php
// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load the Installer class if not already loaded
require_once plugin_dir_path(__FILE__) . 'includes/Installer.php';

// Call the uninstall method
DailyMenuManager\Installer::uninstall();