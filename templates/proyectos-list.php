<?php
/**
 * Template Lista de Proyectos
 * 
 * Ubicación: templates/proyectos-list.php
 */

if (!defined('ABSPATH')) exit;

PT_Auth::requireLogin();

$user_id = PT_Auth::getCurrentUserId();
$proyectos = PT_Proyecto::getProyectosCliente($user_id);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Proyectos - Portal de Proyectos</title>
    <?php wp_head(); ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 40px 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 50px;
            position: relative;
        }
        
        .page-header h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .page-header .subtitle {
            color: #666;
            font-size: 16px;
        }
        
        .page-header .subtitle strong {
            color: #000;
        }
        
        .logout-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            z-index: 100;
        }
        
        .logout-btn:hover {
            background: #c82333;
        }
        
        .proyectos-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .proyectos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .proyecto-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            position: relative;
        }
        
        .proyecto-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .proyecto-imagen {
            width: 100%;
            height: 200px;
            object-fit: cover;
            position: relative;
        }
        
        .proyecto-estado {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .proyecto-estado.en_proceso {
            background: #FDC425;
            color: #000;
        }
        
        .proyecto-estado.finalizado {
            background: #FFDE88;
            color: #000;
        }
        
        .proyecto-estado.pendiente {
            background: #EDEDED;
            color: #666;
        }
        
        .proyecto-info {
            padding: 25px;
        }
        
        .proyecto-titulo {
            font-size: 18px;
            font-weight: 700;
            color: #000;
            margin-bottom: 10px;
            line-height: 1.3;
        }
        
        .proyecto-direccion {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .ver-proyecto-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: #FDC425;
            color: #000;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .ver-proyecto-btn:hover {
            background: #e5b020;
        }
        
        .no-proyectos {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .no-proyectos h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .no-proyectos p {
            color: #666;
            font-size: 16px;
        }
        
        @media (max-width: 768px) {
            .proyectos-grid {
                grid-template-columns: 1fr;
            }
            
            .logout-btn {
                position: relative;
                top: 0;
                right: 0;
                display: block;
                width: fit-content;
                margin: 0 auto 30px;
            }
        }
    </style>
</head>
<body>
    <a href="<?php echo home_url('/?pt_logout=1'); ?>" class="logout-btn">Cerrar Sesión</a>
    
    <div class="page-header">
        <h1>Explora <strong>tus proyectos</strong></h1>
        <p class="subtitle">Esta es tu <strong>área de proyectos</strong>, aquí puedes acceder a <strong>toda la información</strong> sobre cada obra, ya esté finalizada o en proceso.</p>
    </div>
    
    <div class="proyectos-container">
        <?php if (empty($proyectos)): ?>
            <div class="no-proyectos">
                <h2>No tienes proyectos asignados</h2>
                <p>Actualmente no tienes ningún proyecto asignado. Contacta con tu administrador si crees que es un error.</p>
            </div>
        <?php else: ?>
            <div class="proyectos-grid">
                <?php foreach ($proyectos as $proyecto): 
                    $direccion = get_post_meta($proyecto->ID, '_pt_direccion', true);
                    $estado = get_post_meta($proyecto->ID, '_pt_estado', true);
                    $thumbnail = get_the_post_thumbnail_url($proyecto->ID, 'large');
                    
                    if (!$thumbnail) {
                        // Obtener primera imagen de la galería
                        $imagenes = get_post_meta($proyecto->ID, '_pt_imagenes', true);
                        if (is_array($imagenes) && !empty($imagenes)) {
                            $thumbnail = wp_get_attachment_image_url($imagenes[0], 'large');
                        }
                    }
                    
                    if (!$thumbnail) {
                        $thumbnail = PT_PLUGIN_URL . 'assets/images/placeholder.jpg';
                    }
                    
                    $estado_texto = array(
                        'en_proceso' => 'EN PROCESO',
                        'finalizado' => 'FINALIZADO',
                        'pendiente' => 'PENDIENTE'
                    )[$estado] ?? 'EN PROCESO';
                ?>
                    <div class="proyecto-card">
                        <div style="position: relative;">
                            <img src="<?php echo esc_url($thumbnail); ?>" 
                                 alt="<?php echo esc_attr($proyecto->post_title); ?>" 
                                 class="proyecto-imagen">
                            <div class="proyecto-estado <?php echo esc_attr($estado); ?>">
                                <?php echo $estado_texto; ?>
                            </div>
                        </div>
                        
                        <div class="proyecto-info">
                            <h2 class="proyecto-titulo"><?php echo esc_html($proyecto->post_title); ?></h2>
                            <?php if ($direccion): ?>
                                <p class="proyecto-direccion"><?php echo esc_html($direccion); ?></p>
                            <?php endif; ?>
                            
                            <a href="<?php echo home_url('/proyecto/' . $proyecto->ID); ?>" 
                               class="ver-proyecto-btn">
                                VER INFORMACIÓN DEL PROYECTO
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php wp_footer(); ?>
</body>
</html>