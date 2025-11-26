<?php
/**
 * Custom Post Type: Proyecto
 * 
 * Ubicación: includes/post-types/class-pt-proyecto.php
 */

if (!defined('ABSPATH')) exit;

class PT_Proyecto {
    
    public static function init() {
        add_action('init', array(__CLASS__, 'registerPostType'));
        add_action('add_meta_boxes', array(__CLASS__, 'addMetaBoxes'));
        add_action('save_post_pt_proyecto', array(__CLASS__, 'saveMetaBoxes'));
    }
    
    /**
     * Registrar Custom Post Type
     */
    public static function registerPostType() {
        $labels = array(
            'name' => 'Proyectos',
            'singular_name' => 'Proyecto',
            'menu_name' => 'Proyectos',
            'add_new' => 'Añadir Proyecto',
            'add_new_item' => 'Añadir Nuevo Proyecto',
            'edit_item' => 'Editar Proyecto',
            'new_item' => 'Nuevo Proyecto',
            'view_item' => 'Ver Proyecto',
            'search_items' => 'Buscar Proyectos',
            'not_found' => 'No se encontraron proyectos',
            'not_found_in_trash' => 'No hay proyectos en la papelera'
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-building',
            'capability_type' => 'post',
            'supports' => array('title', 'thumbnail'),
            'has_archive' => false,
            'rewrite' => false,
            'menu_position' => 5,
        );
        
        register_post_type('pt_proyecto', $args);
    }
    
    /**
     * Añadir meta boxes
     */
    public static function addMetaBoxes() {
        add_meta_box(
            'pt_proyecto_datos',
            'Datos del Proyecto',
            array(__CLASS__, 'renderDatosMetaBox'),
            'pt_proyecto',
            'normal',
            'high'
        );
        
        add_meta_box(
            'pt_proyecto_clientes',
            'Clientes Asignados',
            array(__CLASS__, 'renderClientesMetaBox'),
            'pt_proyecto',
            'side',
            'default'
        );
        
        add_meta_box(
            'pt_proyecto_imagenes',
            'Galería del Proyecto',
            array(__CLASS__, 'renderImagenesMetaBox'),
            'pt_proyecto',
            'normal',
            'default'
        );
    }
    
    /**
     * Render meta box de datos
     */
    public static function renderDatosMetaBox($post) {
        wp_nonce_field('pt_proyecto_nonce', 'pt_proyecto_nonce_field');
        
        $direccion = get_post_meta($post->ID, '_pt_direccion', true);
        $fecha_inicio = get_post_meta($post->ID, '_pt_fecha_inicio', true);
        $fecha_fin = get_post_meta($post->ID, '_pt_fecha_fin', true);
        $estado = get_post_meta($post->ID, '_pt_estado', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="pt_direccion">Dirección</label></th>
                <td>
                    <input type="text" id="pt_direccion" name="pt_direccion" 
                           value="<?php echo esc_attr($direccion); ?>" 
                           class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="pt_fecha_inicio">Fecha de Inicio</label></th>
                <td>
                    <input type="date" id="pt_fecha_inicio" name="pt_fecha_inicio" 
                           value="<?php echo esc_attr($fecha_inicio); ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="pt_fecha_fin">Fecha de Fin Prevista</label></th>
                <td>
                    <input type="date" id="pt_fecha_fin" name="pt_fecha_fin" 
                           value="<?php echo esc_attr($fecha_fin); ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="pt_estado">Estado del Proyecto</label></th>
                <td>
                    <select id="pt_estado" name="pt_estado">
                        <option value="en_proceso" <?php selected($estado, 'en_proceso'); ?>>En Proceso</option>
                        <option value="finalizado" <?php selected($estado, 'finalizado'); ?>>Finalizado</option>
                        <option value="pendiente" <?php selected($estado, 'pendiente'); ?>>Pendiente</option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render meta box de clientes
     */
    public static function renderClientesMetaBox($post) {
        global $wpdb;
        $table_users = $wpdb->prefix . 'pt_users';
        $table_rel = $wpdb->prefix . 'pt_user_proyecto';
        
        // Obtener todos los clientes
        $clientes = $wpdb->get_results("SELECT * FROM $table_users WHERE role = 'cliente' AND active = 1");
        
        // Obtener clientes asignados
        $asignados = $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM $table_rel WHERE proyecto_id = %d",
            $post->ID
        ));
        
        ?>
        <div style="max-height: 300px; overflow-y: auto;">
            <?php foreach ($clientes as $cliente): ?>
                <label style="display: block; padding: 5px;">
                    <input type="checkbox" name="pt_clientes[]" 
                           value="<?php echo $cliente->id; ?>"
                           <?php checked(in_array($cliente->id, $asignados)); ?> />
                    <?php echo esc_html($cliente->nombre . ' ' . $cliente->apellidos . ' (' . $cliente->username . ')'); ?>
                </label>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render meta box de imágenes
     */
    public static function renderImagenesMetaBox($post) {
        $imagenes = get_post_meta($post->ID, '_pt_imagenes', true);
        if (!is_array($imagenes)) {
            $imagenes = array();
        }
        ?>
        <div id="pt-galeria-container">
            <div id="pt-galeria-imagenes" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
                <?php foreach ($imagenes as $index => $imagen_id): 
                    $imagen_url = wp_get_attachment_image_url($imagen_id, 'thumbnail');
                    if ($imagen_url):
                ?>
                    <div class="pt-imagen-item" style="position: relative; width: 100px; height: 100px;">
                        <img src="<?php echo esc_url($imagen_url); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <input type="hidden" name="pt_imagenes[]" value="<?php echo esc_attr($imagen_id); ?>">
                        <button type="button" class="pt-remove-imagen" 
                                style="position: absolute; top: 0; right: 0; background: red; color: white; border: none; cursor: pointer; width: 20px; height: 20px;">
                            ×
                        </button>
                    </div>
                <?php 
                    endif;
                endforeach; ?>
            </div>
            <button type="button" id="pt-add-imagenes" class="button">Añadir Imágenes</button>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var mediaUploader;
            
            $('#pt-add-imagenes').on('click', function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: 'Seleccionar Imágenes',
                    button: { text: 'Añadir al Proyecto' },
                    multiple: true
                });
                
                mediaUploader.on('select', function() {
                    var attachments = mediaUploader.state().get('selection').toJSON();
                    attachments.forEach(function(attachment) {
                        $('#pt-galeria-imagenes').append(
                            '<div class="pt-imagen-item" style="position: relative; width: 100px; height: 100px;">' +
                            '<img src="' + attachment.sizes.thumbnail.url + '" style="width: 100%; height: 100%; object-fit: cover;">' +
                            '<input type="hidden" name="pt_imagenes[]" value="' + attachment.id + '">' +
                            '<button type="button" class="pt-remove-imagen" style="position: absolute; top: 0; right: 0; background: red; color: white; border: none; cursor: pointer; width: 20px; height: 20px;">×</button>' +
                            '</div>'
                        );
                    });
                });
                
                mediaUploader.open();
            });
            
            $(document).on('click', '.pt-remove-imagen', function() {
                $(this).closest('.pt-imagen-item').remove();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Guardar meta boxes
     */
    public static function saveMetaBoxes($post_id) {
        // Verificar nonce
        if (!isset($_POST['pt_proyecto_nonce_field']) || 
            !wp_verify_nonce($_POST['pt_proyecto_nonce_field'], 'pt_proyecto_nonce')) {
            return;
        }
        
        // Verificar autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Guardar campos
        if (isset($_POST['pt_direccion'])) {
            update_post_meta($post_id, '_pt_direccion', sanitize_text_field($_POST['pt_direccion']));
        }
        
        if (isset($_POST['pt_fecha_inicio'])) {
            update_post_meta($post_id, '_pt_fecha_inicio', sanitize_text_field($_POST['pt_fecha_inicio']));
        }
        
        if (isset($_POST['pt_fecha_fin'])) {
            update_post_meta($post_id, '_pt_fecha_fin', sanitize_text_field($_POST['pt_fecha_fin']));
        }
        
        if (isset($_POST['pt_estado'])) {
            update_post_meta($post_id, '_pt_estado', sanitize_text_field($_POST['pt_estado']));
        }
        
        // Guardar imágenes
        if (isset($_POST['pt_imagenes'])) {
            $imagenes = array_map('intval', $_POST['pt_imagenes']);
            update_post_meta($post_id, '_pt_imagenes', $imagenes);
        } else {
            delete_post_meta($post_id, '_pt_imagenes');
        }
        
        // Guardar clientes asignados
        global $wpdb;
        $table_rel = $wpdb->prefix . 'pt_user_proyecto';
        
        // Eliminar asignaciones previas
        $wpdb->delete($table_rel, array('proyecto_id' => $post_id));
        
        // Añadir nuevas asignaciones
        if (isset($_POST['pt_clientes']) && is_array($_POST['pt_clientes'])) {
            foreach ($_POST['pt_clientes'] as $user_id) {
                $wpdb->insert($table_rel, array(
                    'user_id' => intval($user_id),
                    'proyecto_id' => $post_id,
                    'assigned_at' => current_time('mysql'),
                    'assigned_by' => PT_Auth::getCurrentUserId()
                ));
            }
        }
        
        // Log de auditoría
        PT_Audit_Log::log(
            PT_Auth::getCurrentUserId(),
            'update_proyecto',
            'proyecto',
            $post_id,
            'Proyecto actualizado'
        );
    }
    
    /**
     * Obtener proyectos de un cliente
     */
    public static function getProyectosCliente($user_id) {
        global $wpdb;
        $table_rel = $wpdb->prefix . 'pt_user_proyecto';
        
        $proyecto_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT proyecto_id FROM $table_rel WHERE user_id = %d",
            $user_id
        ));
        
        if (empty($proyecto_ids)) {
            return array();
        }
        
        $args = array(
            'post_type' => 'pt_proyecto',
            'post__in' => $proyecto_ids,
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        return get_posts($args);
    }
}