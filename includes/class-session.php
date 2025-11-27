<?php
/**
 * Clase para manejar sesiones de usuario
 */

class GP_Session {
    
    private $session_key = 'gp_user_session';
    
    public function __construct() {
        if (!session_id()) {
            session_start();
        }
    }
    
    /**
     * Iniciar sesión de usuario
     */
    public function start_session($user_data) {
        $_SESSION[$this->session_key] = [
            'id' => $user_data['id'],
            'nombre' => $user_data['nombre'],
            'email' => $user_data['email'],
            'username' => $user_data['username'],
            'user_type' => $user_data['user_type'],
            'login_time' => time()
        ];
        
        // Actualizar último login
        global $wpdb;
        $table_users = $wpdb->prefix . 'gp_users';
        $wpdb->update(
            $table_users,
            ['last_login' => current_time('mysql')],
            ['id' => $user_data['id']]
        );
        
        // Registrar en log
        $db = new GP_Database();
        $db->log_action($user_data['id'], 'login');
    }
    
    /**
     * Cerrar sesión
     */
    public function end_session() {
        if (isset($_SESSION[$this->session_key])) {
            $user_id = $_SESSION[$this->session_key]['id'];
            
            // Registrar en log
            $db = new GP_Database();
            $db->log_action($user_id, 'logout');
            
            unset($_SESSION[$this->session_key]);
        }
    }
    
    /**
     * Verificar si hay sesión activa
     */
    public function is_logged_in() {
        return isset($_SESSION[$this->session_key]);
    }
    
    /**
     * Obtener datos del usuario actual
     */
    public function get_user() {
        if ($this->is_logged_in()) {
            return $_SESSION[$this->session_key];
        }
        return null;
    }
    
    /**
     * Obtener ID del usuario actual
     */
    public function get_user_id() {
        if ($this->is_logged_in()) {
            return $_SESSION[$this->session_key]['id'];
        }
        return null;
    }
    
    /**
     * Verificar si el usuario es super admin
     */
    public function is_super_admin() {
        if ($this->is_logged_in()) {
            return $_SESSION[$this->session_key]['user_type'] === 'super_admin';
        }
        return false;
    }
    
    /**
     * Verificar si el usuario es admin o super admin
     */
    public function is_admin() {
        if ($this->is_logged_in()) {
            $type = $_SESSION[$this->session_key]['user_type'];
            return $type === 'admin' || $type === 'super_admin';
        }
        return false;
    }
    
    /**
     * Verificar si el usuario es cliente
     */
    public function is_cliente() {
        if ($this->is_logged_in()) {
            return $_SESSION[$this->session_key]['user_type'] === 'cliente';
        }
        return false;
    }
}