<?php
/**
 * Seguridad del Plugin
 * Ubicación: includes/class-pt-security.php
 */

if (!defined('ABSPATH')) exit;

class PT_Security {
    
    public static function init() {
        // Prevenir enumeración de usuarios
        add_filter('rest_authentication_errors', array(__CLASS__, 'restrictRestApi'));
        
        // Sanitizar inputs
        add_action('init', array(__CLASS__, 'sanitizeInputs'));
    }
    
    public static function restrictRestApi($access) {
        if (!PT_Auth::isLoggedIn() && !is_user_logged_in()) {
            return new WP_Error('rest_forbidden', 'Acceso denegado', array('status' => 401));
        }
        return $access;
    }
    
    public static function sanitizeInputs() {
        $_GET = array_map('stripslashes_deep', $_GET);
        $_POST = array_map('stripslashes_deep', $_POST);
        $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
        $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
    }
    
    public static function validateNonce($nonce, $action) {
        return wp_verify_nonce($nonce, $action);
    }
}

PT_Security::init();