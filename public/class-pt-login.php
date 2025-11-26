<?php
/**
 * Sistema de Login Frontend
 * 
 * Ubicación: public/class-pt-login.php
 */

if (!defined('ABSPATH')) exit;

class PT_Login {
    
    public function __construct() {
        add_action('init', array($this, 'handleLogin'));
        add_filter('template_include', array($this, 'loadLoginTemplate'));
    }
    
    /**
     * Procesar login
     */
    public function handleLogin() {
        if (isset($_POST['pt_login_submit'])) {
            $username = sanitize_user($_POST['pt_username']);
            $password = $_POST['pt_password'];
            
            $result = PT_Auth::login($username, $password);
            
            if ($result['success']) {
                wp_redirect(home_url('/mis-proyectos'));
                exit;
            } else {
                $_SESSION['pt_login_error'] = $result['message'];
            }
        }
    }
    
    /**
     * Cargar template de login
     */
    public function loadLoginTemplate($template) {
        if ($this->isLoginPage()) {
            // Si ya está logueado, redirigir
            if (PT_Auth::isLoggedIn()) {
                wp_redirect(home_url('/mis-proyectos'));
                exit;
            }
            
            return PT_PLUGIN_DIR . 'templates/login.php';
        }
        
        return $template;
    }
    
    /**
     * Verificar si estamos en la página de login
     */
    private function isLoginPage() {
        $uri = trim($_SERVER['REQUEST_URI'], '/');
        return $uri === 'login-proyectos' || strpos($uri, 'login-proyectos') !== false;
    }
}

new PT_Login();