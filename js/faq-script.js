jQuery(document).ready(function($) {
    'use strict';

    // Проверяем, что данные FAQ доступны
    if (typeof faqData === 'undefined' || !faqData.questions || faqData.questions.length === 0) {
        $('#faq-accordion').html('<div class="faq-error">No FAQ questions available. Please add questions in the admin panel.</div>');
        return;
    }

    // Рендерим вопросы
    var html = '';
    $.each(faqData.questions, function(index, item) {
        html += '<div class="faq-item" data-index="' + index + '">';
        html += '    <div class="faq-question" role="button" aria-expanded="false" tabindex="0">';
        html += '        <span class="faq-question-text">' + item.question + '</span>';
        html += '    </div>';
        html += '    <div class="faq-answer">';
        html += '        <div class="faq-answer-content">' + item.answer + '</div>';
        html += '    </div>';
        html += '</div>';
    });

    $('#faq-accordion').html(html);

    // Поисковая логика
    var $searchInput = $('#faq-search');
    var $noResults = $('.faq-no-results');

    $searchInput.on('input', function() {
        var searchTerm = $(this).val().toLowerCase().trim();
        var foundItems = 0;

        // Если поисковый запрос пустой - показываем все
        if (searchTerm === '') {
            $('.faq-item').show();
            $noResults.hide();
            return;
        }

        // Фильтруем вопросы и ответы
        $('.faq-item').each(function() {
            var $item = $(this);
            var question = $item.find('.faq-question-text').text().toLowerCase();
            var answer = $item.find('.faq-answer-content').text().toLowerCase();

            // Показываем только совпадающие элементы
            if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                $item.show();
                foundItems++;
            } else {
                $item.hide();
            }
        });

        // Показываем/скрываем сообщение "no results"
        if (foundItems === 0) {
            $noResults.show();
        } else {
            $noResults.hide();
        }
    });

    // Основная логика аккордеона
    $(document).on('click', '.faq-question', function(e) {
        e.preventDefault();

        var $question = $(this);
        var $answer = $question.next('.faq-answer');
        var isExpanded = $question.hasClass('active');

        // Закрываем все открытые вопросы
        $('.faq-question').removeClass('active').attr('aria-expanded', 'false');
        $('.faq-answer').removeClass('show');

        // Раскрываем выбранный вопрос, если он был закрыт
        if (!isExpanded) {
            $question.addClass('active').attr('aria-expanded', 'true');
            $answer.addClass('show');

            // Плавная прокрутка к открытому вопросу
            $('html, body').animate({
                scrollTop: $question.offset().top - 100
            }, 400);
        }
    });

    // Поддержка клавиатуры
    $(document).on('keydown', '.faq-question', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(this).trigger('click');
        }
    });

    // Закрытие при нажатии Escape
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('.faq-question').removeClass('active').attr('aria-expanded', 'false');
            $('.faq-answer').removeClass('show');
        }
    });
});