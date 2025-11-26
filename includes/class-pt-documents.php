<?php
/**
 * Gestión de Documentos
 * Ubicación: includes/class-pt-documents.php
 */

if (!defined('ABSPATH')) exit;

class PT_Documents {
    
    public static function getProyectoDocuments($proyecto_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pt_documents';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE proyecto_id = %d ORDER BY uploaded_at DESC",
            $proyecto_id
        ));
    }
    
    public static function deleteDocument($doc_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pt_documents';
        
        // Obtener URL del archivo antes de eliminar
        $doc = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $doc_id
        ));
        
        if ($doc) {
            // Eliminar archivo físico
            $upload_dir = wp_upload_dir();
            $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $doc->file_url);
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
            
            // Eliminar de BD
            $wpdb->delete($table, array('id' => $doc_id));
            return true;
        }
        
        return false;
    }
}