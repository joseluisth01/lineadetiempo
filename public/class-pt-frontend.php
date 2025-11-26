<?php
/**
 * Controlador del Frontend
 * 
 * Ubicación: public/class-pt-frontend.php
 */

if (!defined('ABSPATH')) exit;

class PT_Frontend {
    
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new PT_Frontend();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'registerRewriteRules'));
        add_filter('query_vars', array($this, 'addQueryVars'));
        add_filter('template_include', array($this, 'templateRedirect'));
    }
    
    /**
     * Registrar reglas de reescritura
     */
    public function registerRewriteRules() {
        add_rewrite_rule(
            '^login-proyectos/?$',
            'index.php?pt_page=login',
            'top'
        );
        
        add_rewrite_rule(
            '^mis-proyectos/?$',
            'index.php?pt_page=proyectos',
            'top'
        );
        
        add_rewrite_rule(
            '^proyecto/([0-9]+)/?$',
            'index.php?pt_page=timeline&pt_proyecto_id=$matches[1]',
            'top'
        );
    }
    
    /**
     * Añadir variables de consulta
     */
    public function addQueryVars($vars) {
        $vars[] = 'pt_page';
        $vars[] = 'pt_proyecto_id';
        return $vars;
    }
    
    /**
     * Redireccionar templates
     */
    public function templateRedirect($template) {
        $pt_page = get_query_var('pt_page');
        
        if (!$pt_page) {
            return $template;
        }
        
        switch ($pt_page) {
            case 'login':
                return PT_PLUGIN_DIR . 'templates/login.php';
                
            case 'proyectos':
                PT_Auth::requireLogin();
                return PT_PLUGIN_DIR . 'templates/proyectos-list.php';
                
            case 'timeline':
                PT_Auth::requireLogin();
                return PT_PLUGIN_DIR . 'templates/timeline.php';
                
            default:
                return $template;
        }
    }
}