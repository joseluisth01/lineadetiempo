<?php
/**
 * Plugin Name: Gestor de Proyectos con Línea de Tiempo
 * Plugin URI: https://tictac-comunicacion.es/
 * Description: Sistema completo de gestión de proyectos con líneas de tiempo para clientes
 * Version: 1.0.0
 * Author: TICTAC COMUNICACIÓN
 * Author URI: https://tictac-comunicacion.es/
 * Text Domain: proyectos-timeline
 * Domain Path: /languages
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('PT_VERSION', '1.0.0');
define('PT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Clase principal del plugin
 */
class ProyectosTimeline {
    
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new ProyectosTimeline();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->loadDependencies();
        $this->defineHooks();
    }
    
    /**
     * Cargar todos los archivos necesarios
     */
    private function loadDependencies() {
        // Core
        require_once PT_PLUGIN_DIR . 'includes/class-pt-database.php';
        require_once PT_PLUGIN_DIR . 'includes/class-pt-roles.php';
        require_once PT_PLUGIN_DIR . 'includes/class-pt-auth.php';
        require_once PT_PLUGIN_DIR . 'includes/class-pt-security.php';
        
        // Custom Post Types
        require_once PT_PLUGIN_DIR . 'includes/post-types/class-pt-proyecto.php';
        require_once PT_PLUGIN_DIR . 'includes/post-types/class-pt-hito.php';
        
        // Admin
        require_once PT_PLUGIN_DIR . 'admin/class-pt-admin.php';
        require_once PT_PLUGIN_DIR . 'admin/class-pt-usuarios-admin.php';
        require_once PT_PLUGIN_DIR . 'admin/class-pt-proyectos-admin.php';
        require_once PT_PLUGIN_DIR . 'admin/class-pt-hitos-admin.php';
        
        // Frontend
        require_once PT_PLUGIN_DIR . 'public/class-pt-frontend.php';
        require_once PT_PLUGIN_DIR . 'public/class-pt-login.php';
        require_once PT_PLUGIN_DIR . 'public/class-pt-proyectos-view.php';
        require_once PT_PLUGIN_DIR . 'public/class-pt-timeline-view.php';
        
        // Utilidades
        require_once PT_PLUGIN_DIR . 'includes/class-pt-notifications.php';
        require_once PT_PLUGIN_DIR . 'includes/class-pt-documents.php';
        require_once PT_PLUGIN_DIR . 'includes/class-pt-audit-log.php';
        
        // AJAX
        require_once PT_PLUGIN_DIR . 'includes/ajax/class-pt-ajax-handler.php';
    }
    
    /**
     * Definir hooks de WordPress
     */
    private function defineHooks() {
        // Activación y desactivación
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Inicialización
        add_action('plugins_loaded', array($this, 'init'));
        add_action('init', array($this, 'loadTextDomain'));
        
        // Enqueue scripts y estilos
        add_action('wp_enqueue_scripts', array($this, 'enqueuePublicAssets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminAssets'));
    }
    
    /**
     * Activación del plugin
     */
    public function activate() {
        PT_Database::createTables();
        PT_Roles::createRoles();
        PT_Auth::createSuperAdmin();
        flush_rewrite_rules();
    }
    
    /**
     * Desactivación del plugin
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Inicializar el plugin
     */
    public function init() {
        // Inicializar Custom Post Types
        PT_Proyecto::init();
        PT_Hito::init();
        
        // Inicializar clases
        PT_Auth::getInstance();
        PT_Frontend::getInstance();
        PT_Admin::getInstance();
        PT_Ajax_Handler::getInstance();
    }
    
    /**
     * Cargar idiomas
     */
    public function loadTextDomain() {
        load_plugin_textdomain('proyectos-timeline', false, dirname(PT_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Enqueue assets públicos
     */
    public function enqueuePublicAssets() {
        if (PT_Auth::isProjectRoute()) {
            wp_enqueue_style('pt-public-css', PT_PLUGIN_URL . 'assets/css/public.css', array(), PT_VERSION);
            wp_enqueue_style('pt-timeline-css', PT_PLUGIN_URL . 'assets/css/timeline.css', array(), PT_VERSION);
            
            wp_enqueue_script('pt-public-js', PT_PLUGIN_URL . 'assets/js/public.js', array('jquery'), PT_VERSION, true);
            wp_enqueue_script('pt-timeline-js', PT_PLUGIN_URL . 'assets/js/timeline.js', array('jquery'), PT_VERSION, true);
            wp_enqueue_script('pt-modal-js', PT_PLUGIN_URL . 'assets/js/modal.js', array('jquery'), PT_VERSION, true);
            
            wp_localize_script('pt-public-js', 'ptAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('pt_nonce')
            ));
        }
    }
    
    /**
     * Enqueue assets admin
     */
    public function enqueueAdminAssets($hook) {
        if (strpos($hook, 'pt-') !== false || get_post_type() === 'pt_proyecto' || get_post_type() === 'pt_hito') {
            wp_enqueue_style('pt-admin-css', PT_PLUGIN_URL . 'assets/css/admin.css', array(), PT_VERSION);
            wp_enqueue_script('pt-admin-js', PT_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), PT_VERSION, true);
            
            wp_enqueue_media();
            
            wp_localize_script('pt-admin-js', 'ptAdmin', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('pt_admin_nonce')
            ));
        }
    }
}

// Inicializar el plugin
function PT() {
    return ProyectosTimeline::getInstance();
}

PT();