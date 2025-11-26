<?php
/**
 * Gestión de roles y permisos
 * 
 * Ubicación: includes/class-pt-roles.php
 */

if (!defined('ABSPATH')) exit;

class PT_Roles {
    
    const SUPER_ADMIN = 'pt_super_admin';
    const ADMIN = 'pt_admin';
    const CLIENTE = 'pt_cliente';
    
    /**
     * Crear roles personalizados
     */
    public static function createRoles() {
        // Super Admin - Acceso total
        add_role(self::SUPER_ADMIN, 'Super Admin Proyectos', array(
            'read' => true,
            'pt_manage_all' => true,
            'pt_create_admins' => true,
            'pt_create_clients' => true,
            'pt_manage_proyectos' => true,
            'pt_manage_hitos' => true,
            'pt_manage_documents' => true,
            'pt_view_audit_log' => true,
        ));
        
        // Admin - Gestión de proyectos y clientes
        add_role(self::ADMIN, 'Administrador Proyectos', array(
            'read' => true,
            'pt_create_clients' => true,
            'pt_manage_proyectos' => true,
            'pt_manage_hitos' => true,
            'pt_manage_documents' => true,
        ));
        
        // Cliente - Solo visualización de sus proyectos
        add_role(self::CLIENTE, 'Cliente Proyectos', array(
            'read' => true,
            'pt_view_own_proyectos' => true,
            'pt_download_documents' => true,
        ));
    }
    
    /**
     * Eliminar roles
     */
    public static function removeRoles() {
        remove_role(self::SUPER_ADMIN);
        remove_role(self::ADMIN);
        remove_role(self::CLIENTE);
    }
    
    /**
     * Verificar si el usuario tiene un rol específico
     */
    public static function userHasRole($user_id, $role) {
        global $wpdb;
        $table = $wpdb->prefix . 'pt_users';
        
        $user_role = $wpdb->get_var($wpdb->prepare(
            "SELECT role FROM $table WHERE id = %d",
            $user_id
        ));
        
        return $user_role === $role;
    }
    
    /**
     * Verificar si el usuario es admin (super_admin o admin)
     */
    public static function isAdmin($user_id) {
        if (!$user_id || $user_id == 0) {
            return false;
        }
        
        return self::userHasRole($user_id, 'super_admin') || 
               self::userHasRole($user_id, 'admin');
    }
    
    /**
     * Verificar si el usuario es super admin
     */
    public static function isSuperAdmin($user_id) {
        if (!$user_id || $user_id == 0) {
            return false;
        }
        
        return self::userHasRole($user_id, 'super_admin');
    }
    
    /**
     * Verificar si el usuario es cliente
     */
    public static function isCliente($user_id) {
        if (!$user_id || $user_id == 0) {
            return false;
        }
        
        return self::userHasRole($user_id, 'cliente');
    }
    
    /**
     * Obtener nombre del rol para visualización
     */
    public static function getRoleName($role) {
        $nombres = array(
            'super_admin' => 'Super Administrador',
            'admin' => 'Administrador',
            'cliente' => 'Cliente'
        );
        
        return $nombres[$role] ?? 'Desconocido';
    }
    
    /**
     * Obtener todos los roles disponibles
     */
    public static function getAllRoles() {
        return array(
            'super_admin' => 'Super Administrador',
            'admin' => 'Administrador',
            'cliente' => 'Cliente'
        );
    }
    
    /**
     * Verificar si un usuario puede crear usuarios de un rol específico
     */
    public static function canCreateRole($user_id, $target_role) {
        if (self::isSuperAdmin($user_id)) {
            // Super admin puede crear cualquier rol
            return true;
        }
        
        if (self::isAdmin($user_id)) {
            // Admin puede crear clientes y otros admins, pero no super admins
            return in_array($target_role, array('admin', 'cliente'));
        }
        
        // Los clientes no pueden crear usuarios
        return false;
    }
    
    /**
     * Verificar permisos para una acción específica
     */
    public static function userCan($user_id, $capability) {
        global $wpdb;
        $table = $wpdb->prefix . 'pt_users';
        
        $user_role = $wpdb->get_var($wpdb->prepare(
            "SELECT role FROM $table WHERE id = %d",
            $user_id
        ));
        
        if (!$user_role) {
            return false;
        }
        
        // Permisos por rol
        $permissions = array(
            'super_admin' => array(
                'manage_all',
                'create_admins',
                'create_clients',
                'manage_proyectos',
                'manage_hitos',
                'manage_documents',
                'view_audit_log',
                'delete_users',
                'edit_users'
            ),
            'admin' => array(
                'create_clients',
                'manage_proyectos',
                'manage_hitos',
                'manage_documents',
                'edit_clients'
            ),
            'cliente' => array(
                'view_own_proyectos',
                'download_documents'
            )
        );
        
        if (!isset($permissions[$user_role])) {
            return false;
        }
        
        return in_array($capability, $permissions[$user_role]);
    }
    
    /**
     * Obtener rol del usuario actual
     */
    public static function getCurrentUserRole() {
        $user_id = PT_Auth::getCurrentUserId();
        
        if (!$user_id) {
            return null;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'pt_users';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT role FROM $table WHERE id = %d",
            $user_id
        ));
    }
}