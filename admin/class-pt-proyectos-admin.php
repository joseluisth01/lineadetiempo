<?php
/**
 * Gestión de Proyectos en Admin
 * 
 * Ubicación: admin/class-pt-proyectos-admin.php
 */

if (!defined('ABSPATH')) exit;

class PT_Proyectos_Admin {
    
    public function __construct() {
        add_filter('manage_pt_proyecto_posts_columns', array($this, 'customColumns'));
        add_action('manage_pt_proyecto_posts_custom_column', array($this, 'customColumnContent'), 10, 2);
        add_filter('manage_edit-pt_proyecto_sortable_columns', array($this, 'sortableColumns'));
    }
    
    /**
     * Columnas personalizadas
     */
    public function customColumns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = 'Título';
        $new_columns['direccion'] = 'Dirección';
        $new_columns['fechas'] = 'Fechas';
        $new_columns['estado'] = 'Estado';
        $new_columns['clientes'] = 'Clientes';
        $new_columns['hitos'] = 'Hitos';
        $new_columns['date'] = 'Creado';
        
        return $new_columns;
    }
    
    /**
     * Contenido de columnas personalizadas
     */
    public function customColumnContent($column, $post_id) {
        switch ($column) {
            case 'direccion':
                $direccion = get_post_meta($post_id, '_pt_direccion', true);
                echo $direccion ? esc_html($direccion) : '—';
                break;
                
            case 'fechas':
                $fecha_inicio = get_post_meta($post_id, '_pt_fecha_inicio', true);
                $fecha_fin = get_post_meta($post_id, '_pt_fecha_fin', true);
                
                if ($fecha_inicio && $fecha_fin) {
                    echo '<strong>Inicio:</strong> ' . date('d/m/Y', strtotime($fecha_inicio)) . '<br>';
                    echo '<strong>Fin:</strong> ' . date('d/m/Y', strtotime($fecha_fin));
                    
                    // Calcular duración
                    $diff = abs(strtotime($fecha_fin) - strtotime($fecha_inicio));
                    $dias = floor($diff / (60*60*24));
                    echo '<br><small style="color: #666;">' . $dias . ' días</small>';
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
                
            case 'clientes':
                global $wpdb;
                $table_rel = $wpdb->prefix . 'pt_user_proyecto';
                $table_users = $wpdb->prefix . 'pt_users';
                
                $clientes = $wpdb->get_results($wpdb->prepare(
                    "SELECT u.nombre, u.apellidos 
                     FROM $table_users u
                     INNER JOIN $table_rel ur ON u.id = ur.user_id
                     WHERE ur.proyecto_id = %d AND u.active = 1
                     LIMIT 3",
                    $post_id
                ));
                
                if (!empty($clientes)) {
                    foreach ($clientes as $cliente) {
                        echo '<div style="margin-bottom: 3px;">👤 ' . esc_html($cliente->nombre . ' ' . $cliente->apellidos) . '</div>';
                    }
                    
                    $total = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $table_rel WHERE proyecto_id = %d",
                        $post_id
                    ));
                    
                    if ($total > 3) {
                        echo '<small style="color: #666;">+' . ($total - 3) . ' más</small>';
                    }
                } else {
                    echo '<span style="color: #999;">Sin clientes</span>';
                }
                break;
                
            case 'hitos':
                $hitos = PT_Hito::getHitosProyecto($post_id);
                $total = count($hitos);
                
                if ($total > 0) {
                    // Contar por estado
                    $finalizados = 0;
                    $en_proceso = 0;
                    $pendientes = 0;
                    
                    foreach ($hitos as $hito) {
                        $estado = get_post_meta($hito->ID, '_pt_estado', true);
                        if ($estado === 'finalizado') $finalizados++;
                        elseif ($estado === 'en_proceso') $en_proceso++;
                        else $pendientes++;
                    }
                    
                    echo '<strong>' . $total . ' hitos</strong><br>';
                    echo '<small style="color: #FFDE88;">✓ ' . $finalizados . '</small> / ';
                    echo '<small style="color: #FDC425;">⟳ ' . $en_proceso . '</small> / ';
                    echo '<small style="color: #EDEDED;">○ ' . $pendientes . '</small>';
                } else {
                    echo '<span style="color: #999;">Sin hitos</span>';
                }
                break;
        }
    }
    
    /**
     * Columnas ordenables
     */
    public function sortableColumns($columns) {
        $columns['fechas'] = 'fecha_inicio';
        return $columns;
    }
}

new PT_Proyectos_Admin();