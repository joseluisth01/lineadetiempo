<?php
/**
 * Template: Formulario de Login
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="gp-login-container">
    <div class="gp-login-box">
        <div class="gp-login-header">
            <h1>Sistema de Gestión de Proyectos</h1>
            <p>Inicia sesión para continuar</p>
        </div>
        
        <form id="gp-login-form" class="gp-login-form">
            <div class="gp-form-group">
                <label for="gp-username">Usuario</label>
                <input type="text" id="gp-username" name="username" required placeholder="Ingresa tu usuario" autocomplete="username">
            </div>
            
            <div class="gp-form-group">
                <label for="gp-password">Contraseña</label>
                <input type="password" id="gp-password" name="password" required placeholder="Ingresa tu contraseña" autocomplete="current-password">
            </div>
            
            <div class="gp-form-message" style="display: none;"></div>
            
            <button type="submit" class="gp-btn gp-btn-primary">
                <span class="gp-btn-text">Iniciar Sesión</span>
                <span class="gp-btn-loader" style="display: none;">Cargando...</span>
            </button>
        </form>
        
        <div class="gp-login-footer">
            <p>¿Problemas para iniciar sesión? Contacta con tu administrador.</p>
        </div>
    </div>
</div>