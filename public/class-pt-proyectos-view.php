<?php
/**
 * Vista de lista de proyectos
 * 
 * Ubicación: public/class-pt-proyectos-view.php
 */

if (!defined('ABSPATH')) exit;

class PT_Proyectos_View {
    
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new PT_Proyectos_View();
        }
        return self::$instance;
    }
    
    /**
     * Renderizar lista de proyectos
     */
    public static function render() {
        PT_Auth::requireLogin();
        
        $user_id = PT_Auth::getCurrentUserId();
        $user_role = PT_Auth::getCurrentUserRole();
        $user = PT_Auth::getCurrentUser();
        
        // Obtener proyectos según el rol
        if ($user_role === 'super_admin' || $user_role === 'admin') {
            $proyectos = self::getAllProyectos();
        } else {
            $proyectos = self::getProyectosByUser($user_id);
        }
        
        include PT_PLUGIN_DIR . 'templates/proyectos-list.php';
    }
    
    /**
     * Obtener todos los proyectos (admin)
     */
    private static function getAllProyectos() {
        $args = array(
            'post_type' => 'pt_proyecto',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        return get_posts($args);
    }
    
    /**
     * Obtener proyectos de un usuario específico
     */
    private static function getProyectosByUser($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pt_user_proyectos';
        
        $proyecto_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT proyecto_id FROM $table WHERE user_id = %d",
            $user_id
        ));
        
        if (empty($proyecto_ids)) {
            return array();
        }
        
        $args = array(
            'post_type' => 'pt_proyecto',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'post__in' => $proyecto_ids,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        return get_posts($args);
    }
    
    /**
     * Obtener meta de proyecto
     */
    public static function getProyectoMeta($proyecto_id) {
        return array(
            'direccion' => get_post_meta($proyecto_id, '_pt_direccion', true),
            'fecha_inicio' => get_post_meta($proyecto_id, '_pt_fecha_inicio', true),
            'fecha_fin' => get_post_meta($proyecto_id, '_pt_fecha_fin', true),
            'fecha_fin_real' => get_post_meta($proyecto_id, '_pt_fecha_fin_real', true),
            'imagen_principal' => get_post_meta($proyecto_id, '_pt_imagen_principal', true),
            'estado' => get_post_meta($proyecto_id, '_pt_estado', true)
        );
    }
    
    /**
     * Contar hitos de un proyecto
     */
    public static function countHitos($proyecto_id) {
        $args = array(
            'post_type' => 'pt_hito',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_pt_proyecto_id',
                    'value' => $proyecto_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1
        );
        
        $hitos = get_posts($args);
        return count($hitos);
    }
}