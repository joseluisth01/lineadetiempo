<?php
/**
 * Template de Login
 * 
 * Ubicación: templates/login.php
 */

if (!defined('ABSPATH')) exit;

$error = isset($_SESSION['pt_login_error']) ? $_SESSION['pt_login_error'] : '';
unset($_SESSION['pt_login_error']);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Portal de Proyectos</title>
    <style>
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
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        
        .login-header {
            background: #FDC425;
            padding: 40px 30px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 28px;
            color: #000;
            margin-bottom: 5px;
        }
        
        .login-header p {
            color: #333;
            font-size: 14px;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #FDC425;
            box-shadow: 0 0 0 3px rgba(253, 196, 37, 0.1);
        }
        
        .error-message {
            background: #fee;
            border-left: 4px solid #c00;
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #c00;
            font-size: 14px;
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: #FDC425;
            border: none;
            border-radius: 8px;
            color: #000;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            background: #e5b020;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(253, 196, 37, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .login-footer {
            text-align: center;
            padding: 20px;
            background: #f8f8f8;
            font-size: 13px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Portal de Proyectos</h1>
            <p>Accede para ver tus proyectos</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo esc_html($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="pt_username">Usuario</label>
                    <input type="text" id="pt_username" name="pt_username" required autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="pt_password">Contraseña</label>
                    <input type="password" id="pt_password" name="pt_password" required autocomplete="current-password">
                </div>
                
                <button type="submit" name="pt_login_submit" class="btn-login">
                    Iniciar Sesión
                </button>
            </form>
        </div>
        
        <div class="login-footer">
            ¿Problemas para acceder? Contacta con tu administrador
        </div>
    </div>
</body>
</html>