<?php
/**
 * Manejador de peticiones AJAX
 * 
 * Ubicación: includes/ajax/class-pt-ajax-handler.php
 */

if (!defined('ABSPATH')) exit;

class PT_Ajax_Handler {
    
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new PT_Ajax_Handler();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // AJAX para usuarios logueados
        add_action('wp_ajax_pt_get_hito_modal', array($this, 'getHitoModal'));
        add_action('wp_ajax_pt_upload_document', array($this, 'uploadDocument'));
        add_action('wp_ajax_pt_delete_document', array($this, 'deleteDocument'));
        add_action('wp_ajax_pt_mark_notification_read', array($this, 'markNotificationRead'));
        
        // AJAX para admin
        add_action('wp_ajax_pt_create_user', array($this, 'createUser'));
        add_action('wp_ajax_pt_delete_user', array($this, 'deleteUser'));
        add_action('wp_ajax_pt_assign_cliente', array($this, 'assignCliente'));
    }
    
    /**
     * Obtener datos del hito para el modal
     */
    public function getHitoModal() {
        check_ajax_referer('pt_nonce', 'nonce');
        
        if (!PT_Auth::isLoggedIn()) {
            wp_send_json_error('No autenticado');
        }
        
        $hito_id = intval($_POST['hito_id']);
        $hito = get_post($hito_id);
        
        if (!$hito || $hito->post_type !== 'pt_hito') {
            wp_send_json_error('Hito no encontrado');
        }
        
        // Verificar permisos
        $proyecto_id = get_post_meta($hito_id, '_pt_proyecto_id', true);
        if (!$this->canAccessProyecto($proyecto_id)) {
            wp_send_json_error('Sin permisos');
        }
        
        // Obtener datos
        $fecha = get_post_meta($hito_id, '_pt_fecha', true);
        $estado = get_post_meta($hito_id, '_pt_estado', true);
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
        
        // Obtener número de hito en el proyecto
        $hitos = PT_Hito::getHitosProyecto($proyecto_id);
        $numero = 0;
        foreach ($hitos as $index => $h) {
            if ($h->ID == $hito_id) {
                $numero = $index + 1;
                break;
            }
        }
        
        $data = array(
            'titulo' => $hito->post_title,
            'descripcion' => wpautop($hito->post_content),
            'fecha' => date('d/m/Y', strtotime($fecha)),
            'estado' => $estado,
            'imagenes' => $imagenes,
            'numero' => sprintf('%02d', $numero)
        );
        
        wp_send_json_success($data);
    }
    
    /**
     * Subir documento
     */
    public function uploadDocument() {
        check_ajax_referer('pt_admin_nonce', 'nonce');
        
        if (!PT_Roles::isAdmin(PT_Auth::getCurrentUserId())) {
            wp_send_json_error('Sin permisos');
        }
        
        if (!isset($_FILES['file']) || !isset($_POST['proyecto_id'])) {
            wp_send_json_error('Datos incompletos');
        }
        
        $proyecto_id = intval($_POST['proyecto_id']);
        $file = $_FILES['file'];
        
        // Subir archivo
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $upload = wp_handle_upload($file, array('test_form' => false));
        
        if (isset($upload['error'])) {
            wp_send_json_error($upload['error']);
        }
        
        // Guardar en BD
        global $wpdb;
        $table = $wpdb->prefix . 'pt_documents';
        
        $wpdb->insert($table, array(
            'proyecto_id' => $proyecto_id,
            'title' => sanitize_text_field($_POST['title']),
            'file_url' => $upload['url'],
            'file_type' => $upload['type'],
            'file_size' => filesize($upload['file']),
            'uploaded_by' => PT_Auth::getCurrentUserId(),
            'uploaded_at' => current_time('mysql')
        ));
        
        PT_Audit_Log::log(
            PT_Auth::getCurrentUserId(),
            'upload_document',
            'documento',
            $wpdb->insert_id,
            'Documento subido: ' . $_POST['title']
        );
        
        wp_send_json_success();
    }
    
    /**
     * Eliminar documento
     */
    public function deleteDocument() {
        check_ajax_referer('pt_admin_nonce', 'nonce');
        
        if (!PT_Roles::isAdmin(PT_Auth::getCurrentUserId())) {
            wp_send_json_error('Sin permisos');
        }
        
        $doc_id = intval($_POST['doc_id']);
        
        global $wpdb;
        $table = $wpdb->prefix . 'pt_documents';
        
        $wpdb->delete($table, array('id' => $doc_id));
        
        PT_Audit_Log::log(
            PT_Auth::getCurrentUserId(),
            'delete_document',
            'documento',
            $doc_id,
            'Documento eliminado'
        );
        
        wp_send_json_success();
    }
    
    /**
     * Marcar notificación como leída
     */
    public function markNotificationRead() {
        check_ajax_referer('pt_nonce', 'nonce');
        
        if (!PT_Auth::isLoggedIn()) {
            wp_send_json_error('No autenticado');
        }
        
        $notif_id = intval($_POST['notification_id']);
        PT_Notifications::markAsRead($notif_id);
        
        wp_send_json_success();
    }
    
    /**
     * Crear usuario (AJAX)
     */
    public function createUser() {
        check_ajax_referer('pt_admin_nonce', 'nonce');
        
        if (!PT_Roles::isAdmin(PT_Auth::getCurrentUserId())) {
            wp_send_json_error('Sin permisos');
        }
        
        $result = PT_Auth::createUser($_POST);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Eliminar usuario
     */
    public function deleteUser() {
        check_ajax_referer('pt_admin_nonce', 'nonce');
        
        if (!PT_Roles::isSuperAdmin(PT_Auth::getCurrentUserId())) {
            wp_send_json_error('Sin permisos');
        }
        
        $user_id = intval($_POST['user_id']);
        
        global $wpdb;
        $table = $wpdb->prefix . 'pt_users';
        
        $wpdb->update($table, 
            array('active' => 0),
            array('id' => $user_id)
        );
        
        PT_Audit_Log::log(
            PT_Auth::getCurrentUserId(),
            'delete_user',
            'user',
            $user_id,
            'Usuario desactivado'
        );
        
        wp_send_json_success();
    }
    
    /**
     * Verificar si el usuario puede acceder al proyecto
     */
    private function canAccessProyecto($proyecto_id) {
        $user_id = PT_Auth::getCurrentUserId();
        
        // Admin puede ver todo
        if (PT_Roles::isAdmin($user_id)) {
            return true;
        }
        
        // Cliente solo sus proyectos
        global $wpdb;
        $table = $wpdb->prefix . 'pt_user_proyecto';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d AND proyecto_id = %d",
            $user_id,
            $proyecto_id
        ));
        
        return $count > 0;
    }
}