/**
 * JavaScript Público General
 * Ubicación: assets/js/public.js
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Animaciones de entrada
        $('.proyecto-card').each(function(index) {
            $(this).css({
                'opacity': '0',
                'transform': 'translateY(30px)'
            }).delay(index * 100).animate({
                'opacity': '1'
            }, 500).css('transform', 'translateY(0)');
        });
        
        // Confirmación de logout
        $('.logout-btn').on('click', function(e) {
            if (!confirm('¿Estás seguro de que quieres cerrar sesión?')) {
                e.preventDefault();
            }
        });
    });
    
})(jQuery);