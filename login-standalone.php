<?php
/**
 * Template: Login Standalone (sin tema de WordPress)
 * 
 * Este archivo debe colocarse en la raíz del plugin y cargarse mediante
 * template_redirect para mostrar el login sin header/footer
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

$session = new GP_Session();

// Si ya está logueado, redirigir al dashboard
if ($session->is_logged_in()) {
    wp_redirect(home_url('/login-proyectos/'));
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Gestión de Proyectos</title>
    
    <!-- CSS del plugin -->
    <link rel="stylesheet" href="<?php echo GP_PLUGIN_URL; ?>assets/css/styles.css?v=<?php echo GP_VERSION; ?>">
    
    <style>
        /* Reset básico */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Estilos minimalistas para el login */
        .gp-login-minimal {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
            padding: 50px 40px;
        }
        
        .gp-login-minimal h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .gp-login-minimal .subtitle {
            color: #666;
            font-size: 14px;
            text-align: center;
            margin-bottom: 40px;
        }
        
        .gp-form-group {
            margin-bottom: 24px;
        }
        
        .gp-form-group label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            color: #333;
            margin-bottom: 8px;
        }
        
        .gp-form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            background: #fafafa;
        }
        
        .gp-form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
        }
        
        .gp-btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .gp-btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .gp-btn-login:active {
            transform: translateY(0);
        }
        
        .gp-form-message {
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }
        
        .gp-form-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .gp-form-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .gp-login-footer {
            margin-top: 30px;
            text-align: center;
            color: #999;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="gp-login-minimal">
        <h1>Bienvenido</h1>
        <p class="subtitle">Inicia sesión para continuar</p>
        
        <form id="gp-login-form">
            <div class="gp-form-group">
                <label for="gp-username">Usuario</label>
                <input type="text" id="gp-username" name="username" required placeholder="Tu nombre de usuario" autocomplete="username">
            </div>
            
            <div class="gp-form-group">
                <label for="gp-password">Contraseña</label>
                <input type="password" id="gp-password" name="password" required placeholder="Tu contraseña" autocomplete="current-password">
            </div>
            
            <div class="gp-form-message"></div>
            
            <button type="submit" class="gp-btn-login">
                <span class="gp-btn-text">Iniciar Sesión</span>
                <span class="gp-btn-loader" style="display: none;">Cargando...</span>
            </button>
        </form>
        
        <div class="gp-login-footer">
            ¿Problemas para acceder? Contacta con tu administrador
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- JavaScript del plugin -->
    <script>
        var gpAjax = {
            ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('gp_nonce'); ?>'
        };
        
        jQuery(document).ready(function($) {
            $('#gp-login-form').on('submit', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const $btn = $form.find('button[type="submit"]');
                const $btnText = $btn.find('.gp-btn-text');
                const $btnLoader = $btn.find('.gp-btn-loader');
                const $message = $('.gp-form-message');
                
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
                            window.location.href = response.redirect || window.location.href;
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
                }).fail(function() {
                    $message
                        .removeClass('success')
                        .addClass('error')
                        .text('Error de conexión. Por favor, intenta de nuevo.')
                        .show();
                    
                    // Habilitar botón
                    $btn.prop('disabled', false);
                    $btnText.show();
                    $btnLoader.hide();
                });
            });
        });
    </script>
</body>
</html>