<?php
/**
 * Script de desinstalación
 * Ubicación: uninstall.php
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Eliminar tablas
require_once plugin_dir_path(__FILE__) . 'includes/class-pt-database.php';
PT_Database::dropTables();

// Eliminar roles
require_once plugin_dir_path(__FILE__) . 'includes/class-pt-roles.php';
PT_Roles::removeRoles();

// Eliminar opciones
delete_option('pt_version');

// Eliminar posts de tipo pt_proyecto y pt_hito
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type IN ('pt_proyecto', 'pt_hito')");
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE post_id NOT IN (SELECT ID FROM {$wpdb->posts})");