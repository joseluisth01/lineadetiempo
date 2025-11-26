<?php
/**
 * Template de Timeline del Proyecto
 * 
 * Ubicación: templates/timeline.php
 */

if (!defined('ABSPATH')) exit;

PT_Auth::requireLogin();

$proyecto_id = get_query_var('pt_proyecto_id');
$proyecto = get_post($proyecto_id);

if (!$proyecto || $proyecto->post_type !== 'pt_proyecto') {
    wp_die('Proyecto no encontrado');
}

// Verificar permisos
$user_id = PT_Auth::getCurrentUserId();
$tiene_acceso = false;

if (PT_Roles::isAdmin($user_id)) {
    $tiene_acceso = true;
} else {
    global $wpdb;
    $table = $wpdb->prefix . 'pt_user_proyecto';
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE user_id = %d AND proyecto_id = %d",
        $user_id,
        $proyecto_id
    ));
    $tiene_acceso = $count > 0;
}

if (!$tiene_acceso) {
    wp_die('No tienes permisos para ver este proyecto');
}

// Obtener datos del proyecto
$direccion = get_post_meta($proyecto_id, '_pt_direccion', true);
$fecha_inicio = get_post_meta($proyecto_id, '_pt_fecha_inicio', true);
$fecha_fin = get_post_meta($proyecto_id, '_pt_fecha_fin', true);
$hitos = PT_Hito::getHitosProyecto($proyecto_id);

// Obtener documentos
$table_docs = $wpdb->prefix . 'pt_documents';
$documentos = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_docs WHERE proyecto_id = %d ORDER BY uploaded_at DESC",
    $proyecto_id
));

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($proyecto->post_title); ?> - Timeline</title>
    <?php wp_head(); ?>
</head>
<body>
    <div class="pt-timeline-container">
        <div class="pt-proyecto-header">
            <a href="<?php echo home_url('/mis-proyectos'); ?>" 
               style="display: inline-block; margin-bottom: 20px; color: #666; text-decoration: none;">
                ← Volver a Mis Proyectos
            </a>
            <h1><?php echo esc_html($proyecto->post_title); ?></h1>
            <?php if ($direccion): ?>
                <p class="direccion"><?php echo esc_html($direccion); ?></p>
            <?php endif; ?>
            <a href="<?php echo home_url('/?pt_logout=1'); ?>" class="logout-btn">Cerrar Sesión</a>
        </div>
        
        <!-- Barra de fechas superior -->
        <div class="pt-timeline-dates-bar">
            <div class="date-marker start">
                <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?>
            </div>
            <div class="date-marker end">
                <?php echo date('d/m/Y', strtotime($fecha_fin)); ?>
            </div>
        </div>
        
        <!-- Línea de tiempo -->
        <div class="pt-timeline-wrapper">
            <div class="pt-timeline-line"></div>
            
            <div class="pt-timeline-points" 
                 data-fecha-inicio="<?php echo $fecha_inicio; ?>"
                 data-fecha-fin="<?php echo $fecha_fin; ?>">
                
                <?php foreach ($hitos as $index => $hito): 
                    $hito_fecha = get_post_meta($hito->ID, '_pt_fecha', true);
                    $hito_estado = get_post_meta($hito->ID, '_pt_estado', true);
                    $hito_imagenes = get_post_meta($hito->ID, '_pt_hito_imagenes', true);
                    
                    $primera_imagen = '';
                    if (is_array($hito_imagenes) && !empty($hito_imagenes)) {
                        $primera_imagen = wp_get_attachment_image_url($hito_imagenes[0], 'thumbnail');
                    }
                ?>
                    <div class="pt-hito" 
                         data-hito-id="<?php echo $hito->ID; ?>"
                         data-fecha="<?php echo $hito_fecha; ?>">
                        
                        <div class="pt-hito-point <?php echo esc_attr($hito_estado); ?>"></div>
                        
                        <div class="pt-hito-content">
                            <div class="pt-hito-fecha">
                                <?php echo date('d/m/Y', strtotime($hito_fecha)); ?>
                            </div>
                            
                            <?php if ($primera_imagen): ?>
                                <img src="<?php echo esc_url($primera_imagen); ?>" 
                                     alt="<?php echo esc_attr($hito->post_title); ?>"
                                     class="pt-hito-imagen">
                            <?php endif; ?>
                            
                            <?php if ($hito->post_title): ?>
                                <div class="pt-hito-titulo">
                                    <?php echo esc_html($hito->post_title); ?>
                                </div>
                            <?php endif; ?>
                            
                            <span class="pt-hito-info-btn">+ INFORMACIÓN</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Sección de Documentos -->
        <?php if (!empty($documentos)): ?>
            <div class="pt-documentos-section">
                <h2>📄 Documentos del Proyecto</h2>
                <div class="pt-documentos-grid">
                    <?php foreach ($documentos as $doc): 
                        $extension = pathinfo($doc->file_url, PATHINFO_EXTENSION);
                        $icon = '📄';
                        
                        if (in_array($extension, ['pdf'])) $icon = '📕';
                        elseif (in_array($extension, ['doc', 'docx'])) $icon = '📘';
                        elseif (in_array($extension, ['xls', 'xlsx'])) $icon = '📊';
                        elseif (in_array($extension, ['zip', 'rar'])) $icon = '📦';
                        elseif (in_array($extension, ['jpg', 'jpeg', 'png'])) $icon = '🖼️';
                    ?>
                        <div class="pt-documento-item">
                            <a href="<?php echo esc_url($doc->file_url); ?>" 
                               target="_blank" 
                               download>
                                <span class="pt-documento-icon"><?php echo $icon; ?></span>
                                <div>
                                    <strong><?php echo esc_html($doc->title); ?></strong>
                                    <br>
                                    <small><?php echo strtoupper($extension); ?> 
                                    (<?php echo size_format($doc->file_size); ?>)</small>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php wp_footer(); ?>
</body>
</html>