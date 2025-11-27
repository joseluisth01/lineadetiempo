<?php
/**
 * Template de lista de proyectos
 * 
 * Ubicación: templates/proyectos-list.php
 */

if (!defined('ABSPATH')) exit;

$is_admin = in_array($user_role, array('super_admin', 'admin'));
$is_super_admin = $user_role === 'super_admin';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Proyectos - Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }
        
        /* Header */
        .header {
            background: white;
            border-bottom: 1px solid #e0e0e0;
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #333;
        }
        
        .logo span {
            color: #FDC425;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #FDC425;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #000;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #FDC425;
            color: #000;
        }
        
        .btn-primary:hover {
            background: #e5b020;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(253, 196, 37, 0.3);
        }
        
        .btn-secondary {
            background: white;
            color: #333;
            border: 2px solid #e0e0e0;
        }
        
        .btn-secondary:hover {
            border-color: #FDC425;
            color: #000;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        /* Admin Menu */
        .admin-menu {
            background: #667eea;
            color: white;
            padding: 15px 0;
        }
        
        .admin-menu-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .admin-menu a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s;
            font-size: 14px;
            font-weight: 600;
        }
        
        .admin-menu a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 30px;
        }
        
        .page-header {
            margin-bottom: 40px;
        }
        
        .page-header h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: #666;
            font-size: 16px;
        }
        
        /* Empty State */
        .empty-state {
            background: white;
            border-radius: 16px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .empty-state h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }
        
        .empty-state-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        /* Grid de proyectos */
        .proyectos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }
        
        .proyecto-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .proyecto-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        
        .proyecto-image {
            width: 100%;
            height: 200px;
            background: #f0f0f0;
            overflow: hidden;
        }
        
        .proyecto-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .proyecto-content {
            padding: 24px;
        }
        
        .proyecto-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }
        
        .proyecto-direccion {
            color: #666;
            font-size: 14px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .proyecto-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid #f0f0f0;
        }
        
        .proyecto-dates {
            font-size: 13px;
            color: #666;
        }
        
        .proyecto-hitos {
            background: #FDC425;
            color: #000;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }
        
        .icon {
            width: 16px;
            height: 16px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="logo">
                Portal <span>Proyectos</span>
            </div>
            
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user->nombre, 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600; font-size: 14px;"><?php echo esc_html($user->nombre . ' ' . $user->apellidos); ?></div>
                        <div style="font-size: 12px; color: #666;">
                            <?php 
                            if ($user_role === 'super_admin') echo 'Super Administrador';
                            elseif ($user_role === 'admin') echo 'Administrador';
                            else echo 'Cliente';
                            ?>
                        </div>
                    </div>
                </div>
                
                <a href="<?php echo home_url('?pt_logout=1'); ?>" class="btn btn-secondary">
                    Cerrar Sesión
                </a>
            </div>
        </div>
    </div>
    
    <!-- Admin Menu -->
    <?php if ($is_admin): ?>
    <div class="admin-menu">
        <div class="admin-menu-content">
            <strong>⚙️ Panel de Administración:</strong>
            <a href="<?php echo admin_url('edit.php?post_type=pt_proyecto'); ?>">📂 Gestionar Proyectos</a>
            <a href="<?php echo admin_url('edit.php?post_type=pt_proyecto&page=pt-usuarios'); ?>">👥 Gestionar Usuarios</a>
            <?php if ($is_super_admin): ?>
            <a href="<?php echo admin_url('edit.php?post_type=pt_proyecto&page=pt-audit-log'); ?>">📊 Registro de Actividad</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Container -->
    <div class="container">
        <div class="page-header">
            <h1>Explora tus proyectos</h1>
            <p>Esta es tu <strong>área de proyectos</strong>, aquí puedes acceder a <strong>toda la información</strong> sobre cada obra, ya esté finalizada o en proceso.</p>
        </div>
        
        <?php if (empty($proyectos)): ?>
            <!-- Estado vacío -->
            <div class="empty-state">
                <div class="empty-state-icon">📦</div>
                <h2>No tienes proyectos asignados</h2>
                <p>
                    <?php if ($is_admin): ?>
                        Aún no has creado ningún proyecto. Comienza creando tu primer proyecto desde el panel de administración.
                    <?php else: ?>
                        Actualmente no tienes ningún proyecto asignado. Contacta con tu administrador si crees que es un error.
                    <?php endif; ?>
                </p>
                
                <?php if ($is_admin): ?>
                <div class="empty-state-actions">
                    <a href="<?php echo admin_url('post-new.php?post_type=pt_proyecto'); ?>" class="btn btn-primary">
                        ➕ Crear Primer Proyecto
                    </a>
                    <a href="<?php echo admin_url('edit.php?post_type=pt_proyecto&page=pt-usuarios'); ?>" class="btn btn-secondary">
                        👥 Gestionar Usuarios
                    </a>
                </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Grid de proyectos -->
            <div class="proyectos-grid">
                <?php foreach ($proyectos as $proyecto): 
                    $meta = PT_Proyectos_View::getProyectoMeta($proyecto->ID);
                    $num_hitos = PT_Proyectos_View::countHitos($proyecto->ID);
                    $imagen = $meta['imagen_principal'] ? wp_get_attachment_url($meta['imagen_principal']) : '';
                ?>
                    <a href="<?php echo home_url('/proyecto/' . $proyecto->post_name); ?>" class="proyecto-card">
                        <div class="proyecto-image">
                            <?php if ($imagen): ?>
                                <img src="<?php echo esc_url($imagen); ?>" alt="<?php echo esc_attr($proyecto->post_title); ?>">
                            <?php else: ?>
                                <div style="width:100%;height:100%;background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#999;font-size:48px;">
                                    🏗️
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="proyecto-content">
                            <h3 class="proyecto-title"><?php echo esc_html($proyecto->post_title); ?></h3>
                            
                            <?php if ($meta['direccion']): ?>
                            <div class="proyecto-direccion">
                                📍 <?php echo esc_html($meta['direccion']); ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="proyecto-meta">
                                <div class="proyecto-dates">
                                    <?php 
                                    if ($meta['fecha_inicio']) {
                                        echo date('d/m/Y', strtotime($meta['fecha_inicio']));
                                    }
                                    if ($meta['fecha_fin']) {
                                        echo ' - ' . date('d/m/Y', strtotime($meta['fecha_fin']));
                                    }
                                    ?>
                                </div>
                                
                                <div class="proyecto-hitos">
                                    <?php echo $num_hitos; ?> hitos
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>