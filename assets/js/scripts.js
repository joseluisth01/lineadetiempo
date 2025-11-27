/**
 * JavaScript principal para el plugin de Gestión de Proyectos
 */

(function($) {
    'use strict';
    
    // Variables globales
    let currentUser = null;
    
    /**
     * Inicialización cuando el documento está listo
     */
    $(document).ready(function() {
        initLoginForm();
        initLogout();
        initDashboard();
        initCreateUserForm();
    });
    
    /**
     * Inicializar formulario de login
     */
    function initLoginForm() {
        $('#gp-login-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $btn = $form.find('button[type="submit"]');
            const $btnText = $btn.find('.gp-btn-text');
            const $btnLoader = $btn.find('.gp-btn-loader');
            const $message = $form.find('.gp-form-message');
            
            // Deshabilitar botón y mostrar loader
            $btn.prop('disabled', true);
            $btnText.hide();
            $btnLoader.show();
            $message.hide();
            
            // Datos del formulario
            const data = {
                action: 'gp_login',
                nonce: gpAjax.nonce,
                username: $('#gp-username').val(),
                password: $('#gp-password').val()
            };
            
            // Petición AJAX
            $.post(gpAjax.ajaxurl, data, function(response) {
                if (response.success) {
                    $message
                        .removeClass('error')
                        .addClass('success')
                        .text(response.message)
                        .show();
                    
                    // Recargar la página
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    $message
                        .removeClass('success')
                        .addClass('error')
                        .text(response.message)
                        .show();
                    
                    // Habilitar botón
                    $btn.prop('disabled', false);
                    $btnText.show();
                    $btnLoader.hide();
                }
            });
        });
    }
    
    /**
     * Inicializar logout
     */
    function initLogout() {
        $(document).on('click', '#gp-logout-btn', function(e) {
            e.preventDefault();
            
            if (!confirm('¿Estás seguro de que quieres cerrar sesión?')) {
                return;
            }
            
            const data = {
                action: 'gp_logout',
                nonce: gpAjax.nonce
            };
            
            $.post(gpAjax.ajaxurl, data, function(response) {
                if (response.success) {
                    window.location.reload();
                }
            });
        });
    }
    
    /**
     * Inicializar dashboard
     */
    function initDashboard() {
        // Navegación entre vistas
        $('.gp-nav-item').on('click', function(e) {
            e.preventDefault();
            
            const viewName = $(this).data('view');
            
            // Cambiar navegación activa
            $('.gp-nav-item').removeClass('active');
            $(this).addClass('active');
            
            // Cambiar vista activa
            $('.gp-view').removeClass('active');
            $('#gp-view-' + viewName).addClass('active');
            
            // Cargar datos según la vista
            loadViewData(viewName);
        });
        
        // Tabs
        $('.gp-tab').on('click', function() {
            const tabName = $(this).data('tab');
            
            // Cambiar tab activo
            $('.gp-tab').removeClass('active');
            $(this).addClass('active');
            
            // Cambiar contenido activo
            $('.gp-tab-content').removeClass('active');
            $('#gp-tab-' + tabName).addClass('active');
            
            // Cargar datos del tab
            loadTabData(tabName);
        });
        
        // Cargar datos iniciales
        const firstTab = $('.gp-tab.active').data('tab');
        if (firstTab) {
            loadTabData(firstTab);
        }
    }
    
    /**
     * Cargar datos de una vista
     */
    function loadViewData(viewName) {
        switch(viewName) {
            case 'usuarios':
                const activeTab = $('.gp-tab.active').data('tab');
                if (activeTab) {
                    loadTabData(activeTab);
                }
                break;
            case 'proyectos':
                // TODO: Cargar proyectos
                break;
            case 'auditoria':
                // TODO: Cargar auditoría
                break;
        }
    }
    
    /**
     * Cargar datos de un tab
     */
    function loadTabData(tabName) {
        switch(tabName) {
            case 'admins':
                loadUsers('admin');
                break;
            case 'clientes':
                loadUsers('cliente');
                break;
        }
    }
    
    /**
     * Cargar usuarios
     */
    function loadUsers(userType) {
        const containerId = userType === 'admin' ? '#gp-admins-list' : '#gp-clientes-list';
        const $container = $(containerId);
        
        $container.html('<p>Cargando usuarios...</p>');
        
        const data = {
            action: 'gp_get_users',
            nonce: gpAjax.nonce,
            user_type: userType
        };
        
        $.post(gpAjax.ajaxurl, data, function(response) {
            if (response.success && response.users) {
                displayUsers(response.users, $container);
            } else {
                $container.html('<p>No se encontraron usuarios.</p>');
            }
        });
    }
    
    /**
     * Mostrar usuarios en tabla
     */
    function displayUsers(users, $container) {
        if (users.length === 0) {
            $container.html('<p>No hay usuarios registrados.</p>');
            return;
        }
        
        let html = '<table class="gp-users-table">';
        html += '<thead><tr>';
        html += '<th>Nombre</th>';
        html += '<th>Usuario</th>';
        html += '<th>Email</th>';
        html += '<th>Último acceso</th>';
        html += '<th>Estado</th>';
        html += '<th>Acciones</th>';
        html += '</tr></thead>';
        html += '<tbody>';
        
        users.forEach(function(user) {
            html += '<tr>';
            html += '<td>' + escapeHtml(user.nombre) + '</td>';
            html += '<td>' + escapeHtml(user.username) + '</td>';
            html += '<td>' + escapeHtml(user.email) + '</td>';
            html += '<td>' + (user.last_login ? formatDate(user.last_login) : 'Nunca') + '</td>';
            html += '<td><span class="gp-badge gp-badge-' + user.status + '">' + (user.status === 'active' ? 'Activo' : 'Inactivo') + '</span></td>';
            html += '<td class="gp-table-actions">';
            html += '<button class="gp-btn gp-btn-small gp-btn-secondary" onclick="gpEditUser(' + user.id + ')">Editar</button>';
            if (user.username !== 'administrador') {
                html += '<button class="gp-btn gp-btn-small" style="background-color: #dc3545; color: white;" onclick="gpDeleteUser(' + user.id + ')">Eliminar</button>';
            }
            html += '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        
        $container.html(html);
    }
    
    /**
     * Inicializar formulario de crear usuario
     */
    function initCreateUserForm() {
        $('#gp-create-user-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $btn = $form.find('button[type="submit"]');
            const $btnText = $btn.find('.gp-btn-text');
            const $btnLoader = $btn.find('.gp-btn-loader');
            const $message = $form.find('.gp-form-message');
            
            // Deshabilitar botón
            $btn.prop('disabled', true);
            $btnText.hide();
            $btnLoader.show();
            $message.hide();
            
            // Datos del formulario
            const data = {
                action: 'gp_create_user',
                nonce: gpAjax.nonce,
                nombre: $('#user-nombre').val(),
                email: $('#user-email').val(),
                username: $('#user-username').val(),
                password: $('#user-password').val(),
                user_type: $('#user-type').val()
            };
            
            // Petición AJAX
            $.post(gpAjax.ajaxurl, data, function(response) {
                if (response.success) {
                    $message
                        .removeClass('error')
                        .addClass('success')
                        .text(response.message)
                        .show();
                    
                    // Resetear formulario
                    $form[0].reset();
                    
                    // Recargar lista de usuarios
                    setTimeout(function() {
                        gpCloseModal('gp-create-user-modal');
                        loadUsers(data.user_type);
                    }, 1500);
                } else {
                    $message
                        .removeClass('success')
                        .addClass('error')
                        .text(response.message)
                        .show();
                }
                
                // Habilitar botón
                $btn.prop('disabled', false);
                $btnText.show();
                $btnLoader.hide();
            });
        });
    }
    
    /**
     * Funciones globales
     */
    window.gpShowCreateUserModal = function() {
        $('#gp-create-user-modal').addClass('active');
    };
    
    window.gpCloseModal = function(modalId) {
        $('#' + modalId).removeClass('active');
        $('#' + modalId + ' form')[0].reset();
        $('#' + modalId + ' .gp-form-message').hide();
    };
    
    window.gpGeneratePassword = function() {
        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
        let password = '';
        for (let i = 0; i < 12; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        $('#user-password').val(password);
    };
    
    window.gpEditUser = function(userId) {
        // TODO: Implementar edición de usuario
        alert('Función de edición en desarrollo. ID: ' + userId);
    };
    
    window.gpDeleteUser = function(userId) {
        if (!confirm('¿Estás seguro de que quieres eliminar este usuario?')) {
            return;
        }
        
        const data = {
            action: 'gp_delete_user',
            nonce: gpAjax.nonce,
            user_id: userId
        };
        
        $.post(gpAjax.ajaxurl, data, function(response) {
            if (response.success) {
                alert(response.message);
                // Recargar usuarios
                const activeTab = $('.gp-tab.active').data('tab');
                if (activeTab) {
                    loadTabData(activeTab);
                }
            } else {
                alert(response.message);
            }
        });
    };
    
    /**
     * Utilidades
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('es-ES', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    // Cerrar modales al hacer clic fuera
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('gp-modal')) {
            $(e.target).removeClass('active');
        }
    });
    
})(jQuery);