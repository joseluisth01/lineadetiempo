/**
 * Gestión del Modal de Hitos
 * 
 * Ubicación: assets/js/modal.js
 */

(function($) {
    'use strict';
    
    // Variables del carrusel
    let currentImageIndex = 0;
    let totalImages = 0;
    
    /**
     * Objeto Modal
     */
    const PTModal = {
        
        /**
         * Abrir modal con datos del hito
         */
        open: function(hitoData) {
            this.render(hitoData);
            this.show();
            this.bindEvents();
            this.resetCarousel();
        },
        
        /**
         * Renderizar contenido del modal
         */
        render: function(data) {
            const estadoClases = {
                'pendiente': 'pendiente',
                'en_proceso': 'en_proceso',
                'finalizado': 'finalizado'
            };
            
            const estadoTextos = {
                'pendiente': 'PENDIENTE',
                'en_proceso': 'EN PROCESO',
                'finalizado': 'FINALIZADO'
            };
            
            const estadoClase = estadoClases[data.estado] || 'en_proceso';
            const estadoTexto = estadoTextos[data.estado] || 'EN PROCESO';
            
            // Construir carrusel de imágenes
            let carouselHTML = '';
            if (data.imagenes && data.imagenes.length > 0) {
                totalImages = data.imagenes.length;
                
                carouselHTML = '<div class="pt-modal-carousel">';
                
                // Imágenes
                data.imagenes.forEach((img, index) => {
                    carouselHTML += `
                        <img src="${img}" 
                             class="pt-carousel-image ${index === 0 ? 'active' : ''}" 
                             alt="Imagen ${index + 1}"
                             data-index="${index}">
                    `;
                });
                
                // Controles de navegación (si hay más de 1 imagen)
                if (data.imagenes.length > 1) {
                    carouselHTML += `
                        <button class="pt-carousel-btn prev" id="pt-carousel-prev">
                            <span>‹</span>
                        </button>
                        <button class="pt-carousel-btn next" id="pt-carousel-next">
                            <span>›</span>
                        </button>
                    `;
                    
                    // Indicadores
                    carouselHTML += '<div class="pt-carousel-indicators">';
                    data.imagenes.forEach((_, index) => {
                        carouselHTML += `
                            <div class="pt-carousel-indicator ${index === 0 ? 'active' : ''}" 
                                 data-index="${index}"></div>
                        `;
                    });
                    carouselHTML += '</div>';
                }
                
                carouselHTML += '</div>';
            } else {
                carouselHTML = '<div class="pt-modal-no-images"><p>No hay imágenes para este hito</p></div>';
            }
            
            // HTML completo del modal
            const modalHTML = `
                <div class="pt-modal-content">
                    <button class="pt-modal-close" id="pt-modal-close-btn">
                        <span>×</span>
                    </button>
                    
                    <div class="pt-modal-header">
                        ${data.numero ? `<div class="pt-modal-fecha">Ficha ENTRADA ${data.numero}</div>` : ''}
                        <div class="pt-modal-fecha">${data.fecha}</div>
                        <div class="pt-modal-estado ${estadoClase}">${estadoTexto}</div>
                        <h2 class="pt-modal-titulo">${data.titulo}</h2>
                    </div>
                    
                    <div class="pt-modal-body">
                        <div class="pt-modal-descripcion">
                            ${data.descripcion}
                        </div>
                        <div class="pt-modal-carousel-wrapper">
                            ${carouselHTML}
                        </div>
                    </div>
                    
                    <div class="pt-modal-navigation">
                        <button class="pt-modal-nav-btn prev" id="pt-modal-prev-hito" 
                                ${data.hasPrev ? '' : 'disabled'}>
                            <span>◄</span> ANTERIOR
                        </button>
                        <button class="pt-modal-nav-btn next" id="pt-modal-next-hito"
                                ${data.hasNext ? '' : 'disabled'}>
                            SIGUIENTE <span>►</span>
                        </button>
                    </div>
                </div>
            `;
            
            // Actualizar o crear el modal
            let $modal = $('#pt-hito-modal');
            if (!$modal.length) {
                $('body').append('<div id="pt-hito-modal" class="pt-modal-overlay"></div>');
                $modal = $('#pt-hito-modal');
            }
            
            $modal.html(modalHTML);
        },
        
        /**
         * Mostrar modal
         */
        show: function() {
            $('#pt-hito-modal').addClass('active');
            $('body').css('overflow', 'hidden');
            currentImageIndex = 0;
        },
        
        /**
         * Cerrar modal
         */
        close: function() {
            $('#pt-hito-modal').removeClass('active');
            $('body').css('overflow', '');
            this.unbindEvents();
        },
        
        /**
         * Vincular eventos
         */
        bindEvents: function() {
            const self = this;
            
            // Botón cerrar
            $(document).on('click', '#pt-modal-close-btn', function(e) {
                e.preventDefault();
                self.close();
            });
            
            // Click fuera del modal
            $(document).on('click', '#pt-hito-modal', function(e) {
                if ($(e.target).is('#pt-hito-modal')) {
                    self.close();
                }
            });
            
            // Tecla ESC
            $(document).on('keydown.ptmodal', function(e) {
                if (e.key === 'Escape') {
                    self.close();
                }
            });
            
            // Navegación del carrusel
            $(document).on('click', '#pt-carousel-prev', function(e) {
                e.preventDefault();
                self.prevImage();
            });
            
            $(document).on('click', '#pt-carousel-next', function(e) {
                e.preventDefault();
                self.nextImage();
            });
            
            // Click en indicadores
            $(document).on('click', '.pt-carousel-indicator', function() {
                const index = $(this).data('index');
                self.goToImage(index);
            });
            
            // Navegación entre hitos
            $(document).on('click', '#pt-modal-prev-hito', function(e) {
                e.preventDefault();
                if (!$(this).is(':disabled')) {
                    $(document).trigger('pt:prevHito');
                }
            });
            
            $(document).on('click', '#pt-modal-next-hito', function(e) {
                e.preventDefault();
                if (!$(this).is(':disabled')) {
                    $(document).trigger('pt:nextHito');
                }
            });
            
            // Flechas del teclado para el carrusel
            $(document).on('keydown.ptcarousel', function(e) {
                if ($('#pt-hito-modal').hasClass('active')) {
                    if (e.key === 'ArrowLeft') {
                        e.preventDefault();
                        self.prevImage();
                    } else if (e.key === 'ArrowRight') {
                        e.preventDefault();
                        self.nextImage();
                    }
                }
            });
        },
        
        /**
         * Desvincular eventos
         */
        unbindEvents: function() {
            $(document).off('keydown.ptmodal');
            $(document).off('keydown.ptcarousel');
            $(document).off('click', '#pt-modal-close-btn');
            $(document).off('click', '#pt-hito-modal');
            $(document).off('click', '#pt-carousel-prev');
            $(document).off('click', '#pt-carousel-next');
            $(document).off('click', '.pt-carousel-indicator');
            $(document).off('click', '#pt-modal-prev-hito');
            $(document).off('click', '#pt-modal-next-hito');
        },
        
        /**
         * Ir a imagen anterior
         */
        prevImage: function() {
            if (totalImages <= 1) return;
            
            currentImageIndex--;
            if (currentImageIndex < 0) {
                currentImageIndex = totalImages - 1;
            }
            
            this.updateCarousel();
        },
        
        /**
         * Ir a imagen siguiente
         */
        nextImage: function() {
            if (totalImages <= 1) return;
            
            currentImageIndex++;
            if (currentImageIndex >= totalImages) {
                currentImageIndex = 0;
            }
            
            this.updateCarousel();
        },
        
        /**
         * Ir a imagen específica
         */
        goToImage: function(index) {
            if (index < 0 || index >= totalImages) return;
            
            currentImageIndex = index;
            this.updateCarousel();
        },
        
        /**
         * Actualizar visualización del carrusel
         */
        updateCarousel: function() {
            // Actualizar imágenes
            $('.pt-carousel-image').removeClass('active')
                .eq(currentImageIndex).addClass('active');
            
            // Actualizar indicadores
            $('.pt-carousel-indicator').removeClass('active')
                .eq(currentImageIndex).addClass('active');
        },
        
        /**
         * Resetear carrusel
         */
        resetCarousel: function() {
            currentImageIndex = 0;
            totalImages = $('.pt-carousel-image').length;
            this.updateCarousel();
        }
    };
    
    // Exponer funciones globalmente para compatibilidad
    window.ptOpenModal = function(data) {
        PTModal.open(data);
    };
    
    window.ptCloseModal = function() {
        PTModal.close();
    };
    
    window.ptPrevImage = function() {
        PTModal.prevImage();
    };
    
    window.ptNextImage = function() {
        PTModal.nextImage();
    };
    
    // Inicialización
    $(document).ready(function() {
        // Auto-cerrar modal con animación suave
        $(document).on('click', '.pt-modal-overlay', function(e) {
            if ($(e.target).hasClass('pt-modal-overlay')) {
                PTModal.close();
            }
        });
    });
    
    // Exponer objeto PTModal globalmente
    window.PTModal = PTModal;
    
})(jQuery);