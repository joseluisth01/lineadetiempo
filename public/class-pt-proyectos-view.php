<?php
/**
 * Vista de Lista de Proyectos
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
    
    private function __construct() {
        // Constructor vacío - toda la lógica está en los métodos estáticos
    }
    
    /**
     * Renderizar lista de proyectos del cliente
     */
    public static function render() {
        if (!PT_Auth::isLoggedIn()) {
            wp_redirect(home_url('/login-proyectos'));
            exit;
        }
        
        $user_id = PT_Auth::getCurrentUserId();
        $user = PT_Auth::getCurrentUser();
        $proyectos = self::getProyectosUsuario($user_id);
        
        // Cargar template
        include PT_PLUGIN_DIR . 'templates/proyectos-list.php';
    }
    
    /**
     * Obtener proyectos del usuario actual
     */
    public static function getProyectosUsuario($user_id) {
        // Si es admin, mostrar todos
        if (PT_Roles::isAdmin($user_id)) {
            $args = array(
                'post_type' => 'pt_proyecto',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC'
            );
            
            return get_posts($args);
        }
        
        // Si es cliente, solo sus proyectos
        return PT_Proyecto::getProyectosCliente($user_id);
    }
    
    /**
     * Obtener datos de un proyecto para mostrar
     */
    public static function getProyectoData($proyecto_id) {
        $proyecto = get_post($proyecto_id);
        
        if (!$proyecto || $proyecto->post_type !== 'pt_proyecto') {
            return null;
        }
        
        $data = array(
            'id' => $proyecto->ID,
            'titulo' => $proyecto->post_title,
            'descripcion' => $proyecto->post_content,
            'direccion' => get_post_meta($proyecto->ID, '_pt_direccion', true),
            'fecha_inicio' => get_post_meta($proyecto->ID, '_pt_fecha_inicio', true),
            'fecha_fin' => get_post_meta($proyecto->ID, '_pt_fecha_fin', true),
            'estado' => get_post_meta($proyecto->ID, '_pt_estado', true),
            'thumbnail' => self::getProyectoThumbnail($proyecto->ID),
            'imagenes' => get_post_meta($proyecto->ID, '_pt_imagenes', true),
            'clientes' => self::getProyectoClientes($proyecto->ID),
            'hitos_count' => self::getProyectoHitosCount($proyecto->ID)
        );
        
        return $data;
    }
    
    /**
     * Obtener thumbnail del proyecto
     */
    private static function getProyectoThumbnail($proyecto_id) {
        // Primero intentar con el thumbnail destacado
        $thumbnail = get_the_post_thumbnail_url($proyecto_id, 'large');
        
        if ($thumbnail) {
            return $thumbnail;
        }
        
        // Si no, obtener la primera imagen de la galería
        $imagenes = get_post_meta($proyecto_id, '_pt_imagenes', true);
        
        if (is_array($imagenes) && !empty($imagenes)) {
            $img_url = wp_get_attachment_image_url($imagenes[0], 'large');
            if ($img_url) {
                return $img_url;
            }
        }
        
        // Si no hay nada, devolver placeholder
        return PT_PLUGIN_URL . 'assets/images/placeholder.jpg';
    }
    
    /**
     * Obtener clientes asignados al proyecto
     */
    private static function getProyectoClientes($proyecto_id) {
        global $wpdb;
        $table_rel = $wpdb->prefix . 'pt_user_proyecto';
        $table_users = $wpdb->prefix . 'pt_users';
        
        $clientes = $wpdb->get_results($wpdb->prepare(
            "SELECT u.* 
             FROM $table_users u
             INNER JOIN $table_rel ur ON u.id = ur.user_id
             WHERE ur.proyecto_id = %d AND u.active = 1
             ORDER BY u.nombre, u.apellidos",
            $proyecto_id
        ));
        
        return $clientes;
    }
    
    /**
     * Obtener número de hitos del proyecto
     */
    private static function getProyectoHitosCount($proyecto_id) {
        $hitos = PT_Hito::getHitosProyecto($proyecto_id);
        return count($hitos);
    }
    
    /**
     * Verificar si el usuario tiene acceso al proyecto
     */
    public static function userCanAccessProyecto($user_id, $proyecto_id) {
        // Los admins tienen acceso a todo
        if (PT_Roles::isAdmin($user_id)) {
            return true;
        }
        
        // Los clientes solo a sus proyectos asignados
        global $wpdb;
        $table_rel = $wpdb->prefix . 'pt_user_proyecto';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_rel 
             WHERE user_id = %d AND proyecto_id = %d",
            $user_id,
            $proyecto_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Obtener estadísticas del usuario
     */
    public static function getUserStats($user_id) {
        $proyectos = self::getProyectosUsuario($user_id);
        
        $stats = array(
            'total_proyectos' => count($proyectos),
            'en_proceso' => 0,
            'finalizados' => 0,
            'pendientes' => 0,
            'total_hitos' => 0
        );
        
        foreach ($proyectos as $proyecto) {
            $estado = get_post_meta($proyecto->ID, '_pt_estado', true);
            
            switch ($estado) {
                case 'en_proceso':
                    $stats['en_proceso']++;
                    break;
                case 'finalizado':
                    $stats['finalizados']++;
                    break;
                case 'pendiente':
                    $stats['pendientes']++;
                    break;
            }
            
            $stats['total_hitos'] += self::getProyectoHitosCount($proyecto->ID);
        }
        
        return $stats;
    }
    
    /**
     * Obtener proyectos recientes del usuario
     */
    public static function getProyectosRecientes($user_id, $limit = 5) {
        if (PT_Roles::isAdmin($user_id)) {
            $args = array(
                'post_type' => 'pt_proyecto',
                'posts_per_page' => $limit,
                'post_status' => 'publish',
                'orderby' => 'modified',
                'order' => 'DESC'
            );
            
            return get_posts($args);
        }
        
        $proyectos = PT_Proyecto::getProyectosCliente($user_id);
        
        // Ordenar por fecha de modificación
        usort($proyectos, function($a, $b) {
            return strtotime($b->post_modified) - strtotime($a->post_modified);
        });
        
        return array_slice($proyectos, 0, $limit);
    }
}