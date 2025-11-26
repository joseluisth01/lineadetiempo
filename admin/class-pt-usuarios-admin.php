<?php
/**
 * Gestión de Usuarios en Admin
 * 
 * Ubicación: admin/class-pt-usuarios-admin.php
 */

if (!defined('ABSPATH')) exit;

class PT_Usuarios_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'addSubmenu'), 20);
        add_action('admin_post_pt_crear_usuario', array($this, 'handleCrearUsuario'));
        add_action('admin_post_pt_editar_usuario', array($this, 'handleEditarUsuario'));
        add_action('admin_post_pt_eliminar_usuario', array($this, 'handleEliminarUsuario'));
    }
    
    public function addSubmenu() {
        add_submenu_page(
            'edit.php?post_type=pt_proyecto',
            'Gestión de Usuarios',
            'Usuarios',
            'manage_options',
            'pt-usuarios',
            array($this, 'renderPage')
        );
    }
    
    public function renderPage() {
        global $wpdb;
        $table = $wpdb->prefix . 'pt_users';
        
        // Obtener todos los usuarios
        $usuarios = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
        
        // Obtener acción
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Gestión de Usuarios</h1>
            
            <?php if ($action === 'list'): ?>
                <a href="<?php echo admin_url('edit.php?post_type=pt_proyecto&page=pt-usuarios&action=new'); ?>" 
                   class="page-title-action">Añadir Usuario</a>
                
                <?php if (isset($_GET['message'])): ?>
                    <div class="notice notice-success is-dismissible">
                        <p>
                            <?php 
                            switch($_GET['message']) {
                                case 'created':
                                    echo 'Usuario creado correctamente.';
                                    break;
                                case 'updated':
                                    echo 'Usuario actualizado correctamente.';
                                    break;
                                case 'deleted':
                                    echo 'Usuario eliminado correctamente.';
                                    break;
                            }
                            ?>
                        </p>
                    </div>
                <?php endif; ?>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Último Login</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $user): ?>
                            <tr>
                                <td><?php echo $user->id; ?></td>
                                <td><strong><?php echo esc_html($user->username); ?></strong></td>
                                <td><?php echo esc_html($user->nombre . ' ' . $user->apellidos); ?></td>
                                <td><?php echo esc_html($user->email); ?></td>
                                <td>
                                    <span class="pt-badge <?php echo $user->role; ?>">
                                        <?php 
                                        switch($user->role) {
                                            case 'super_admin': echo 'Super Admin'; break;
                                            case 'admin': echo 'Admin'; break;
                                            case 'cliente': echo 'Cliente'; break;
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user->active): ?>
                                        <span style="color: green;">●</span> Activo
                                    <?php else: ?>
                                        <span style="color: red;">●</span> Inactivo
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($user->last_login) {
                                        echo date('d/m/Y H:i', strtotime($user->last_login));
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('edit.php?post_type=pt_proyecto&page=pt-usuarios&action=edit&user_id=' . $user->id); ?>">
                                        Editar
                                    </a>
                                    <?php if ($user->role !== 'super_admin' || $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE role = 'super_admin'") > 1): ?>
                                        | 
                                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=pt_eliminar_usuario&user_id=' . $user->id), 'pt_eliminar_usuario_' . $user->id); ?>" 
                                           onclick="return confirm('¿Estás seguro de eliminar este usuario?');"
                                           style="color: #dc3545;">
                                            Eliminar
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
            <?php elseif ($action === 'new' || $action === 'edit'): ?>
                <?php
                $user_data = null;
                if ($action === 'edit' && $user_id) {
                    $user_data = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM $table WHERE id = %d",
                        $user_id
                    ));
                }
                ?>
                
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field($action === 'new' ? 'pt_crear_usuario' : 'pt_editar_usuario_' . $user_id); ?>
                    <input type="hidden" name="action" value="<?php echo $action === 'new' ? 'pt_crear_usuario' : 'pt_editar_usuario'; ?>">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                    <?php endif; ?>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="username">Usuario *</label></th>
                            <td>
                                <input type="text" id="username" name="username" 
                                       value="<?php echo $user_data ? esc_attr($user_data->username) : ''; ?>" 
                                       class="regular-text" required
                                       <?php echo $action === 'edit' ? 'readonly' : ''; ?>>
                                <p class="description">El nombre de usuario no se puede cambiar después de crear el usuario.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="email">Email *</label></th>
                            <td>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo $user_data ? esc_attr($user_data->email) : ''; ?>" 
                                       class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="nombre">Nombre *</label></th>
                            <td>
                                <input type="text" id="nombre" name="nombre" 
                                       value="<?php echo $user_data ? esc_attr($user_data->nombre) : ''; ?>" 
                                       class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="apellidos">Apellidos</label></th>
                            <td>
                                <input type="text" id="apellidos" name="apellidos" 
                                       value="<?php echo $user_data ? esc_attr($user_data->apellidos) : ''; ?>" 
                                       class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="role">Rol *</label></th>
                            <td>
                                <select id="role" name="role" required>
                                    <?php if (PT_Roles::isSuperAdmin(PT_Auth::getCurrentUserId())): ?>
                                        <option value="super_admin" <?php selected($user_data ? $user_data->role : '', 'super_admin'); ?>>
                                            Super Administrador
                                        </option>
                                    <?php endif; ?>
                                    <option value="admin" <?php selected($user_data ? $user_data->role : '', 'admin'); ?>>
                                        Administrador
                                    </option>
                                    <option value="cliente" <?php selected($user_data ? $user_data->role : 'cliente', 'cliente'); ?>>
                                        Cliente
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <?php if ($action === 'edit'): ?>
                            <tr>
                                <th><label for="active">Estado</label></th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="active" name="active" value="1" 
                                               <?php checked($user_data ? $user_data->active : 1, 1); ?>>
                                        Usuario activo
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="reset_password">Restablecer Contraseña</label></th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="reset_password" name="reset_password" value="1">
                                        Generar nueva contraseña y enviar por email
                                    </label>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" class="button button-primary" 
                               value="<?php echo $action === 'new' ? 'Crear Usuario' : 'Actualizar Usuario'; ?>">
                        <a href="<?php echo admin_url('edit.php?post_type=pt_proyecto&page=pt-usuarios'); ?>" 
                           class="button">Cancelar</a>
                    </p>
                </form>
            <?php endif; ?>
        </div>
        
        <style>
            .pt-badge {
                display: inline-block;
                padding: 4px 12px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
            }
            .pt-badge.super_admin {
                background: #dc3545;
                color: white;
            }
            .pt-badge.admin {
                background: #FDC425;
                color: #000;
            }
            .pt-badge.cliente {
                background: #28a745;
                color: white;
            }
        </style>
        <?php
    }
    
    public function handleCrearUsuario() {
        check_admin_referer('pt_crear_usuario');
        
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para realizar esta acción.');
        }
        
        $data = array(
            'username' => sanitize_user($_POST['username']),
            'email' => sanitize_email($_POST['email']),
            'nombre' => sanitize_text_field($_POST['nombre']),
            'apellidos' => sanitize_text_field($_POST['apellidos']),
            'role' => sanitize_text_field($_POST['role'])
        );
        
        $result = PT_Auth::createUser($data);
        
        if ($result['success']) {
            wp_redirect(admin_url('edit.php?post_type=pt_proyecto&page=pt-usuarios&message=created'));
        } else {
            wp_die('Error al crear el usuario: ' . $result['message']);
        }
        exit;
    }
    
    public function handleEditarUsuario() {
        $user_id = intval($_POST['user_id']);
        check_admin_referer('pt_editar_usuario_' . $user_id);
        
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para realizar esta acción.');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'pt_users';
        
        $update_data = array(
            'email' => sanitize_email($_POST['email']),
            'nombre' => sanitize_text_field($_POST['nombre']),
            'apellidos' => sanitize_text_field($_POST['apellidos']),
            'role' => sanitize_text_field($_POST['role']),
            'active' => isset($_POST['active']) ? 1 : 0
        );
        
        // Si se solicita reset de contraseña
        if (isset($_POST['reset_password'])) {
            $new_password = wp_generate_password(12, false);
            $update_data['password'] = password_hash($new_password, PASSWORD_BCRYPT);
            
            // Enviar email con nueva contraseña
            $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $user_id));
            PT_Notifications::sendWelcomeEmail($user_id, $user->email, $user->username, $new_password);
        }
        
        $wpdb->update($table, $update_data, array('id' => $user_id));
        
        PT_Audit_Log::log(
            get_current_user_id(),
            'update_user',
            'user',
            $user_id,
            'Usuario actualizado'
        );
        
        wp_redirect(admin_url('edit.php?post_type=pt_proyecto&page=pt-usuarios&message=updated'));
        exit;
    }
    
    public function handleEliminarUsuario() {
        $user_id = intval($_GET['user_id']);
        check_admin_referer('pt_eliminar_usuario_' . $user_id);
        
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para realizar esta acción.');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'pt_users';
        
        // Desactivar en lugar de eliminar
        $wpdb->update($table, 
            array('active' => 0),
            array('id' => $user_id)
        );
        
        PT_Audit_Log::log(
            get_current_user_id(),
            'delete_user',
            'user',
            $user_id,
            'Usuario desactivado'
        );
        
        wp_redirect(admin_url('edit.php?post_type=pt_proyecto&page=pt-usuarios&message=deleted'));
        exit;
    }
}

new PT_Usuarios_Admin();