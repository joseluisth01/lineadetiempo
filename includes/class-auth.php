<?php
/**
 * Clase para manejar autenticación
 */

class GP_Auth {
    
    /**
     * Iniciar sesión
     */
    public function login($username, $password) {
        global $wpdb;
        $table_users = $wpdb->prefix . 'gp_users';
        
        // Buscar usuario
        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_users WHERE username = %s AND status = 'active'",
            $username
        ), ARRAY_A);
        
        // Verificar si existe el usuario
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Usuario o contraseña incorrectos'
            ];
        }
        
        // Verificar contraseña
        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Usuario o contraseña incorrectos'
            ];
        }
        
        // Iniciar sesión
        $session = new GP_Session();
        $session->start_session($user);
        
        return [
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'redirect' => home_url('/login-proyectos/')
        ];
    }
    
    /**
     * Cerrar sesión
     */
    public function logout() {
        $session = new GP_Session();
        $session->end_session();
    }
    
    /**
     * Verificar si un username ya existe
     */
    public function username_exists($username, $exclude_id = null) {
        global $wpdb;
        $table_users = $wpdb->prefix . 'gp_users';
        
        $query = "SELECT COUNT(*) FROM $table_users WHERE username = %s";
        $params = [$username];
        
        if ($exclude_id) {
            $query .= " AND id != %d";
            $params[] = $exclude_id;
        }
        
        $count = $wpdb->get_var($wpdb->prepare($query, $params));
        
        return $count > 0;
    }
    
    /**
     * Verificar si un email ya existe
     */
    public function email_exists($email, $exclude_id = null) {
        global $wpdb;
        $table_users = $wpdb->prefix . 'gp_users';
        
        $query = "SELECT COUNT(*) FROM $table_users WHERE email = %s";
        $params = [$email];
        
        if ($exclude_id) {
            $query .= " AND id != %d";
            $params[] = $exclude_id;
        }
        
        $count = $wpdb->get_var($wpdb->prepare($query, $params));
        
        return $count > 0;
    }
    
    /**
     * Generar contraseña aleatoria
     */
    public function generate_password($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
        $password = '';
        $chars_length = strlen($chars);
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $chars_length - 1)];
        }
        
        return $password;
    }
}