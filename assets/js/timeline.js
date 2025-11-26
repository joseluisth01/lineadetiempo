/**
 * JavaScript de la Línea de Tiempo
 * 
 * Ubicación: assets/js/timeline.js
 */

(function($) {
    'use strict';
    
    // Variables globales
    let currentHitoIndex = 0;
    let hitosData = [];
    
    /**
     * Inicializar timeline
     */
    function initTimeline() {
        positionHitos();
        initHitoClick();
        initModalControls();
    }
    
    /**
     * Posicionar hitos proporcionalmente en la línea
     */
    function positionHitos() {
        const container = $('.pt-timeline-points');
        if (!container.length) return;
        
        const containerWidth = container.width();
        const $hitos = $('.pt-hito');
        
        if (!$hitos.length) return;
        
        // Obtener fechas del proyecto
        const fechaInicio = new Date(container.data('fecha-inicio'));
        const fechaFin = new Date(container.data('fecha-fin'));
        const duracionTotal = fechaFin - fechaInicio;
        
        // Verificar si hay extensión
        let maxFechaHito = fechaFin;
        $hitos.each(function() {
            const fechaHito = new Date($(this).data('fecha'));
            if (fechaHito > maxFechaHito) {
                maxFechaHito = fechaHito;
            }
        });
        
        // Si hay extensión, añadir indicador visual
        if (maxFechaHito > fechaFin) {
            $('.pt-timeline-line').addClass('extended');
            const extensionDias = Math.ceil((maxFechaHito - fechaFin) / (1000 * 60 * 60 * 24));
            $('.pt-timeline-dates-bar').append(
                '<div class="extension-notice" style="position: absolute; right: 10px; top: 35px; background: #ff6b6b; color: white; padding: 5px 10px; border-radius: 5px; font-size: 11px;">' +
                'Extensión: +' + extensionDias + ' días' +
                '</div>'
            );
        }
        
        // Posicionar cada hito
        $hitos.each(function(index) {
            const $hito = $(this);
            const fechaHito = new Date($hito.data('fecha'));
            
            // Calcular posición porcentual
            let posicionPorcentaje;
            if (fechaHito <= fechaFin) {
                const tiempoTranscurrido = fechaHito - fechaInicio;
                posicionPorcentaje = (tiempoTranscurrido / duracionTotal) * 100;
            } else {
                // Si está fuera del rango, colocar en la zona de extensión
                const duracionExtendida = maxFechaHito - fechaInicio;
                const tiempoTranscurrido = fechaHito - fechaInicio;
                posicionPorcentaje = (tiempoTranscurrido / duracionExtendida) * 100;
            }
            
            // Limitar entre 2% y 98% para evitar que salga del contenedor
            posicionPorcentaje = Math.max(2, Math.min(98, posicionPorcentaje));
            
            $hito.css('left', posicionPorcentaje + '%');
        });
        
        // Crear array de datos de hitos para navegación
        hitosData = [];
        $hitos.each(function(index) {
            hitosData.push({
                index: index,
                element: $(this),
                id: $(this).data('hito-id')
            });
        });
    }
    
    /**
     * Inicializar click en hitos
     */
    function initHitoClick() {
        $('.pt-hito').on('click', function() {
            const hitoId = $(this).data('hito-id');
            const hitoIndex = $(this).index();
            currentHitoIndex = hitoIndex;
            
            loadHitoModal(hitoId);
        });
    }
    
    /**
     * Cargar modal del hito
     */
    function loadHitoModal(hitoId) {
        $.ajax({
            url: ptAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'pt_get_hito_modal',
                hito_id: hitoId,
                nonce: ptAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showModal(response.data);
                }
            },
            error: function() {
                alert('Error al cargar el hito');
            }
        });
    }
    
    /**
     * Mostrar modal
     */
    function showModal(data) {
        // Crear o actualizar modal
        let $modal = $('#pt-hito-modal');
        if (!$modal.length) {
            $('body').append('<div id="pt-hito-modal" class="pt-modal-overlay"></div>');
            $modal = $('#pt-hito-modal');
        }
        
        // Construir HTML del modal
        const estadoClase = data.estado || 'en_proceso';
        const estadoTexto = {
            'pendiente': 'Pendiente',
            'en_proceso': 'En Proceso',
            'finalizado': 'Finalizado'
        }[estadoClase] || 'En Proceso';
        
        let carouselHTML = '';
        if (data.imagenes && data.imagenes.length > 0) {
            carouselHTML = '<div class="pt-modal-carousel">';
            data.imagenes.forEach((img, index) => {
                carouselHTML += `<img src="${img}" class="pt-carousel-image ${index === 0 ? 'active' : ''}" alt="Imagen ${index + 1}">`;
            });
            
            if (data.imagenes.length > 1) {
                carouselHTML += `
                    <button class="pt-carousel-btn prev" onclick="window.ptPrevImage()">‹</button>
                    <button class="pt-carousel-btn next" onclick="window.ptNextImage()">›</button>
                    <div class="pt-carousel-indicators">
                        ${data.imagenes.map((_, index) => 
                            `<div class="pt-carousel-indicator ${index === 0 ? 'active' : ''}" data-index="${index}"></div>`
                        ).join('')}
                    </div>
                `;
            }
            carouselHTML += '</div>';
        }
        
        const modalHTML = `
            <div class="pt-modal-content">
                <button class="pt-modal-close" onclick="window.ptCloseModal()">×</button>
                
                <div class="pt-modal-header">
                    <div class="pt-modal-fecha">Ficha ENTRADA ${data.numero || ''}</div>
                    <div class="pt-modal-fecha">${data.fecha}</div>
                    <div class="pt-modal-estado ${estadoClase}">${estadoTexto}</div>
                    <h2 class="pt-modal-titulo">${data.titulo}</h2>
                </div>
                
                <div class="pt-modal-body">
                    <div class="pt-modal-descripcion">
                        ${data.descripcion}
                    </div>
                    <div>
                        ${carouselHTML}
                    </div>
                </div>
                
                <div class="pt-modal-navigation">
                    <button class="pt-modal-nav-btn prev" onclick="window.ptPrevHito()" ${currentHitoIndex === 0 ? 'disabled' : ''}>
                        ◄ ANTERIOR
                    </button>
                    <button class="pt-modal-nav-btn next" onclick="window.ptNextHito()" ${currentHitoIndex >= hitosData.length - 1 ? 'disabled' : ''}>
                        SIGUIENTE ►
                    </button>
                </div>
            </div>
        `;
        
        $modal.html(modalHTML);
        $modal.addClass('active');
        $('body').css('overflow', 'hidden');
        
        // Inicializar controles del carrusel
        initCarouselIndicators();
    }
    
    /**
     * Inicializar indicadores del carrusel
     */
    function initCarouselIndicators() {
        $('.pt-carousel-indicator').on('click', function() {
            const index = $(this).data('index');
            goToImage(index);
        });
    }
    
    /**
     * Ir a imagen específica
     */
    function goToImage(index) {
        $('.pt-carousel-image').removeClass('active').eq(index).addClass('active');
        $('.pt-carousel-indicator').removeClass('active').eq(index).addClass('active');
    }
    
    /**
     * Controles del modal
     */
    function initModalControls() {
        // Cerrar modal con ESC
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
        
        // Cerrar al hacer click fuera del modal
        $(document).on('click', '.pt-modal-overlay', function(e) {
            if ($(e.target).hasClass('pt-modal-overlay')) {
                closeModal();
            }
        });
        
        // Navegación con flechas del teclado
        $(document).on('keydown', function(e) {
            if ($('#pt-hito-modal').hasClass('active')) {
                if (e.key === 'ArrowLeft') {
                    window.ptPrevHito();
                } else if (e.key === 'ArrowRight') {
                    window.ptNextHito();
                }
            }
        });
    }
    
    /**
     * Cerrar modal
     */
    function closeModal() {
        $('#pt-hito-modal').removeClass('active');
        $('body').css('overflow', '');
    }
    
    /**
     * Navegación entre hitos
     */
    window.ptPrevHito = function() {
        if (currentHitoIndex > 0) {
            currentHitoIndex--;
            const hitoId = hitosData[currentHitoIndex].id;
            loadHitoModal(hitoId);
        }
    };
    
    window.ptNextHito = function() {
        if (currentHitoIndex < hitosData.length - 1) {
            currentHitoIndex++;
            const hitoId = hitosData[currentHitoIndex].id;
            loadHitoModal(hitoId);
        }
    };
    
    /**
     * Navegación del carrusel
     */
    window.ptPrevImage = function() {
        const $images = $('.pt-carousel-image');
        const $current = $images.filter('.active');
        const currentIndex = $images.index($current);
        const newIndex = currentIndex > 0 ? currentIndex - 1 : $images.length - 1;
        goToImage(newIndex);
    };
    
    window.ptNextImage = function() {
        const $images = $('.pt-carousel-image');
        const $current = $images.filter('.active');
        const currentIndex = $images.index($current);
        const newIndex = currentIndex < $images.length - 1 ? currentIndex + 1 : 0;
        goToImage(newIndex);
    };
    
    window.ptCloseModal = closeModal;
    
    // Inicializar cuando el documento esté listo
    $(document).ready(function() {
        initTimeline();
    });
    
})(jQuery);