<?php
/**
 * Sistema de autenticación personalizado
 * 
 * Ubicación: includes/class-pt-auth.php
 */

if (!defined('ABSPATH')) exit;

class PT_Auth {
    
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new PT_Auth();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Iniciar sesión lo antes posible
        add_action('init', array($this, 'startSession'), 1);
        add_action('init', array($this, 'handleLogout'), 10);
    }
    
    /**
     * Iniciar sesión PHP
     */
    public function startSession() {
        if (!session_id() && !headers_sent()) {
            session_start();
        }
    }
    
    /**
     * Crear super admin inicial
     */
    public static function createSuperAdmin() {
        global $wpdb;
        $table = $wpdb->prefix . 'pt_users';
        
        // Verificar si ya existe
        $exists = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE role = 'super_admin'");
        
        if ($exists == 0) {
            $password_hash = password_hash('adminproyectos', PASSWORD_BCRYPT);
            
            $inserted = $wpdb->insert($table, array(
                'username' => 'administrador',
                'email' => 'admin@proyectos.com',
                'password' => $password_hash,
                'nombre' => 'Super',
                'apellidos' => 'Administrador',
                'role' => 'super_admin',
                'active' => 1,
                'created_at' => current_time('mysql')
            ));
            
            if ($inserted) {
                error_log('PT: Super admin creado correctamente');
            } else {
                error_log('PT: Error al crear super admin - ' . $wpdb->last_error);
            }
        }
    }
    
    /**
     * Login de usuario
     */
    public static function login($username, $password) {
        global $wpdb;
        $table = $wpdb->prefix . 'pt_users';
        
        // Log para debugging
        error_log("PT: Intentando login con usuario: " . $username);
        
        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE username = %s AND active = 1",
            $username
        ));
        
        if (!$user) {
            error_log("PT: Usuario no encontrado o inactivo: " . $username);
            return array('success' => false, 'message' => 'Usuario o contraseña incorrectos');
        }
        
        error_log("PT: Usuario encontrado, verificando contraseña...");
        
        // Verificar contraseña
        if (password_verify($password, $user->password)) {
            error_log("PT: Contraseña correcta, iniciando sesión...");
            
            // Iniciar sesión si no está iniciada
            if (!session_id() && !headers_sent()) {
                session_start();
            }
            
            // Actualizar último login
            $wpdb->update($table, 
                array('last_login' => current_time('mysql')),
                array('id' => $user->id)
            );
            
            // Guardar en sesión
            $_SESSION['pt_user_id'] = $user->id;
            $_SESSION['pt_username'] = $user->username;
            $_SESSION['pt_role'] = $user->role;
            $_SESSION['pt_nombre'] = $user->nombre . ' ' . $user->apellidos;
            
            error_log("PT: Sesión iniciada para usuario ID: " . $user->id);
            
            // Registrar en audit log
            PT_Audit_Log::log($user->id, 'login', 'user', $user->id, 'Inicio de sesión exitoso');
            
            return array('success' => true, 'user' => $user);
        }
        
        error_log("PT: Contraseña incorrecta para usuario: " . $username);
        return array('success' => false, 'message' => 'Usuario o contraseña incorrectos');
    }
    
    /**
     * Logout de usuario
     */
    public function handleLogout() {
        if (isset($_GET['pt_logout'])) {
            if (self::isLoggedIn()) {
                PT_Audit_Log::log(self::getCurrentUserId(), 'logout', 'user', self::getCurrentUserId(), 'Cierre de sesión');
            }
            
            // Limpiar sesión
            $_SESSION = array();
            
            if (session_id()) {
                session_destroy();
            }
            
            wp_redirect(home_url('/login-proyectos'));
            exit;
        }
    }
    
    /**
     * Verificar si el usuario está logueado
     */
    public static function isLoggedIn() {
        // Asegurar que la sesión está iniciada
        if (!session_id() && !headers_sent()) {
            session_start();
        }
        
        return isset($_SESSION['pt_user_id']) && !empty($_SESSION['pt_user_id']);
    }
    
    /**
     * Obtener ID del usuario actual
     */
    public static function getCurrentUserId() {
        if (!session_id() && !headers_sent()) {
            session_start();
        }
        
        return isset($_SESSION['pt_user_id']) ? $_SESSION['pt_user_id'] : 0;
    }
    
    /**
     * Obtener rol del usuario actual
     */
    public static function getCurrentUserRole() {
        if (!session_id() && !headers_sent()) {
            session_start();
        }
        
        return isset($_SESSION['pt_role']) ? $_SESSION['pt_role'] : '';
    }
    
    /**
     * Obtener usuario actual completo
     */
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'pt_users';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            self::getCurrentUserId()
        ));
    }
    
    /**
     * Verificar si estamos en una ruta del proyecto
     */
    public static function isProjectRoute() {
        $uri = $_SERVER['REQUEST_URI'];
        return strpos($uri, '/login-proyectos') !== false || 
               strpos($uri, '/mis-proyectos') !== false ||
               strpos($uri, '/proyecto/') !== false;
    }
    
    /**
     * Redireccionar si no está logueado
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            wp_redirect(home_url('/login-proyectos'));
            exit;
        }
    }
    
    /**
     * Crear nuevo usuario
     */
    public static function createUser($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pt_users';
        
        // Validar que no exista el username o email
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE username = %s OR email = %s",
            $data['username'],
            $data['email']
        ));
        
        if ($exists > 0) {
            return array('success' => false, 'message' => 'El usuario o email ya existe');
        }
        
        // Generar contraseña temporal
        $password = wp_generate_password(12, false);
        
        // Insertar usuario
        $inserted = $wpdb->insert($table, array(
            'username' => sanitize_user($data['username']),
            'email' => sanitize_email($data['email']),
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'nombre' => sanitize_text_field($data['nombre']),
            'apellidos' => sanitize_text_field($data['apellidos']),
            'role' => sanitize_text_field($data['role']),
            'active' => 1,
            'created_at' => current_time('mysql'),
            'created_by' => self::getCurrentUserId()
        ));
        
        if ($inserted) {
            $user_id = $wpdb->insert_id;
            
            // Enviar email con credenciales
            PT_Notifications::sendWelcomeEmail($user_id, $data['email'], $data['username'], $password);
            
            // Log de auditoría
            PT_Audit_Log::log(
                self::getCurrentUserId(), 
                'create_user', 
                'user', 
                $user_id, 
                "Usuario creado: {$data['username']} ({$data['role']})"
            );
            
            return array('success' => true, 'user_id' => $user_id, 'password' => $password);
        }
        
        return array('success' => false, 'message' => 'Error al crear el usuario');
    }
}