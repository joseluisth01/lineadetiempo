<?php
/**
 * Template: Dashboard
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

$session = new GP_Session();
$current_user = $session->get_user();

if (!$current_user) {
    echo '<p>Debes iniciar sesión.</p>';
    return;
}
?>

<div class="gp-dashboard">
    <!-- Header -->
    <div class="gp-dashboard-header">
        <h1>Sistema de Gestión de Proyectos</h1>
        <div class="gp-header-right">
            <span class="gp-user-name"><?php echo esc_html($current_user['nombre']); ?></span>
            <span class="gp-user-badge gp-badge-<?php echo esc_attr($current_user['user_type']); ?>">
                <?php 
                $roles = [
                    'super_admin' => 'Super Admin',
                    'admin' => 'Administrador',
                    'cliente' => 'Cliente'
                ];
                echo $roles[$current_user['user_type']];
                ?>
            </span>
            <button id="gp-logout-btn" class="gp-btn gp-btn-secondary gp-btn-small">Cerrar Sesión</button>
        </div>
    </div>
    
    <?php if ($session->is_admin()): ?>
    <!-- Navegación del Dashboard (Solo para admins) -->
    <div class="gp-dashboard-nav">
        <a href="#" class="gp-nav-item active" data-view="usuarios">Usuarios</a>
        <a href="#" class="gp-nav-item" data-view="proyectos">Proyectos</a>
        <a href="#" class="gp-nav-item" data-view="auditoria">Auditoría</a>
    </div>
    <?php endif; ?>
    
    <!-- Contenido del Dashboard -->
    <div class="gp-dashboard-content">
        
        <?php if ($session->is_admin()): ?>
        <!-- Vista de Usuarios -->
        <div id="gp-view-usuarios" class="gp-view active">
            <div class="gp-view-header">
                <h2>Gestión de Usuarios</h2>
                <button class="gp-btn gp-btn-primary" onclick="gpShowCreateUserModal()">+ Crear Usuario</button>
            </div>
            
            <div class="gp-content-area">
                <!-- Tabs -->
                <div class="gp-tabs">
                    <?php if ($session->is_super_admin()): ?>
                    <div class="gp-tab active" data-tab="admins">Administradores</div>
                    <?php endif; ?>
                    <div class="gp-tab <?php echo !$session->is_super_admin() ? 'active' : ''; ?>" data-tab="clientes">Clientes</div>
                </div>
                
                <!-- Contenido de Tabs -->
                <?php if ($session->is_super_admin()): ?>
                <div id="gp-tab-admins" class="gp-tab-content active">
                    <div id="gp-admins-list">
                        <p>Cargando administradores...</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <div id="gp-tab-clientes" class="gp-tab-content <?php echo !$session->is_super_admin() ? 'active' : ''; ?>">
                    <div id="gp-clientes-list">
                        <p>Cargando clientes...</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Vista de Proyectos -->
        <div id="gp-view-proyectos" class="gp-view">
            <div class="gp-view-header">
                <h2>Proyectos</h2>
                <button class="gp-btn gp-btn-primary">+ Crear Proyecto</button>
            </div>
            
            <div class="gp-content-area">
                <p>Próximamente: Gestión de proyectos</p>
            </div>
        </div>
        
        <!-- Vista de Auditoría -->
        <div id="gp-view-auditoria" class="gp-view">
            <div class="gp-view-header">
                <h2>Registro de Auditoría</h2>
            </div>
            
            <div class="gp-content-area">
                <p>Próximamente: Historial de acciones</p>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Vista de Cliente -->
        <div class="gp-view active">
            <div class="gp-view-header">
                <h2>Mis Proyectos</h2>
            </div>
            
            <div class="gp-content-area">
                <p>Próximamente: Vista de proyectos del cliente</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Crear Usuario -->
<div id="gp-create-user-modal" class="gp-modal">
    <div class="gp-modal-content">
        <div class="gp-modal-header">
            <h2>Crear Usuario</h2>
            <span class="gp-modal-close" onclick="gpCloseModal('gp-create-user-modal')">&times;</span>
        </div>
        
        <form id="gp-create-user-form">
            <div class="gp-form-group">
                <label for="user-nombre">Nombre Completo *</label>
                <input type="text" id="user-nombre" name="nombre" required>
            </div>
            
            <div class="gp-form-group">
                <label for="user-email">Email *</label>
                <input type="email" id="user-email" name="email" required>
            </div>
            
            <div class="gp-form-group">
                <label for="user-username">Nombre de Usuario *</label>
                <input type="text" id="user-username" name="username" required>
                <small>Solo letras, números y guiones bajos</small>
            </div>
            
            <div class="gp-form-group">
                <label for="user-password">Contraseña *</label>
                <div class="gp-password-field">
                    <input type="text" id="user-password" name="password" required>
                    <button type="button" class="gp-btn gp-btn-secondary gp-btn-small" onclick="gpGeneratePassword()">Generar</button>
                </div>
                <small>Mínimo 8 caracteres</small>
            </div>
            
            <div class="gp-form-group">
                <label for="user-type">Tipo de Usuario *</label>
                <select id="user-type" name="user_type" required>
                    <?php if ($session->is_super_admin()): ?>
                    <option value="admin">Administrador</option>
                    <?php endif; ?>
                    <option value="cliente">Cliente</option>
                </select>
            </div>
            
            <div class="gp-form-message" style="display: none;"></div>
            
            <div class="gp-modal-footer">
                <button type="button" class="gp-btn gp-btn-secondary" onclick="gpCloseModal('gp-create-user-modal')">Cancelar</button>
                <button type="submit" class="gp-btn gp-btn-primary">
                    <span class="gp-btn-text">Crear Usuario</span>
                    <span class="gp-btn-loader" style="display: none;">Creando...</span>
                </button>
            </div>
        </form>
    </div>
</div>