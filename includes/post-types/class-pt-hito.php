<?php
/**
 * Custom Post Type: Hito
 * 
 * Ubicación: includes/post-types/class-pt-hito.php
 */

if (!defined('ABSPATH')) exit;

class PT_Hito {
    
    public static function init() {
        add_action('init', array(__CLASS__, 'registerPostType'));
        add_action('add_meta_boxes', array(__CLASS__, 'addMetaBoxes'));
        add_action('save_post_pt_hito', array(__CLASS__, 'saveMetaBoxes'), 10, 2);
    }
    
    /**
     * Registrar Custom Post Type
     */
    public static function registerPostType() {
        $labels = array(
            'name' => 'Hitos',
            'singular_name' => 'Hito',
            'menu_name' => 'Hitos',
            'add_new' => 'Añadir Hito',
            'add_new_item' => 'Añadir Nuevo Hito',
            'edit_item' => 'Editar Hito',
            'new_item' => 'Nuevo Hito',
            'view_item' => 'Ver Hito',
            'search_items' => 'Buscar Hitos',
            'not_found' => 'No se encontraron hitos',
            'not_found_in_trash' => 'No hay hitos en la papelera'
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-flag',
            'capability_type' => 'post',
            'supports' => array('title', 'editor'),
            'has_archive' => false,
            'rewrite' => false,
            'menu_position' => 6,
        );
        
        register_post_type('pt_hito', $args);
    }
    
    /**
     * Añadir meta boxes
     */
    public static function addMetaBoxes() {
        add_meta_box(
            'pt_hito_datos',
            'Datos del Hito',
            array(__CLASS__, 'renderDatosMetaBox'),
            'pt_hito',
            'normal',
            'high'
        );
        
        add_meta_box(
            'pt_hito_imagenes',
            'Imágenes del Hito (Carrusel)',
            array(__CLASS__, 'renderImagenesMetaBox'),
            'pt_hito',
            'normal',
            'default'
        );
    }
    
    /**
     * Render meta box de datos
     */
    public static function renderDatosMetaBox($post) {
        wp_nonce_field('pt_hito_nonce', 'pt_hito_nonce_field');
        
        $proyecto_id = get_post_meta($post->ID, '_pt_proyecto_id', true);
        $fecha = get_post_meta($post->ID, '_pt_fecha', true);
        $estado = get_post_meta($post->ID, '_pt_estado', true);
        
        // Obtener todos los proyectos
        $proyectos = get_posts(array(
            'post_type' => 'pt_proyecto',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="pt_proyecto_id">Proyecto</label></th>
                <td>
                    <select id="pt_proyecto_id" name="pt_proyecto_id" required style="width: 100%; max-width: 400px;">
                        <option value="">Seleccionar Proyecto</option>
                        <?php foreach ($proyectos as $proyecto): ?>
                            <option value="<?php echo $proyecto->ID; ?>" 
                                    <?php selected($proyecto_id, $proyecto->ID); ?>>
                                <?php echo esc_html($proyecto->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="pt_fecha">Fecha del Hito</label></th>
                <td>
                    <input type="date" id="pt_fecha" name="pt_fecha" 
                           value="<?php echo esc_attr($fecha); ?>" required />
                </td>
            </tr>
            <tr>
                <th><label for="pt_estado">Estado</label></th>
                <td>
                    <select id="pt_estado" name="pt_estado" required>
                        <option value="pendiente" <?php selected($estado, 'pendiente'); ?>>Pendiente</option>
                        <option value="en_proceso" <?php selected($estado, 'en_proceso'); ?>>En Proceso</option>
                        <option value="finalizado" <?php selected($estado, 'finalizado'); ?>>Finalizado</option>
                    </select>
                    <p class="description">
                        Pendiente (gris #EDEDED) | En Proceso (amarillo #FDC425) | Finalizado (amarillo claro #FFDE88)
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render meta box de imágenes
     */
    public static function renderImagenesMetaBox($post) {
        $imagenes = get_post_meta($post->ID, '_pt_hito_imagenes', true);
        if (!is_array($imagenes)) {
            $imagenes = array();
        }
        ?>
        <p class="description">Añade entre 5-7 imágenes para el carrusel del modal</p>
        <div id="pt-hito-galeria-container">
            <div id="pt-hito-galeria-imagenes" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
                <?php foreach ($imagenes as $imagen_id): 
                    $imagen_url = wp_get_attachment_image_url($imagen_id, 'thumbnail');
                    if ($imagen_url):
                ?>
                    <div class="pt-hito-imagen-item" style="position: relative; width: 100px; height: 100px;">
                        <img src="<?php echo esc_url($imagen_url); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;">
                        <input type="hidden" name="pt_hito_imagenes[]" value="<?php echo esc_attr($imagen_id); ?>">
                        <button type="button" class="pt-remove-hito-imagen" 
                                style="position: absolute; top: 2px; right: 2px; background: #dc3232; color: white; border: none; cursor: pointer; width: 22px; height: 22px; border-radius: 50%; font-weight: bold;">
                            ×
                        </button>
                    </div>
                <?php 
                    endif;
                endforeach; ?>
            </div>
            <button type="button" id="pt-add-hito-imagenes" class="button button-primary">Añadir Imágenes al Carrusel</button>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var mediaUploader;
            
            $('#pt-add-hito-imagenes').on('click', function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: 'Seleccionar Imágenes para el Hito',
                    button: { text: 'Añadir Imágenes' },
                    multiple: true
                });
                
                mediaUploader.on('select', function() {
                    var attachments = mediaUploader.state().get('selection').toJSON();
                    attachments.forEach(function(attachment) {
                        $('#pt-hito-galeria-imagenes').append(
                            '<div class="pt-hito-imagen-item" style="position: relative; width: 100px; height: 100px;">' +
                            '<img src="' + attachment.sizes.thumbnail.url + '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;">' +
                            '<input type="hidden" name="pt_hito_imagenes[]" value="' + attachment.id + '">' +
                            '<button type="button" class="pt-remove-hito-imagen" style="position: absolute; top: 2px; right: 2px; background: #dc3232; color: white; border: none; cursor: pointer; width: 22px; height: 22px; border-radius: 50%; font-weight: bold;">×</button>' +
                            '</div>'
                        );
                    });
                });
                
                mediaUploader.open();
            });
            
            $(document).on('click', '.pt-remove-hito-imagen', function() {
                $(this).closest('.pt-hito-imagen-item').remove();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Guardar meta boxes
     */
    public static function saveMetaBoxes($post_id, $post) {
        // Verificar nonce
        if (!isset($_POST['pt_hito_nonce_field']) || 
            !wp_verify_nonce($_POST['pt_hito_nonce_field'], 'pt_hito_nonce')) {
            return;
        }
        
        // Verificar autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        $old_proyecto_id = get_post_meta($post_id, '_pt_proyecto_id', true);
        $is_new_hito = ($post->post_date === $post->post_modified);
        
        // Guardar campos
        if (isset($_POST['pt_proyecto_id'])) {
            update_post_meta($post_id, '_pt_proyecto_id', intval($_POST['pt_proyecto_id']));
        }
        
        if (isset($_POST['pt_fecha'])) {
            update_post_meta($post_id, '_pt_fecha', sanitize_text_field($_POST['pt_fecha']));
        }
        
        if (isset($_POST['pt_estado'])) {
            update_post_meta($post_id, '_pt_estado', sanitize_text_field($_POST['pt_estado']));
        }
        
        // Guardar imágenes
        if (isset($_POST['pt_hito_imagenes'])) {
            $imagenes = array_map('intval', $_POST['pt_hito_imagenes']);
            update_post_meta($post_id, '_pt_hito_imagenes', $imagenes);
        } else {
            delete_post_meta($post_id, '_pt_hito_imagenes');
        }
        
        // Log de auditoría
        PT_Audit_Log::log(
            PT_Auth::getCurrentUserId(),
            $is_new_hito ? 'create_hito' : 'update_hito',
            'hito',
            $post_id,
            'Hito ' . ($is_new_hito ? 'creado' : 'actualizado')
        );
        
        // Enviar notificación si es nuevo o ha cambiado de proyecto
        if ($is_new_hito || ($old_proyecto_id != $_POST['pt_proyecto_id'])) {
            self::notificarClientesNuevoHito($post_id, intval($_POST['pt_proyecto_id']));
        }
    }
    
    /**
     * Notificar a clientes sobre nuevo hito
     */
    private static function notificarClientesNuevoHito($hito_id, $proyecto_id) {
        global $wpdb;
        $table_rel = $wpdb->prefix . 'pt_user_proyecto';
        
        // Obtener clientes del proyecto
        $clientes = $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM $table_rel WHERE proyecto_id = %d",
            $proyecto_id
        ));
        
        $hito = get_post($hito_id);
        $proyecto = get_post($proyecto_id);
        
        foreach ($clientes as $cliente_id) {
            PT_Notifications::createNotification(
                $cliente_id,
                'new_hito',
                'Nuevo avance en tu proyecto',
                'Se ha añadido un nuevo hito en el proyecto: ' . $proyecto->post_title,
                home_url('/proyecto/' . $proyecto_id)
            );
        }
    }
    
    /**
     * Obtener hitos de un proyecto ordenados cronológicamente
     */
    public static function getHitosProyecto($proyecto_id) {
        $args = array(
            'post_type' => 'pt_hito',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_pt_proyecto_id',
                    'value' => $proyecto_id,
                    'compare' => '='
                )
            ),
            'meta_key' => '_pt_fecha',
            'orderby' => 'meta_value',
            'order' => 'ASC'
        );
        
        return get_posts($args);
    }
}