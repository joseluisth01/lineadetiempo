<?php
/**
 * Plugin Name: Gestión de Proyectos
 * Plugin URI: https://tudominio.com
 * Description: Sistema de gestión de proyectos con línea de tiempo para clientes
 * Version: 1.0.0
 * Author: Tu Nombre
 * Author URI: https://tudominio.com
 * Text Domain: gestion-proyectos
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('GP_VERSION', '1.0.0');
define('GP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GP_PLUGIN_FILE', __FILE__);

// Incluir archivos principales
require_once GP_PLUGIN_DIR . 'includes/class-database.php';
require_once GP_PLUGIN_DIR . 'includes/class-auth.php';
require_once GP_PLUGIN_DIR . 'includes/class-users.php';
require_once GP_PLUGIN_DIR . 'includes/class-notifications.php';
require_once GP_PLUGIN_DIR . 'includes/class-session.php';

// Hooks de activación y desactivación
register_activation_hook(__FILE__, 'gp_activate_plugin');
register_deactivation_hook(__FILE__, 'gp_deactivate_plugin');

/**
 * Activación del plugin
 */
function gp_activate_plugin() {
    $database = new GP_Database();
    $database->create_tables();
    $database->create_super_admin();
    
    // Crear página de login si no existe
    gp_create_login_page();
    
    // Limpiar rewrite rules
    flush_rewrite_rules();
}

/**
 * Desactivación del plugin
 */
function gp_deactivate_plugin() {
    flush_rewrite_rules();
}

/**
 * Crear página de login
 */
function gp_create_login_page() {
    $login_page = get_page_by_path('login-proyectos');
    
    if (!$login_page) {
        wp_insert_post([
            'post_title' => 'Login Proyectos',
            'post_name' => 'login-proyectos',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => '[gp_login_form]'
        ]);
    }
}

// Inicializar el plugin
add_action('init', 'gp_init_plugin');

function gp_init_plugin() {
    // Registrar shortcodes
    add_shortcode('gp_login_form', 'gp_login_form_shortcode');
    add_shortcode('gp_dashboard', 'gp_dashboard_shortcode');
    
    // Registrar estilos y scripts
    add_action('wp_enqueue_scripts', 'gp_enqueue_assets');
    
    // Manejar peticiones AJAX
    add_action('wp_ajax_gp_login', 'gp_handle_login');
    add_action('wp_ajax_nopriv_gp_login', 'gp_handle_login');
    add_action('wp_ajax_gp_logout', 'gp_handle_logout');
    add_action('wp_ajax_gp_create_user', 'gp_handle_create_user');
    add_action('wp_ajax_gp_get_users', 'gp_handle_get_users');
    add_action('wp_ajax_gp_delete_user', 'gp_handle_delete_user');
}

/**
 * Encolar estilos y scripts
 */
function gp_enqueue_assets() {
    // CSS principal
    wp_enqueue_style('gp-styles', GP_PLUGIN_URL . 'assets/css/styles.css', [], GP_VERSION);
    
    // JavaScript principal
    wp_enqueue_script('gp-scripts', GP_PLUGIN_URL . 'assets/js/scripts.js', ['jquery'], GP_VERSION, true);
    
    // Pasar datos al JavaScript
    wp_localize_script('gp-scripts', 'gpAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gp_nonce')
    ]);
}

/**
 * Interceptar la página de login para mostrar template standalone
 */
add_action('template_redirect', 'gp_intercept_login_page');

function gp_intercept_login_page() {
    if (is_page('login-proyectos')) {
        $session = new GP_Session();
        
        // Si ya está logueado, mostrar dashboard
        if ($session->is_logged_in()) {
            return;
        }
        
        // Mostrar login standalone (sin tema)
        include GP_PLUGIN_DIR . 'login-standalone.php';
        exit;
    }
}

/**
 * Shortcode para formulario de login
 */
function gp_login_form_shortcode() {
    $session = new GP_Session();
    
    // Si ya está logueado, mostrar dashboard
    if ($session->is_logged_in()) {
        ob_start();
        include GP_PLUGIN_DIR . 'templates/dashboard.php';
        return ob_get_clean();
    }
    
    // Si no está logueado, redirigir a login standalone
    wp_redirect(home_url('/login-proyectos/'));
    exit;
}

/**
 * Shortcode para dashboard
 */
function gp_dashboard_shortcode() {
    $session = new GP_Session();
    
    // Verificar si está logueado
    if (!$session->is_logged_in()) {
        return '<p>Debes iniciar sesión para ver esta página.</p>';
    }
    
    ob_start();
    include GP_PLUGIN_DIR . 'templates/dashboard.php';
    return ob_get_clean();
}

/**
 * Manejar login AJAX
 */
function gp_handle_login() {
    check_ajax_referer('gp_nonce', 'nonce');
    
    $username = sanitize_text_field($_POST['username']);
    $password = $_POST['password'];
    
    $auth = new GP_Auth();
    $result = $auth->login($username, $password);
    
    wp_send_json($result);
}

/**
 * Manejar logout AJAX
 */
function gp_handle_logout() {
    check_ajax_referer('gp_nonce', 'nonce');
    
    $auth = new GP_Auth();
    $auth->logout();
    
    wp_send_json(['success' => true]);
}

/**
 * Manejar creación de usuario AJAX
 */
function gp_handle_create_user() {
    check_ajax_referer('gp_nonce', 'nonce');
    
    $session = new GP_Session();
    
    // Verificar permisos
    if (!$session->is_logged_in()) {
        wp_send_json(['success' => false, 'message' => 'No autorizado']);
    }
    
    $current_user = $session->get_user();
    $user_type = sanitize_text_field($_POST['user_type']);
    
    // Solo super_admin puede crear admins
    if ($user_type === 'admin' && $current_user['user_type'] !== 'super_admin') {
        wp_send_json(['success' => false, 'message' => 'No tienes permisos para crear administradores']);
    }
    
    $users = new GP_Users();
    $result = $users->create_user([
        'nombre' => sanitize_text_field($_POST['nombre']),
        'email' => sanitize_email($_POST['email']),
        'username' => sanitize_text_field($_POST['username']),
        'password' => $_POST['password'],
        'user_type' => $user_type
    ]);
    
    wp_send_json($result);
}

/**
 * Obtener usuarios AJAX
 */
function gp_handle_get_users() {
    check_ajax_referer('gp_nonce', 'nonce');
    
    $session = new GP_Session();
    
    // Verificar permisos
    if (!$session->is_admin()) {
        wp_send_json(['success' => false, 'message' => 'No autorizado']);
    }
    
    $user_type = isset($_POST['user_type']) ? sanitize_text_field($_POST['user_type']) : null;
    
    $users = new GP_Users();
    $result = $users->get_all_users($user_type);
    
    wp_send_json(['success' => true, 'users' => $result]);
}

/**
 * Eliminar usuario AJAX
 */
function gp_handle_delete_user() {
    check_ajax_referer('gp_nonce', 'nonce');
    
    $session = new GP_Session();
    
    // Verificar permisos
    if (!$session->is_admin()) {
        wp_send_json(['success' => false, 'message' => 'No autorizado']);
    }
    
    $user_id = intval($_POST['user_id']);
    
    $users = new GP_Users();
    $result = $users->delete_user($user_id);
    
    wp_send_json($result);
}