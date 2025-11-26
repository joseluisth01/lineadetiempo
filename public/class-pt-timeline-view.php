<?php
/**
 * Vista de Timeline del Proyecto
 * 
 * Ubicación: public/class-pt-timeline-view.php
 */

if (!defined('ABSPATH')) exit;

class PT_Timeline_View {
    
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new PT_Timeline_View();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Constructor vacío
    }
    
    /**
     * Renderizar timeline del proyecto
     */
    public static function render($proyecto_id) {
        if (!PT_Auth::isLoggedIn()) {
            wp_redirect(home_url('/login-proyectos'));
            exit;
        }
        
        $user_id = PT_Auth::getCurrentUserId();
        
        // Verificar permisos
        if (!self::userCanAccessProyecto($user_id, $proyecto_id)) {
            wp_die('No tienes permisos para ver este proyecto.', 'Acceso Denegado', array('response' => 403));
        }
        
        // Cargar template
        include PT_PLUGIN_DIR . 'templates/timeline.php';
    }
    
    /**
     * Verificar si el usuario puede acceder al proyecto
     */
    public static function userCanAccessProyecto($user_id, $proyecto_id) {
        return PT_Proyectos_View::userCanAccessProyecto($user_id, $proyecto_id);
    }
    
    /**
     * Obtener datos del proyecto para timeline
     */
    public static function getProyectoTimeline($proyecto_id) {
        $proyecto = get_post($proyecto_id);
        
        if (!$proyecto || $proyecto->post_type !== 'pt_proyecto') {
            return null;
        }
        
        $fecha_inicio = get_post_meta($proyecto_id, '_pt_fecha_inicio', true);
        $fecha_fin = get_post_meta($proyecto_id, '_pt_fecha_fin', true);
        $hitos = PT_Hito::getHitosProyecto($proyecto_id);
        
        // Calcular si hay extensión del proyecto
        $fecha_max_hito = $fecha_fin;
        foreach ($hitos as $hito) {
            $fecha_hito = get_post_meta($hito->ID, '_pt_fecha', true);
            if ($fecha_hito > $fecha_max_hito) {
                $fecha_max_hito = $fecha_hito;
            }
        }
        
        $tiene_extension = ($fecha_max_hito > $fecha_fin);
        $dias_extension = 0;
        
        if ($tiene_extension) {
            $date1 = new DateTime($fecha_fin);
            $date2 = new DateTime($fecha_max_hito);
            $dias_extension = $date2->diff($date1)->days;
        }
        
        $data = array(
            'proyecto' => $proyecto,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'hitos' => self::processHitos($hitos),
            'tiene_extension' => $tiene_extension,
            'dias_extension' => $dias_extension,
            'documentos' => self::getProyectoDocumentos($proyecto_id),
            'progreso' => self::calcularProgreso($proyecto_id),
            'clientes' => PT_Proyectos_View::getProyectoData($proyecto_id)['clientes']
        );
        
        return $data;
    }
    
    /**
     * Procesar hitos para la visualización
     */
    private static function processHitos($hitos) {
        $processed = array();
        
        foreach ($hitos as $index => $hito) {
            $hito_data = array(
                'id' => $hito->ID,
                'titulo' => $hito->post_title,
                'descripcion' => wpautop($hito->post_content),
                'fecha' => get_post_meta($hito->ID, '_pt_fecha', true),
                'estado' => get_post_meta($hito->ID, '_pt_estado', true),
                'imagenes' => self::getHitoImagenes($hito->ID),
                'numero' => $index + 1,
                'primera_imagen' => self::getHitoPrimeraImagen($hito->ID)
            );
            
            $processed[] = $hito_data;
        }
        
        return $processed;
    }
    
    /**
     * Obtener imágenes de un hito
     */
    private static function getHitoImagenes($hito_id) {
        $imagenes_ids = get_post_meta($hito_id, '_pt_hito_imagenes', true);
        $imagenes = array();
        
        if (is_array($imagenes_ids)) {
            foreach ($imagenes_ids as $img_id) {
                $img_url = wp_get_attachment_image_url($img_id, 'large');
                if ($img_url) {
                    $imagenes[] = $img_url;
                }
            }
        }
        
        return $imagenes;
    }
    
    /**
     * Obtener primera imagen del hito
     */
    private static function getHitoPrimeraImagen($hito_id) {
        $imagenes_ids = get_post_meta($hito_id, '_pt_hito_imagenes', true);
        
        if (is_array($imagenes_ids) && !empty($imagenes_ids)) {
            return wp_get_attachment_image_url($imagenes_ids[0], 'thumbnail');
        }
        
        return '';
    }
    
    /**
     * Obtener documentos del proyecto
     */
    private static function getProyectoDocumentos($proyecto_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pt_documents';
        
        $documentos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE proyecto_id = %d ORDER BY uploaded_at DESC",
            $proyecto_id
        ));
        
        return $documentos;
    }
    
    /**
     * Calcular progreso del proyecto
     */
    private static function calcularProgreso($proyecto_id) {
        $hitos = PT_Hito::getHitosProyecto($proyecto_id);
        
        if (empty($hitos)) {
            return array(
                'total' => 0,
                'finalizados' => 0,
                'en_proceso' => 0,
                'pendientes' => 0,
                'porcentaje' => 0
            );
        }
        
        $total = count($hitos);
        $finalizados = 0;
        $en_proceso = 0;
        $pendientes = 0;
        
        foreach ($hitos as $hito) {
            $estado = get_post_meta($hito->ID, '_pt_estado', true);
            
            switch ($estado) {
                case 'finalizado':
                    $finalizados++;
                    break;
                case 'en_proceso':
                    $en_proceso++;
                    break;
                case 'pendiente':
                    $pendientes++;
                    break;
            }
        }
        
        $porcentaje = $total > 0 ? round(($finalizados / $total) * 100) : 0;
        
        return array(
            'total' => $total,
            'finalizados' => $finalizados,
            'en_proceso' => $en_proceso,
            'pendientes' => $pendientes,
            'porcentaje' => $porcentaje
        );
    }
    
    /**
     * Calcular posición del hito en la línea (porcentaje)
     */
    public static function calcularPosicionHito($fecha_hito, $fecha_inicio, $fecha_fin) {
        $timestamp_inicio = strtotime($fecha_inicio);
        $timestamp_fin = strtotime($fecha_fin);
        $timestamp_hito = strtotime($fecha_hito);
        
        // Si el hito está antes del inicio, posición 0%
        if ($timestamp_hito <= $timestamp_inicio) {
            return 2;
        }
        
        // Si el hito está después del fin, calcular con extensión
        if ($timestamp_hito > $timestamp_fin) {
            // Buscar el hito más lejano para calcular la extensión total
            $duracion_base = $timestamp_fin - $timestamp_inicio;
            $duracion_hito = $timestamp_hito - $timestamp_inicio;
            
            $porcentaje = ($duracion_hito / $duracion_base) * 100;
            
            // Limitar a máximo 98% para que no se salga
            return min(98, $porcentaje);
        }
        
        // Calcular posición normal
        $duracion_total = $timestamp_fin - $timestamp_inicio;
        $duracion_hito = $timestamp_hito - $timestamp_inicio;
        
        $porcentaje = ($duracion_hito / $duracion_total) * 100;
        
        // Asegurar que esté entre 2% y 98%
        return max(2, min(98, $porcentaje));
    }
    
    /**
     * Obtener color del estado
     */
    public static function getEstadoColor($estado) {
        $colores = array(
            'pendiente' => '#EDEDED',
            'en_proceso' => '#FDC425',
            'finalizado' => '#FFDE88'
        );
        
        return $colores[$estado] ?? '#EDEDED';
    }
    
    /**
     * Formatear fecha para visualización
     */
    public static function formatFecha($fecha, $formato = 'd/m/Y') {
        if (empty($fecha)) {
            return '';
        }
        
        return date($formato, strtotime($fecha));
    }
}