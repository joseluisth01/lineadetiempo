/**
 * JavaScript del Admin
 * Ubicación: assets/js/admin.js
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Crear usuario AJAX
        $('#pt-create-user-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            formData += '&action=pt_create_user&nonce=' + ptAdmin.nonce;
            
            $.post(ptAdmin.ajaxurl, formData, function(response) {
                if (response.success) {
                    alert('Usuario creado correctamente');
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            });
        });
        
        // Eliminar usuario
        $('.pt-delete-user').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('¿Estás seguro de eliminar este usuario?')) {
                return;
            }
            
            var userId = $(this).data('user-id');
            
            $.post(ptAdmin.ajaxurl, {
                action: 'pt_delete_user',
                user_id: userId,
                nonce: ptAdmin.nonce
            }, function(response) {
                if (response.success) {
                    alert('Usuario eliminado');
                    location.reload();
                }
            });
        });
    });
    
})(jQuery);