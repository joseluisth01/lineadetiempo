<?php
/**
 * Gestión de Hitos en Admin
 * 
 * Ubicación: admin/class-pt-hitos-admin.php
 */

if (!defined('ABSPATH')) exit;

class PT_Hitos_Admin {
    
    public function __construct() {
        add_filter('manage_pt_hito_posts_columns', array($this, 'customColumns'));
        add_action('manage_pt_hito_posts_custom_column', array($this, 'customColumnContent'), 10, 2);
        add_filter('manage_edit-pt_hito_sortable_columns', array($this, 'sortableColumns'));
        add_action('pre_get_posts', array($this, 'orderByDate'));
    }
    
    /**
     * Columnas personalizadas
     */
    public function customColumns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = 'Título';
        $new_columns['proyecto'] = 'Proyecto';
        $new_columns['fecha'] = 'Fecha';
        $new_columns['estado'] = 'Estado';
        $new_columns['imagenes'] = 'Imágenes';
        $new_columns['date'] = 'Creado';
        
        return $new_columns;
    }
    
    /**
     * Contenido de columnas personalizadas
     */
    public function customColumnContent($column, $post_id) {
        switch ($column) {
            case 'proyecto':
                $proyecto_id = get_post_meta($post_id, '_pt_proyecto_id', true);
                if ($proyecto_id) {
                    $proyecto = get_post($proyecto_id);
                    if ($proyecto) {
                        echo '<a href="' . admin_url('post.php?post=' . $proyecto_id . '&action=edit') . '">';
                        echo esc_html($proyecto->post_title);
                        echo '</a>';
                    } else {
                        echo '<span style="color: #dc3545;">Proyecto eliminado</span>';
                    }
                } else {
                    echo '—';
                }
                break;
                
            case 'fecha':
                $fecha = get_post_meta($post_id, '_pt_fecha', true);
                if ($fecha) {
                    echo '<strong>' . date('d/m/Y', strtotime($fecha)) . '</strong><br>';
                    
                    // Calcular días desde/hasta
                    $hoy = new DateTime();
                    $fecha_hito = new DateTime($fecha);
                    $diff = $hoy->diff($fecha_hito);
                    
                    if ($fecha_hito > $hoy) {
                        echo '<small style="color: #666;">En ' . $diff->days . ' días</small>';
                    } elseif ($fecha_hito < $hoy) {
                        echo '<small style="color: #999;">Hace ' . $diff->days . ' días</small>';
                    } else {
                        echo '<small style="color: #FDC425;">Hoy</small>';
                    }
                } else {
                    echo '—';
                }
                break;
                
            case 'estado':
                $estado = get_post_meta($post_id, '_pt_estado', true);
                
                $estados = array(
                    'pendiente' => '<span style="background: #EDEDED; padding: 4px 10px; border-radius: 10px; font-size: 11px; font-weight: 600;">PENDIENTE</span>',
                    'en_proceso' => '<span style="background: #FDC425; padding: 4px 10px; border-radius: 10px; font-size: 11px; font-weight: 600;">EN PROCESO</span>',
                    'finalizado' => '<span style="background: #FFDE88; padding: 4px 10px; border-radius: 10px; font-size: 11px; font-weight: 600;">FINALIZADO</span>'
                );
                
                echo $estados[$estado] ?? '—';
                break;
                
            case 'imagenes':
                $imagenes = get_post_meta($post_id, '_pt_hito_imagenes', true);
                
                if (is_array($imagenes) && !empty($imagenes)) {
                    echo '<div style="display: flex; gap: 5px;">';
                    $count = 0;
                    foreach ($imagenes as $img_id) {
                        if ($count >= 3) break;
                        $img_url = wp_get_attachment_image_url($img_id, 'thumbnail');
                        if ($img_url) {
                            echo '<img src="' . esc_url($img_url) . '" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">';
                            $count++;
                        }
                    }
                    echo '</div>';
                    
                    if (count($imagenes) > 3) {
                        echo '<small>+' . (count($imagenes) - 3) . ' más</small>';
                    }
                } else {
                    echo '<span style="color: #999;">Sin imágenes</span>';
                }
                break;
        }
    }
    
    /**
     * Columnas ordenables
     */
    public function sortableColumns($columns) {
        $columns['fecha'] = 'fecha';
        $columns['proyecto'] = 'proyecto';
        return $columns;
    }
    
    /**
     * Ordenar por fecha
     */
    public function orderByDate($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        if ($query->get('post_type') !== 'pt_hito') {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        if ($orderby === 'fecha') {
            $query->set('meta_key', '_pt_fecha');
            $query->set('orderby', 'meta_value');
        }
    }
}

new PT_Hitos_Admin();