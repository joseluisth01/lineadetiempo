<?php
/**
 * Vista de Audit Log
 * Ubicación: admin/views/audit-log.php
 */

if (!defined('ABSPATH')) exit;

// Filtros
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
$action_filter = isset($_GET['action_filter']) ? sanitize_text_field($_GET['action_filter']) : '';

$filters = array();
if ($date_from) $filters['date_from'] = $date_from;
if ($date_to) $filters['date_to'] = $date_to;
if ($action_filter) $filters['action'] = $action_filter;

$logs = PT_Audit_Log::getLogs($filters, 100);

// Acciones disponibles
$acciones_disponibles = array(
    'login' => 'Inicio de sesión',
    'logout' => 'Cierre de sesión',
    'create_user' => 'Crear usuario',
    'update_user' => 'Actualizar usuario',
    'delete_user' => 'Eliminar usuario',
    'create_proyecto' => 'Crear proyecto',
    'update_proyecto' => 'Actualizar proyecto',
    'create_hito' => 'Crear hito',
    'update_hito' => 'Actualizar hito',
    'upload_document' => 'Subir documento',
    'delete_document' => 'Eliminar documento'
);
?>

<div class="wrap">
    <h1>📋 Audit Log - Registro de Actividad</h1>
    
    <div class="pt-admin-wrap">
        <!-- Filtros -->
        <form method="GET" action="" style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
            <input type="hidden" name="page" value="pt-audit-log">
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                <div>
                    <label for="date_from" style="display: block; margin-bottom: 5px; font-weight: 600;">Desde:</label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo esc_attr($date_from); ?>" 
                           style="width: 100%; padding: 8px;">
                </div>
                
                <div>
                    <label for="date_to" style="display: block; margin-bottom: 5px; font-weight: 600;">Hasta:</label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo esc_attr($date_to); ?>" 
                           style="width: 100%; padding: 8px;">
                </div>
                
                <div>
                    <label for="action_filter" style="display: block; margin-bottom: 5px; font-weight: 600;">Acción:</label>
                    <select id="action_filter" name="action_filter" style="width: 100%; padding: 8px;">
                        <option value="">Todas las acciones</option>
                        <?php foreach ($acciones_disponibles as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($action_filter, $key); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <button type="submit" class="button button-primary">Filtrar</button>
                    <a href="?page=pt-audit-log" class="button">Limpiar</a>
                </div>
            </div>
        </form>
        
        <!-- Tabla de logs -->
        <?php if (empty($logs)): ?>
            <div style="text-align: center; padding: 40px; background: white; border-radius: 5px;">
                <p style="color: #666; font-size: 16px;">No hay registros de actividad con los filtros seleccionados.</p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 140px;">Fecha/Hora</th>
                        <th style="width: 150px;">Usuario</th>
                        <th style="width: 150px;">Acción</th>
                        <th>Descripción</th>
                        <th style="width: 120px;">IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): 
                        // Determinar icono según acción
                        $icono = '•';
                        $color = '#666';
                        
                        switch($log->action) {
                            case 'login':
                                $icono = '🔓';
                                $color = '#28a745';
                                break;
                            case 'logout':
                                $icono = '🔒';
                                $color = '#6c757d';
                                break;
                            case 'create_user':
                            case 'create_proyecto':
                            case 'create_hito':
                                $icono = '➕';
                                $color = '#007bff';
                                break;
                            case 'update_user':
                            case 'update_proyecto':
                            case 'update_hito':
                                $icono = '✏️';
                                $color = '#FDC425';
                                break;
                            case 'delete_user':
                            case 'delete_document':
                                $icono = '🗑️';
                                $color = '#dc3545';
                                break;
                            case 'upload_document':
                                $icono = '📤';
                                $color = '#17a2b8';
                                break;
                        }
                        
                        $accion_label = $acciones_disponibles[$log->action] ?? $log->action;
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo date('d/m/Y', strtotime($log->created_at)); ?></strong><br>
                                <small style="color: #666;"><?php echo date('H:i:s', strtotime($log->created_at)); ?></small>
                            </td>
                            <td>
                                <?php if ($log->username): ?>
                                    <strong><?php echo esc_html($log->username); ?></strong><br>
                                    <small style="color: #666;">
                                        <?php echo esc_html($log->nombre . ' ' . $log->apellidos); ?>
                                    </small>
                                <?php else: ?>
                                    <span style="color: #999;">Sistema</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="color: <?php echo $color; ?>; font-weight: 600;">
                                    <?php echo $icono; ?> <?php echo esc_html($accion_label); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo esc_html($log->description); ?>
                                
                                <?php if ($log->entity_type && $log->entity_id): ?>
                                    <br>
                                    <small style="color: #666;">
                                        <?php echo esc_html($log->entity_type); ?> #<?php echo $log->entity_id; ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code style="font-size: 11px; background: #f5f5f5; padding: 3px 6px; border-radius: 3px;">
                                    <?php echo esc_html($log->ip_address); ?>
                                </code>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="margin-top: 20px; text-align: center; color: #666; font-size: 14px;">
                Mostrando los últimos <?php echo count($logs); ?> registros
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .wp-list-table {
        background: white;
        border-radius: 5px;
        overflow: hidden;
    }
    
    .wp-list-table th {
        background: #f9f9f9;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
    }
    
    .wp-list-table td {
        vertical-align: top;
        padding: 12px;
    }
    
    .wp-list-table tr:hover {
        background: #f9f9f9;
    }
</style>