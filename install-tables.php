<?php
/**
 * Script de instalación forzada de tablas
 * 
 * INSTRUCCIONES:
 * 1. Sube este archivo a la raíz del plugin (junto a plugin-proyectos.php)
 * 2. Accede a: https://tudominio.com/wp-content/plugins/plugin-proyectos/install-tables.php
 * 3. Una vez ejecutado, BORRA este archivo por seguridad
 */

// Cargar WordPress
require_once('../../../wp-load.php');

// Verificar que sea admin de WordPress
if (!current_user_can('administrator')) {
    die('Acceso denegado');
}

global $wpdb;

echo "<h1>Instalación de Tablas - Sistema de Gestión de Proyectos</h1>";
echo "<p>Iniciando instalación...</p>";

// Tabla de usuarios
$table_users = $wpdb->prefix . 'gp_users';
$sql = "CREATE TABLE IF NOT EXISTS $table_users (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    nombre varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    username varchar(100) NOT NULL,
    password varchar(255) NOT NULL,
    user_type enum('super_admin','admin','cliente') NOT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    created_by bigint(20) DEFAULT NULL,
    last_login datetime DEFAULT NULL,
    status enum('active','inactive') DEFAULT 'active',
    PRIMARY KEY (id),
    UNIQUE KEY username (username),
    UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$result = $wpdb->query($sql);
echo $result !== false ? "✅ Tabla de usuarios creada<br>" : "❌ Error al crear tabla de usuarios<br>";

// Tabla de proyectos
$table_projects = $wpdb->prefix . 'gp_projects';
$sql = "CREATE TABLE IF NOT EXISTS $table_projects (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    nombre varchar(255) NOT NULL,
    direccion text,
    fecha_inicio date NOT NULL,
    fecha_fin date NOT NULL,
    fecha_fin_real date DEFAULT NULL,
    imagen_principal varchar(255),
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    created_by bigint(20) DEFAULT NULL,
    updated_at datetime DEFAULT NULL,
    updated_by bigint(20) DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$result = $wpdb->query($sql);
echo $result !== false ? "✅ Tabla de proyectos creada<br>" : "❌ Error al crear tabla de proyectos<br>";

// Tabla de asignación proyectos-usuarios
$table_project_users = $wpdb->prefix . 'gp_project_users';
$sql = "CREATE TABLE IF NOT EXISTS $table_project_users (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    project_id bigint(20) NOT NULL,
    user_id bigint(20) NOT NULL,
    assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
    assigned_by bigint(20) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY project_user (project_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$result = $wpdb->query($sql);
echo $result !== false ? "✅ Tabla de asignación proyectos-usuarios creada<br>" : "❌ Error<br>";

// Tabla de hitos
$table_milestones = $wpdb->prefix . 'gp_milestones';
$sql = "CREATE TABLE IF NOT EXISTS $table_milestones (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    project_id bigint(20) NOT NULL,
    titulo varchar(255) NOT NULL,
    fecha date NOT NULL,
    descripcion text,
    estado enum('pendiente','en_proceso','finalizada') DEFAULT 'pendiente',
    icono varchar(255),
    orden int(11) DEFAULT 0,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    created_by bigint(20) DEFAULT NULL,
    updated_at datetime DEFAULT NULL,
    updated_by bigint(20) DEFAULT NULL,
    PRIMARY KEY (id),
    KEY project_id (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$result = $wpdb->query($sql);
echo $result !== false ? "✅ Tabla de hitos creada<br>" : "❌ Error<br>";

// Tabla de imágenes de hitos
$table_milestone_images = $wpdb->prefix . 'gp_milestone_images';
$sql = "CREATE TABLE IF NOT EXISTS $table_milestone_images (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    milestone_id bigint(20) NOT NULL,
    imagen varchar(255) NOT NULL,
    orden int(11) DEFAULT 0,
    PRIMARY KEY (id),
    KEY milestone_id (milestone_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$result = $wpdb->query($sql);
echo $result !== false ? "✅ Tabla de imágenes de hitos creada<br>" : "❌ Error<br>";

// Tabla de documentos
$table_documents = $wpdb->prefix . 'gp_documents';
$sql = "CREATE TABLE IF NOT EXISTS $table_documents (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    project_id bigint(20) NOT NULL,
    nombre varchar(255) NOT NULL,
    archivo varchar(255) NOT NULL,
    tipo varchar(100),
    uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
    uploaded_by bigint(20) DEFAULT NULL,
    PRIMARY KEY (id),
    KEY project_id (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$result = $wpdb->query($sql);
echo $result !== false ? "✅ Tabla de documentos creada<br>" : "❌ Error<br>";

// Tabla de auditoría
$table_audit = $wpdb->prefix . 'gp_audit_log';
$sql = "CREATE TABLE IF NOT EXISTS $table_audit (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    accion varchar(100) NOT NULL,
    tabla varchar(100),
    registro_id bigint(20),
    detalles text,
    ip_address varchar(45),
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$result = $wpdb->query($sql);
echo $result !== false ? "✅ Tabla de auditoría creada<br>" : "❌ Error<br>";

// Crear super admin
$exists = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table_users WHERE username = %s",
    'administrador'
));

if ($exists == 0) {
    $result = $wpdb->insert($table_users, [
        'nombre' => 'Super Administrador',
        'email' => get_option('admin_email'),
        'username' => 'administrador',
        'password' => password_hash('adminproyectos', PASSWORD_DEFAULT),
        'user_type' => 'super_admin',
        'status' => 'active'
    ]);
    
    echo $result !== false ? "✅ Super administrador creado<br>" : "❌ Error al crear super admin<br>";
} else {
    echo "ℹ️ Super administrador ya existe<br>";
}

echo "<hr>";
echo "<h2>✅ Instalación completada</h2>";
echo "<p><strong>Credenciales:</strong></p>";
echo "<ul>";
echo "<li>Usuario: <strong>administrador</strong></li>";
echo "<li>Contraseña: <strong>adminproyectos</strong></li>";
echo "</ul>";
echo "<p><a href='" . home_url('/login-proyectos/') . "'>Ir al login</a></p>";
echo "<hr>";
echo "<p style='color: red;'><strong>IMPORTANTE:</strong> Por seguridad, borra este archivo (install-tables.php) ahora mismo.</p>";
?>