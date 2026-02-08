jQuery(document).ready(function($) {
    'use strict';

    $(window).on('load', function() {

        // Перезаписываем функцию Astra
        window.astraNavMenuToggle = function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $menuContent = $('.ast-mobile-header-content');
            var $menuToggle = $(this);

            // Переключаем классы
            $menuContent.toggleClass('active');
            $('body').toggleClass('menu-open');

            // Корректно переключаем aria-expanded

            $menuToggle.attr('aria-expanded', function(i, val) {
                return $('body').hasClass('menu-open') ? 'true' : 'false';
            });
        };

        // Сбрасываем старые обработчики через клонирование
        $('.menu-toggle').each(function() {
            var $original = $(this);
            var $clone = $original.clone(true);

            $clone.attr('class', $original.attr('class'));
            $clone.attr('aria-expanded', $original.attr('aria-expanded'));
            $clone.attr('aria-label', $original.attr('aria-label'));
            $clone.attr('data-index', $original.attr('data-index'));
            $clone.html($original.html());

            $original.replaceWith($clone);
        });

        // Новый обработчик
        $(document).on('click', '.menu-toggle', window.astraNavMenuToggle);

        // Закрытие при клике вне меню
        $(document).on('click', function(e) {
            if (
                !$(e.target).closest('.ast-mobile-header-content').length &&
                !$(e.target).closest('.menu-toggle').length
            ) {
                $('.ast-mobile-header-content').removeClass('active');
                $('body').removeClass('menu-open');
                $('.menu-toggle').attr('aria-expanded', 'false');
            }
        });

        // Закрытие по ESC
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('body').hasClass('menu-open')) {
                $('.ast-mobile-header-content').removeClass('active');
                $('body').removeClass('menu-open');
                $('.menu-toggle').attr('aria-expanded', 'false');
            }
        });
    });
});