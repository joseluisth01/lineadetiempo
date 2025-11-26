<?php
/**
 * Sistema de Audit Log
 * 
 * Ubicación: includes/class-pt-audit-log.php
 */

if (!defined('ABSPATH')) exit;

class PT_Audit_Log {
    
    /**
     * Registrar acción en el log
     */
    public static function log($user_id, $action, $entity_type = '', $entity_id = 0, $description = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'pt_audit_log';
        
        return $wpdb->insert($table, array(
            'user_id' => $user_id,
            'action' => $action,
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'description' => $description,
            'ip_address' => self::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => current_time('mysql')
        ));
    }
    
    /**
     * Obtener logs con filtros
     */
    public static function getLogs($filters = array(), $limit = 50, $offset = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'pt_audit_log';
        $table_users = $wpdb->prefix . 'pt_users';
        
        $where = array('1=1');
        
        if (!empty($filters['user_id'])) {
            $where[] = $wpdb->prepare("a.user_id = %d", $filters['user_id']);
        }
        
        if (!empty($filters['action'])) {
            $where[] = $wpdb->prepare("a.action = %s", $filters['action']);
        }
        
        if (!empty($filters['entity_type'])) {
            $where[] = $wpdb->prepare("a.entity_type = %s", $filters['entity_type']);
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = $wpdb->prepare("DATE(a.created_at) >= %s", $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = $wpdb->prepare("DATE(a.created_at) <= %s", $filters['date_to']);
        }
        
        $where_sql = implode(' AND ', $where);
        
        $sql = "
            SELECT a.*, u.username, u.nombre, u.apellidos 
            FROM $table a
            LEFT JOIN $table_users u ON a.user_id = u.id
            WHERE $where_sql
            ORDER BY a.created_at DESC
            LIMIT $limit OFFSET $offset
        ";
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Obtener IP del cliente
     */
    private static function getClientIP() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        return $ip;
    }
    
    /**
     * Obtener logs de una entidad específica
     */
    public static function getEntityLogs($entity_type, $entity_id, $limit = 20) {
        global $wpdb;
        $table = $wpdb->prefix . 'pt_audit_log';
        $table_users = $wpdb->prefix . 'pt_users';
        
        $sql = $wpdb->prepare("
            SELECT a.*, u.username, u.nombre, u.apellidos 
            FROM $table a
            LEFT JOIN $table_users u ON a.user_id = u.id
            WHERE a.entity_type = %s AND a.entity_id = %d
            ORDER BY a.created_at DESC
            LIMIT %d
        ", $entity_type, $entity_id, $limit);
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Limpiar logs antiguos (más de X días)
     */
    public static function cleanOldLogs($days = 90) {
        global $wpdb;
        $table = $wpdb->prefix . 'pt_audit_log';
        
        $date = date('Y-m-d', strtotime("-$days days"));
        
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE DATE(created_at) < %s",
            $date
        ));
    }
}