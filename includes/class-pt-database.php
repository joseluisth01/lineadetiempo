<?php
/**
 * Gestión de base de datos
 * 
 * Ubicación: includes/class-pt-database.php
 */

if (!defined('ABSPATH')) exit;

class PT_Database {
    
    /**
     * Crear tablas personalizadas
     */
    public static function createTables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabla de usuarios personalizados
        $table_users = $wpdb->prefix . 'pt_users';
        $sql_users = "CREATE TABLE IF NOT EXISTS $table_users (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            username varchar(60) NOT NULL,
            email varchar(100) NOT NULL,
            password varchar(255) NOT NULL,
            nombre varchar(100) NOT NULL,
            apellidos varchar(100),
            role varchar(20) NOT NULL DEFAULT 'cliente',
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            created_by bigint(20),
            last_login datetime,
            PRIMARY KEY (id),
            UNIQUE KEY username (username),
            UNIQUE KEY email (email)
        ) $charset_collate;";
        
        // Tabla de relación usuario-proyecto
        $table_user_proyecto = $wpdb->prefix . 'pt_user_proyecto';
        $sql_user_proyecto = "CREATE TABLE IF NOT EXISTS $table_user_proyecto (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            proyecto_id bigint(20) NOT NULL,
            assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
            assigned_by bigint(20),
            PRIMARY KEY (id),
            UNIQUE KEY user_proyecto (user_id, proyecto_id)
        ) $charset_collate;";
        
        // Tabla de documentos
        $table_documents = $wpdb->prefix . 'pt_documents';
        $sql_documents = "CREATE TABLE IF NOT EXISTS $table_documents (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            proyecto_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            file_url varchar(500) NOT NULL,
            file_type varchar(50),
            file_size bigint(20),
            uploaded_by bigint(20),
            uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Tabla de log de auditoría
        $table_audit = $wpdb->prefix . 'pt_audit_log';
        $sql_audit = "CREATE TABLE IF NOT EXISTS $table_audit (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20),
            action varchar(50) NOT NULL,
            entity_type varchar(50),
            entity_id bigint(20),
            description text,
            ip_address varchar(45),
            user_agent varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Tabla de notificaciones
        $table_notifications = $wpdb->prefix . 'pt_notifications';
        $sql_notifications = "CREATE TABLE IF NOT EXISTS $table_notifications (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            message text,
            link varchar(500),
            is_read tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY is_read (is_read)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_users);
        dbDelta($sql_user_proyecto);
        dbDelta($sql_documents);
        dbDelta($sql_audit);
        dbDelta($sql_notifications);
    }
    
    /**
     * Eliminar tablas (para desinstalación completa)
     */
    public static function dropTables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'pt_users',
            $wpdb->prefix . 'pt_user_proyecto',
            $wpdb->prefix . 'pt_documents',
            $wpdb->prefix . 'pt_audit_log',
            $wpdb->prefix . 'pt_notifications'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
}