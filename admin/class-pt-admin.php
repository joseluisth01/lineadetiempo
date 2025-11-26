<?php
/**
 * Panel de Administración
 * Ubicación: admin/class-pt-admin.php
 */

if (!defined('ABSPATH')) exit;

class PT_Admin {
    
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new PT_Admin();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'addAdminMenus'));
    }
    
    public function addAdminMenus() {
        add_menu_page(
            'Gestión de Proyectos',
            'Proyectos Timeline',
            'manage_options',
            'pt-dashboard',
            array($this, 'renderDashboard'),
            'dashicons-building',
            3
        );
        
        add_submenu_page(
            'pt-dashboard',
            'Usuarios',
            'Usuarios',
            'manage_options',
            'pt-usuarios',
            array($this, 'renderUsuarios')
        );
        
        add_submenu_page(
            'pt-dashboard',
            'Audit Log',
            'Audit Log',
            'manage_options',
            'pt-audit-log',
            array($this, 'renderAuditLog')
        );
    }
    
    public function renderDashboard() {
        echo '<div class="wrap">';
        echo '<h1>Dashboard - Gestión de Proyectos</h1>';
        echo '<p>Bienvenido al panel de gestión de proyectos.</p>';
        echo '<h2>Accesos Rápidos:</h2>';
        echo '<ul>';
        echo '<li><a href="' . admin_url('edit.php?post_type=pt_proyecto') . '">Gestionar Proyectos</a></li>';
        echo '<li><a href="' . admin_url('edit.php?post_type=pt_hito') . '">Gestionar Hitos</a></li>';
        echo '<li><a href="' . admin_url('admin.php?page=pt-usuarios') . '">Gestionar Usuarios</a></li>';
        echo '</ul>';
        echo '</div>';
    }
    
    public function renderUsuarios() {
        global $wpdb;
        $table = $wpdb->prefix . 'pt_users';
        $usuarios = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
        
        include PT_PLUGIN_DIR . 'admin/views/usuarios.php';
    }
    
    public function renderAuditLog() {
        $logs = PT_Audit_Log::getLogs(array(), 100);
        include PT_PLUGIN_DIR . 'admin/views/audit-log.php';
    }
}