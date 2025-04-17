<?php
namespace DailyMenuManager\Controllers\Frontend;

/**
 * Verwaltet das Template-Rendering im Frontend
 */
class TemplateController {
    /**
     * Constructor
     */
    public function __construct() {
        // Template-Filter registrieren
        add_filter('template_include', [$this, 'loadCustomTemplate']);
        add_filter('single_template', [$this, 'loadSingleTemplate']);
        
        // Shortcodes registrieren, die Templates verwenden
        add_shortcode('dein_plugin_template', [$this, 'renderTemplateShortcode']);
    }
    
    /**
     * Lädt benutzerdefinierte Templates basierend auf bestimmten Bedingungen
     * 
     * @param string $template Originales Template
     * @return string Modifiziertes Template-Pfad
     */
    public function loadCustomTemplate($template) {
        // Beispiel: Benutzerdefinierte Archiv-Seite für ein Custom Post Type
        if (is_post_type_archive('dein_custom_post_type')) {
            $custom_template = plugin_dir_path(dirname(dirname(__FILE__))) . 'views/archive-custom.php';
            
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Lädt ein benutzerdefiniertes Single-Template für bestimmte Post-Types
     * 
     * @param string $template Originales Single-Template
     * @return string Modifiziertes Template-Pfad
     */
    public function loadSingleTemplate($template) {
        if (is_singular('dein_custom_post_type')) {
            $custom_template = plugin_dir_path(dirname(dirname(__FILE__))) . 'views/single-custom.php';
            
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Rendert ein Template per Shortcode
     * 
     * @param array $atts Shortcode-Attribute
     * @param string $content Eingeschlossener Inhalt
     * @return string Gerenderte HTML-Ausgabe
     */
    public function renderTemplateShortcode($atts, $content = null) {
        // Shortcode-Attribute mit Standardwerten
        $attributes = shortcode_atts([
            'template' => 'default',
            'id' => 0
        ], $atts);
        
        // Template-Pfad bestimmen
        $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'views/shortcodes/' . $attributes['template'] . '.php';
        
        // Prüfen, ob Template existiert
        if (!file_exists($template_path)) {
            return '<p>Template nicht gefunden.</p>';
        }
        
        // Output-Buffering starten, um HTML zu erfassen
        ob_start();
        
        // Daten für das Template vorbereiten
        $template_data = [
            'id' => $attributes['id'],
            'content' => $content
        ];
        
        // Template laden und Variablen extrahieren
        extract($template_data);
        include $template_path;
        
        // Gerenderten Inhalt zurückgeben
        return ob_get_clean();
    }
    
    /**
     * Hilfsmethode zum Laden von Teil-Templates
     * 
     * @param string $template_name Name des Teil-Templates
     * @param array $args Variablen für das Template
     * @return void
     */
    public function getTemplatePart($template_name, $args = []) {
        $template_path = plugin_dir_path(dirname(dirname(__FILE__))) . 'views/parts/' . $template_name . '.php';
        
        if (file_exists($template_path)) {
            extract($args);
            include $template_path;
        }
    }
}