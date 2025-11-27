<?php
/**
 * Clase para manejar notificaciones por email
 */

class GP_Notifications {
    
    /**
     * Enviar email de bienvenida con credenciales
     */
    public function send_welcome_email($to_email, $nombre, $username, $password) {
        $login_url = home_url('/login-proyectos/');
        
        $subject = 'Bienvenido al Sistema de Gestión de Proyectos';
        
        $message = $this->get_email_template([
            'titulo' => 'Bienvenido al Sistema',
            'contenido' => "
                <p>Hola <strong>{$nombre}</strong>,</p>
                <p>Se ha creado una cuenta para ti en nuestro Sistema de Gestión de Proyectos.</p>
                <p>Tus credenciales de acceso son:</p>
                <div style='background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin: 5px 0;'><strong>Usuario:</strong> {$username}</p>
                    <p style='margin: 5px 0;'><strong>Contraseña:</strong> {$password}</p>
                </div>
                <p>Puedes iniciar sesión en el siguiente enlace:</p>
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='{$login_url}' style='background-color: #FDC425; color: #000; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>Iniciar Sesión</a>
                </p>
                <p><strong>Importante:</strong> Por seguridad, te recomendamos cambiar tu contraseña después del primer inicio de sesión.</p>
            "
        ]);
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        return wp_mail($to_email, $subject, $message, $headers);
    }
    
    /**
     * Enviar notificación de nuevo hito
     */
    public function send_milestone_notification($to_email, $nombre_cliente, $nombre_proyecto, $titulo_hito) {
        $login_url = home_url('/login-proyectos/');
        
        $subject = "Nuevo hito en tu proyecto: {$nombre_proyecto}";
        
        $message = $this->get_email_template([
            'titulo' => 'Nuevo Hito Publicado',
            'contenido' => "
                <p>Hola <strong>{$nombre_cliente}</strong>,</p>
                <p>Se ha publicado un nuevo hito en tu proyecto <strong>{$nombre_proyecto}</strong>:</p>
                <div style='background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin: 0;'><strong>{$titulo_hito}</strong></p>
                </div>
                <p>Puedes ver todos los detalles iniciando sesión en el sistema:</p>
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='{$login_url}' style='background-color: #FDC425; color: #000; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>Ver Proyecto</a>
                </p>
            "
        ]);
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        return wp_mail($to_email, $subject, $message, $headers);
    }
    
    /**
     * Plantilla HTML para emails
     */
    private function get_email_template($data) {
        $titulo = isset($data['titulo']) ? $data['titulo'] : 'Notificación';
        $contenido = isset($data['contenido']) ? $data['contenido'] : '';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$titulo}</title>
        </head>
        <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;'>
            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color: #f4f4f4; padding: 20px;'>
                <tr>
                    <td align='center'>
                        <table width='600' cellpadding='0' cellspacing='0' border='0' style='background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                            <!-- Header -->
                            <tr>
                                <td style='background-color: #FDC425; padding: 30px; text-align: center;'>
                                    <h1 style='margin: 0; color: #000; font-size: 24px;'>{$titulo}</h1>
                                </td>
                            </tr>
                            <!-- Contenido -->
                            <tr>
                                <td style='padding: 40px 30px;'>
                                    {$contenido}
                                </td>
                            </tr>
                            <!-- Footer -->
                            <tr>
                                <td style='background-color: #f5f5f5; padding: 20px; text-align: center; color: #666; font-size: 12px;'>
                                    <p style='margin: 0;'>Este es un email automático, por favor no responder.</p>
                                    <p style='margin: 10px 0 0 0;'>&copy; " . date('Y') . " Sistema de Gestión de Proyectos. Todos los derechos reservados.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";
    }
}