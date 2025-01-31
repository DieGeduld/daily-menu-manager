<?php
namespace DailyMenuManager;

class Plugin {
    /**
     * Plugin Version
     */
    const VERSION = '1.2';  // Version erhöht für das Update

    /**
     * Plugin Instance
     */
    private static $instance = null;

    /**
     * Menü-Typen Konfiguration
     */
    private $menu_types = [
        'appetizer' => [
            'label' => 'Appetizer',
            'label_de' => 'Vorspeise'
        ],
        'main_course' => [
            'label' => 'Main Course',
            'label_de' => 'Hauptgang'
        ],
        'dessert' => [
            'label' => 'Dessert',
            'label_de' => 'Nachspeise'
        ]
    ];

    /**
     * Singleton Pattern: Gibt die Plugin-Instanz zurück
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Konstruktor: Initialisiert das Plugin
     */
    private function __construct() {
        $this->defineConstants();
        $this->initHooks();
        $this->loadTextdomain();
        $this->initializeComponents();
    }

    /**
     * Definiert Plugin-Konstanten
     */
    private function defineConstants() {
        if (!defined('DMM_VERSION')) {
            define('DMM_VERSION', self::VERSION);
        }
        if (!defined('DMM_PLUGIN_DIR')) {
            define('DMM_PLUGIN_DIR', plugin_dir_path(dirname(__FILE__)));
        }
        if (!defined('DMM_PLUGIN_URL')) {
            define('DMM_PLUGIN_URL', plugin_dir_url(dirname(__FILE__)));
        }
    }

    /**
     * Registriert WordPress Hooks
     */
    private function initHooks() {
        // Aktivierung/Deaktivierung
        register_activation_hook(DMM_PLUGIN_DIR . 'daily-menu-manager.php', [Installer::class, 'activate']);
        register_deactivation_hook(DMM_PLUGIN_DIR . 'daily-menu-manager.php', [Installer::class, 'deactivate']);

        // Plugin Update Check
        add_action('plugins_loaded', [$this, 'checkUpdate']);
        
        // Admin Notices
        add_action('admin_notices', [$this, 'adminNotices']);
    }

    /**
     * Lädt die Übersetzungsdateien
     */
    private function loadTextdomain() {
        load_plugin_textdomain(
            'daily-menu-manager',
            false,
            dirname(plugin_basename(DMM_PLUGIN_DIR)) . '/languages/'
        );
    }

    /**
     * Initialisiert alle Plugin-Komponenten
     */
    private function initializeComponents() {
        // Initialisiere Models
        Models\Menu::init();
        Models\Order::init();
        
        // Admin-Bereich initialisieren wenn im Backend
        if (is_admin()) {
            $this->initAdminComponents();
        }
        
        // Frontend-Komponenten initialisieren
        $this->initFrontendComponents();
        
        // AJAX Handler registrieren
        $this->registerAjaxHandlers();
    }

    /**
     * Initialisiert Admin-Komponenten
     */
    private function initAdminComponents() {
        Admin\MenuController::init();
        Admin\OrderController::init();
    }

    /**
     * Initialisiert Frontend-Komponenten
     */
    private function initFrontendComponents() {
        Frontend\ShortcodeController::init();
    }

    /**
     * Registriert AJAX Handler
     */
    private function registerAjaxHandlers() {
        // Bestellungen
        add_action('wp_ajax_submit_order', [Admin\OrderController::class, 'handleOrder']);
        add_action('wp_ajax_nopriv_submit_order', [Admin\OrderController::class, 'handleOrder']);
        
        // Admin AJAX Handler
        if (is_admin()) {
            add_action('wp_ajax_save_menu_order', [Admin\MenuController::class, 'handleSaveMenuOrder']);
            add_action('wp_ajax_copy_menu', [Admin\MenuController::class, 'handleCopyMenu']);
            add_action('wp_ajax_print_order', [Admin\OrderController::class, 'handlePrintOrder']);
            add_action('wp_ajax_delete_order', [Admin\OrderController::class, 'handleDeleteOrder']);
        }
    }

    /**
     * Prüft auf Plugin-Updates und führt sie aus
     */
    public function checkUpdate() {
        $installed_version = get_option('daily_menu_manager_version');
        
        if ($installed_version !== self::VERSION) {
            // Führe Update-Routinen aus
            $this->runUpdates($installed_version);
            
            // Aktualisiere die Version in der Datenbank
            update_option('daily_menu_manager_version', self::VERSION);
        }
    }

    /**
     * Führt Update-Routinen aus
     */
    private function runUpdates($old_version) {
        // Update auf Version 1.1
        if (version_compare($old_version, '1.1', '<')) {
            $this->updateTo11();
        }
        
        // Update auf Version 1.2
        if (version_compare($old_version, '1.2', '<')) {
            $this->updateTo12();
        }
    }

    /**
     * Update-Routine für Version 1.1
     */
    private function updateTo11() {
        MigrationManager::updateTo11();
        
        self::log('Ran migrations for version 1.1');
    }

    /**
     * Update-Routine für Version 1.2
     */
    private function updateTo12() {
        MigrationManager::updateTo12();
        
        self::log('Ran migrations for version 1.2');
    }

    /**
     * Zeigt Admin-Benachrichtigungen
     */
    public function adminNotices() {
        // Zeige Update-Benachrichtigungen
        if ($notices = get_option('daily_menu_manager_notices')) {
            foreach ($notices as $notice) {
                echo '<div class="notice notice-' . esc_attr($notice['type']) . ' is-dismissible"><p>' . 
                     esc_html($notice['message']) . '</p></div>';
            }
            delete_option('daily_menu_manager_notices');
        }
    }

    /**
     * Getter für Menu Types
     */
    public function getMenuTypes() {
        return apply_filters('daily_menu_manager_menu_types', $this->menu_types);
    }

    /**
     * Fügt eine Admin-Benachrichtigung hinzu
     */
    public static function addAdminNotice($message, $type = 'success') {
        $notices = get_option('daily_menu_manager_notices', []);
        $notices[] = [
            'message' => $message,
            'type' => $type
        ];
        update_option('daily_menu_manager_notices', $notices);
    }

    /**
     * Hilfsmethode: Logger für Debugging
     */
    public static function log($message, $data = null) {
        if (WP_DEBUG) {
            error_log('Daily Menu Manager: ' . $message);
            if ($data !== null) {
                error_log(print_r($data, true));
            }
        }
    }
}