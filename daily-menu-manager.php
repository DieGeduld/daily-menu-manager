<?php
/**
 * Plugin Name: Daily Menu Manager
 * Plugin URI: https://yourwebsite.com/daily-menu-manager
 * Description: Manage daily menus and their orders efficiently. Perfect for restaurants and cafes offering daily changing menus.
 * Version: 1.1
 * Author: Fabian Wolf
 * Author URI: https://yourwebsite.com
 * Text Domain: daily-menu-manager
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace DailyMenuManager;

// Sicherheitscheck: Direkten Zugriff verhindern
defined('ABSPATH') or die('Direkter Zugriff nicht erlaubt!');

// Composer Autoloader (falls vorhanden)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Plugin Konstanten definieren
if (!defined('DMM_VERSION')) {
    define('DMM_VERSION', '1.1');
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

/**
 * Autoloader für Plugin-Klassen
 */
spl_autoload_register(function ($class) {
    // Nur Klassen in unserem Namespace behandeln
    if (strpos($class, 'DailyMenuManager\\') !== 0) {
        return;
    }

    // Klasse in Dateipfad umwandeln
    $path = DMM_PLUGIN_DIR . 'includes/';
    $file = str_replace(['DailyMenuManager\\', '\\'], ['', '/'], $class) . '.php';
    
    // Datei laden wenn sie existiert
    if (file_exists($path . $file)) {
        require_once $path . $file;
    }
});

/**
 * Plugin Aktivierung
 */
register_activation_hook(__FILE__, function() {
    // Systemanforderungen prüfen
    $requirements = Installer::checkSystemRequirements();
    if (is_array($requirements)) {
        // Aktivierung abbrechen wenn Anforderungen nicht erfüllt sind
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            implode('<br>', $requirements),
            'Plugin Activation Error',
            ['back_link' => true]
        );
    }
    
    // Plugin installieren
    Installer::activate();
});

/**
 * Plugin Deaktivierung
 */
function daily_menu_manager_deactivate() {
    Installer::deactivate();
}
register_deactivation_hook(__FILE__, 'DailyMenuManager\\daily_menu_manager_deactivate');

/**
 * Plugin Deinstallation
 */
function daily_menu_manager_uninstall() {
    Installer::uninstall();
}
register_uninstall_hook(__FILE__, 'daily_menu_manager_uninstall');


/**
 * Initialisierung nach der Plugin-Aktivierung
 */
add_action('plugins_loaded', function() {
    // Sprachdateien laden
    load_plugin_textdomain(
        'daily-menu-manager',
        false,
        dirname(DMM_PLUGIN_BASENAME) . '/languages/'
    );

    // Plugin initialisieren
    Plugin::getInstance();

    // Aktivierungs-Redirect
    if (get_transient('dmm_activation_redirect')) {
        delete_transient('dmm_activation_redirect');
        if (is_admin() && !isset($_GET['activate-multi'])) {
            wp_redirect(admin_url('admin.php?page=daily-menu-manager&welcome=1'));
            exit;
        }
    }
});

/**
 * Adds custom links below the plugin in the plugins list
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $plugin_links = [
        '<a href="' . admin_url('admin.php?page=daily-menu-manager') . '">' . 
            __('Einstellungen', 'daily-menu-manager') . '</a>'
    ];
    return array_merge($plugin_links, $links);
});

/**
 * Adds custom meta links in the plugins list
 */
add_filter('plugin_row_meta', function($links, $file) {
    if (plugin_basename(__FILE__) === $file) {
        $row_meta = [
            'docs' => '<a href="https://yourwebsite.com/docs/daily-menu-manager" target="_blank">' . 
                     __('Dokumentation', 'daily-menu-manager') . '</a>',
            'support' => '<a href="https://yourwebsite.com/support" target="_blank">' . 
                        __('Support', 'daily-menu-manager') . '</a>'
        ];
        return array_merge($links, $row_meta);
    }
    return $links;
}, 10, 2);

/**
 * Fügt Admin-Benachrichtigung hinzu wenn PHP-Version zu alt ist
 */
add_action('admin_notices', function() {
    if (version_compare(PHP_VERSION, '7.2', '<')) {
        $message = sprintf(
            __('Daily Menu Manager benötigt PHP 7.2 oder höher. Sie verwenden PHP %s. Bitte aktualisieren Sie PHP.', 'daily-menu-manager'),
            PHP_VERSION
        );
        echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
    }
});

// Optional: Debug-Modus aktivieren
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

MigrationManager::updateTo12();