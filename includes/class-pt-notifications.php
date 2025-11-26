<?php
/**
 * Sistema de Notificaciones
 * 
 * Ubicación: includes/class-pt-notifications.php
 */

if (!defined('ABSPATH')) exit;

class PT_Notifications {
    
    /**
     * Crear notificación
     */
    public static function createNotification($user_id, $type, $title, $message, $link = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'pt_notifications';
        
        return $wpdb->insert($table, array(
            'user_id' => $user_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'is_read' => 0,
            'created_at' => current_time('mysql')
        ));
    }
    
    /**
     * Obtener notificaciones de un usuario
     */
    public static function getUserNotifications($user_id, $limit = 10, $unread_only = false) {
        global $wpdb;
        $table = $wpdb->prefix . 'pt_notifications';
        
        $where = $wpdb->prepare("user_id = %d", $user_id);
        
        if ($unread_only) {
            $where .= " AND is_read = 0";
        }
        
        return $wpdb->get_results(
            "SELECT * FROM $table WHERE $where ORDER BY created_at DESC LIMIT $limit"
        );
    }
    
    /**
     * Marcar notificación como leída
     */
    public static function markAsRead($notification_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pt_notifications';
        
        return $wpdb->update(
            $table,
            array('is_read' => 1),
            array('id' => $notification_id)
        );
    }
    
    /**
     * Enviar email de bienvenida
     */
    public static function sendWelcomeEmail($user_id, $email, $username, $password) {
        $subject = 'Bienvenido al Portal de Proyectos';
        $login_url = home_url('/login-proyectos');
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #FDC425; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 30px; }
                .credentials { background: white; padding: 20px; border-left: 4px solid #FDC425; margin: 20px 0; }
                .button { display: inline-block; padding: 12px 30px; background: #FDC425; color: #000; text-decoration: none; border-radius: 5px; font-weight: bold; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin:0; color: #000;'>Portal de Proyectos</h1>
                </div>
                <div class='content'>
                    <h2>¡Bienvenido!</h2>
                    <p>Se ha creado una cuenta para que puedas acceder al portal de seguimiento de proyectos.</p>
                    
                    <div class='credentials'>
                        <h3>Tus credenciales de acceso:</h3>
                        <p><strong>Usuario:</strong> {$username}</p>
                        <p><strong>Contraseña:</strong> {$password}</p>
                        <p><strong>URL de acceso:</strong> <a href='{$login_url}'>{$login_url}</a></p>
                    </div>
                    
                    <p>Por favor, guarda estas credenciales en un lugar seguro. Te recomendamos cambiar tu contraseña después del primer inicio de sesión.</p>
                    
                    <p style='text-align: center; margin-top: 30px;'>
                        <a href='{$login_url}' class='button'>Acceder al Portal</a>
                    </p>
                </div>
                <div class='footer'>
                    <p>Este es un mensaje automático, por favor no respondas a este email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Portal de Proyectos <noreply@' . $_SERVER['HTTP_HOST'] . '>'
        );
        
        wp_mail($email, $subject, $message, $headers);
        
        // Crear notificación en el sistema
        self::createNotification(
            $user_id,
            'welcome',
            'Bienvenido al Portal',
            'Tu cuenta ha sido creada. Revisa tu email para obtener tus credenciales de acceso.',
            $login_url
        );
    }
    
    /**
     * Enviar email de nuevo hito
     */
    public static function sendNewHitoEmail($user_id, $proyecto_titulo, $hito_titulo) {
        global $wpdb;
        $table = $wpdb->prefix . 'pt_users';
        
        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $user_id
        ));
        
        if (!$user) return;
        
        $subject = "Nuevo avance en tu proyecto: {$proyecto_titulo}";
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #FDC425; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 30px; }
                .button { display: inline-block; padding: 12px 30px; background: #FDC425; color: #000; text-decoration: none; border-radius: 5px; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin:0; color: #000;'>Nuevo Avance</h1>
                </div>
                <div class='content'>
                    <h2>Hola {$user->nombre},</h2>
                    <p>Hay novedades en tu proyecto <strong>{$proyecto_titulo}</strong>.</p>
                    <p>Se ha añadido un nuevo hito: <strong>{$hito_titulo}</strong></p>
                    <p style='text-align: center; margin-top: 30px;'>
                        <a href='" . home_url('/mis-proyectos') . "' class='button'>Ver Proyecto</a>
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Portal de Proyectos <noreply@' . $_SERVER['HTTP_HOST'] . '>'
        );
        
        wp_mail($user->email, $subject, $message, $headers);
    }
}