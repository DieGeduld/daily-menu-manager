#!/bin/bash

# Plugin Basis-Verzeichnis
PLUGIN_NAME="daily-menu-manager"
BASE_DIR="./$PLUGIN_NAME"

# Erstelle Hauptverzeichnisse
echo "Creating directory structure for $PLUGIN_NAME..."

# Erstelle Verzeichnisse
mkdir -p "$BASE_DIR/includes/Models"
mkdir -p "$BASE_DIR/includes/Admin"
mkdir -p "$BASE_DIR/includes/Frontend"
mkdir -p "$BASE_DIR/includes/Views"
mkdir -p "$BASE_DIR/assets/css"
mkdir -p "$BASE_DIR/assets/js"
mkdir -p "$BASE_DIR/assets/img"

# Erstelle Dateien
# Hauptplugin-Datei
echo "<?php
/**
 * Plugin Name: Daily Menu Manager
 * Plugin URI: https://yourwebsite.com/daily-menu-manager
 * Description: Manage daily menus and their orders efficiently.
 * Version: 1.1
 * Author: Fabian Wolf
 */

namespace DailyMenuManager;

defined('ABSPATH') or die('Direct access not allowed!');

spl_autoload_register(function (\$class) {
    if (strpos(\$class, 'DailyMenuManager\\\\') !== 0) {
        return;
    }

    \$path = plugin_dir_path(__FILE__) . 'includes/';
    \$file = str_replace(['DailyMenuManager\\\\', '\\\\'], ['', '/'], \$class) . '.php';
    
    if (file_exists(\$path . \$file)) {
        require_once \$path . \$file;
    }
});

Plugin::getInstance();" > "$BASE_DIR/$PLUGIN_NAME.php"

# Plugin-Klasse
echo "<?php
namespace DailyMenuManager;

class Plugin {
    private static \$instance = null;
    
    public static function getInstance() {
        if (self::\$instance === null) {
            self::\$instance = new self();
        }
        return self::\$instance;
    }
    
    private function __construct() {
        Models\Menu::init();
        Admin\MenuController::init();
        Admin\OrderController::init();
        Frontend\ShortcodeController::init();
        
        register_activation_hook(__FILE__, [Installer::class, 'activate']);
        register_deactivation_hook(__FILE__, [Installer::class, 'deactivate']);
    }
}" > "$BASE_DIR/includes/Plugin.php"

# Installer
echo "<?php
namespace DailyMenuManager;

class Installer {
    public static function activate() {
        global \$wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        Models\Menu::createTables();
        update_option('daily_menu_manager_version', '1.1');
    }
    
    public static function deactivate() {
        global \$wpdb;
        \$wpdb->query(\"DROP TABLE IF EXISTS {\$wpdb->prefix}daily_menus\");
        \$wpdb->query(\"DROP TABLE IF EXISTS {\$wpdb->prefix}menu_items\");
        \$wpdb->query(\"DROP TABLE IF EXISTS {\$wpdb->prefix}menu_orders\");
    }
}" > "$BASE_DIR/includes/Installer.php"

# Menu Model
echo "<?php
namespace DailyMenuManager\Models;

class Menu {
    private static \$instance = null;
    
    public static function init() {
        if (self::\$instance === null) {
            self::\$instance = new self();
        }
        return self::\$instance;
    }
    
    public static function createTables() {
        global \$wpdb;
        \$charset_collate = \$wpdb->get_charset_collate();
        
        \$tables = [
            \"CREATE TABLE IF NOT EXISTS {\$wpdb->prefix}daily_menus (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                menu_date date NOT NULL,
                PRIMARY KEY  (id)
            ) \$charset_collate\",
            \"CREATE TABLE IF NOT EXISTS {\$wpdb->prefix}menu_items (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                menu_id mediumint(9) NOT NULL,
                item_type varchar(50) NOT NULL,
                title varchar(255) NOT NULL,
                description text,
                price decimal(10,2) NOT NULL,
                sort_order int NOT NULL,
                PRIMARY KEY  (id),
                KEY menu_id (menu_id)
            ) \$charset_collate\"
        ];
        
        foreach(\$tables as \$sql) {
            dbDelta(\$sql);
        }
    }
}" > "$BASE_DIR/includes/Models/Menu.php"

# Order Model
echo "<?php
namespace DailyMenuManager\Models;

class Order {
    public static function createOrder(\$data) {
        global \$wpdb;
        // Order creation logic
    }
    
    public static function getOrders(\$filters = []) {
        global \$wpdb;
        // Order retrieval logic
    }
}" > "$BASE_DIR/includes/Models/Order.php"

# MenuController
echo "<?php
namespace DailyMenuManager\Admin;

class MenuController {
    public static function init() {
        add_action('admin_menu', [self::class, 'addMenuPage']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAssets']);
    }
    
    public static function addMenuPage() {
        add_menu_page(
            'Daily Menu Manager',
            'Daily Menu',
            'manage_options',
            'daily-menu-manager',
            [self::class, 'renderMenuPage'],
            'dashicons-food'
        );
    }
}" > "$BASE_DIR/includes/Admin/MenuController.php"

# OrderController
echo "<?php
namespace DailyMenuManager\Admin;

class OrderController {
    public static function init() {
        add_action('admin_menu', [self::class, 'addSubMenuPage']);
    }
    
    public static function addSubMenuPage() {
        add_submenu_page(
            'daily-menu-manager',
            'Orders',
            'Orders',
            'manage_options',
            'daily-menu-orders',
            [self::class, 'renderOrdersPage']
        );
    }
}" > "$BASE_DIR/includes/Admin/OrderController.php"

# ShortcodeController
echo "<?php
namespace DailyMenuManager\Frontend;

class ShortcodeController {
    public static function init() {
        add_shortcode('daily_menu', [self::class, 'renderMenu']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueueAssets']);
    }
    
    public static function renderMenu(\$atts) {
        return 'Daily menu will be displayed here';
    }
}" > "$BASE_DIR/includes/Frontend/ShortcodeController.php"

# Leere Asset-Dateien erstellen
touch "$BASE_DIR/assets/css/admin.css"
touch "$BASE_DIR/assets/css/frontend.css"
touch "$BASE_DIR/assets/js/admin.js"
touch "$BASE_DIR/assets/js/frontend.js"

echo "Plugin structure created successfully!"
echo "Created directories:"
ls -R "$BASE_DIR"