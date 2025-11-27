<?php
/**
 * Clase para manejar usuarios
 */

class GP_Users {
    
    /**
     * Crear nuevo usuario
     */
    public function create_user($data) {
        global $wpdb;
        $table_users = $wpdb->prefix . 'gp_users';
        
        // Validaciones
        if (empty($data['nombre']) || empty($data['email']) || empty($data['username']) || empty($data['password'])) {
            return [
                'success' => false,
                'message' => 'Todos los campos son obligatorios'
            ];
        }
        
        // Verificar si el username ya existe
        $auth = new GP_Auth();
        if ($auth->username_exists($data['username'])) {
            return [
                'success' => false,
                'message' => 'El nombre de usuario ya existe'
            ];
        }
        
        // Verificar si el email ya existe
        if ($auth->email_exists($data['email'])) {
            return [
                'success' => false,
                'message' => 'El email ya está registrado'
            ];
        }
        
        // Obtener usuario actual
        $session = new GP_Session();
        $current_user = $session->get_user();
        
        // Insertar usuario
        $result = $wpdb->insert($table_users, [
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'username' => $data['username'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'user_type' => $data['user_type'],
            'created_by' => $current_user ? $current_user['id'] : null,
            'status' => 'active'
        ]);
        
        if ($result === false) {
            return [
                'success' => false,
                'message' => 'Error al crear el usuario'
            ];
        }
        
        $user_id = $wpdb->insert_id;
        
        // Registrar en log
        $db = new GP_Database();
        $db->log_action(
            $current_user ? $current_user['id'] : 0,
            'create_user',
            'gp_users',
            $user_id,
            json_encode(['username' => $data['username'], 'user_type' => $data['user_type']])
        );
        
        // Enviar notificación por email
        $notifications = new GP_Notifications();
        $notifications->send_welcome_email(
            $data['email'],
            $data['nombre'],
            $data['username'],
            $data['password']
        );
        
        return [
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'user_id' => $user_id
        ];
    }
    
    /**
     * Obtener usuario por ID
     */
    public function get_user($user_id) {
        global $wpdb;
        $table_users = $wpdb->prefix . 'gp_users';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT id, nombre, email, username, user_type, created_at, last_login, status FROM $table_users WHERE id = %d",
            $user_id
        ), ARRAY_A);
    }
    
    /**
     * Obtener todos los usuarios
     */
    public function get_all_users($user_type = null) {
        global $wpdb;
        $table_users = $wpdb->prefix . 'gp_users';
        
        if ($user_type) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT id, nombre, email, username, user_type, created_at, last_login, status FROM $table_users WHERE user_type = %s ORDER BY created_at DESC",
                $user_type
            ), ARRAY_A);
        } else {
            return $wpdb->get_results(
                "SELECT id, nombre, email, username, user_type, created_at, last_login, status FROM $table_users ORDER BY created_at DESC",
                ARRAY_A
            );
        }
    }
    
    /**
     * Actualizar usuario
     */
    public function update_user($user_id, $data) {
        global $wpdb;
        $table_users = $wpdb->prefix . 'gp_users';
        
        $update_data = [];
        
        if (isset($data['nombre'])) {
            $update_data['nombre'] = $data['nombre'];
        }
        
        if (isset($data['email'])) {
            // Verificar si el email ya existe
            $auth = new GP_Auth();
            if ($auth->email_exists($data['email'], $user_id)) {
                return [
                    'success' => false,
                    'message' => 'El email ya está registrado'
                ];
            }
            $update_data['email'] = $data['email'];
        }
        
        if (isset($data['password']) && !empty($data['password'])) {
            $update_data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (isset($data['status'])) {
            $update_data['status'] = $data['status'];
        }
        
        if (empty($update_data)) {
            return [
                'success' => false,
                'message' => 'No hay datos para actualizar'
            ];
        }
        
        $result = $wpdb->update($table_users, $update_data, ['id' => $user_id]);
        
        if ($result === false) {
            return [
                'success' => false,
                'message' => 'Error al actualizar el usuario'
            ];
        }
        
        // Registrar en log
        $session = new GP_Session();
        $current_user = $session->get_user();
        $db = new GP_Database();
        $db->log_action(
            $current_user['id'],
            'update_user',
            'gp_users',
            $user_id,
            json_encode($update_data)
        );
        
        return [
            'success' => true,
            'message' => 'Usuario actualizado exitosamente'
        ];
    }
    
    /**
     * Eliminar usuario
     */
    public function delete_user($user_id) {
        global $wpdb;
        $table_users = $wpdb->prefix . 'gp_users';
        
        // No permitir eliminar al super admin principal
        $user = $this->get_user($user_id);
        if ($user['username'] === 'administrador') {
            return [
                'success' => false,
                'message' => 'No se puede eliminar el super administrador principal'
            ];
        }
        
        $result = $wpdb->delete($table_users, ['id' => $user_id]);
        
        if ($result === false) {
            return [
                'success' => false,
                'message' => 'Error al eliminar el usuario'
            ];
        }
        
        // Registrar en log
        $session = new GP_Session();
        $current_user = $session->get_user();
        $db = new GP_Database();
        $db->log_action(
            $current_user['id'],
            'delete_user',
            'gp_users',
            $user_id,
            json_encode(['username' => $user['username']])
        );
        
        return [
            'success' => true,
            'message' => 'Usuario eliminado exitosamente'
        ];
    }
}