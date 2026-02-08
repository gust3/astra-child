<?php
/**
 * Astra Child Theme Functions
 */

// Запрет прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Подключение стилей родительской темы
 */
add_action('wp_enqueue_scripts', 'astra_child_enqueue_styles');
function astra_child_enqueue_styles() {

    // Стили родительской темы
    wp_enqueue_style(
        'astra-parent-style',
        get_template_directory_uri() . '/style.css',
        array(),
        wp_get_theme(get_template())->get('Version')
    );

    // Стили дочерней темы
    wp_enqueue_style(
        'astra-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('astra-parent-style'),
        wp_get_theme()->get('Version')
    );

    wp_enqueue_style('dashicons');
}

/**
 * Подключение скриптов
 */
add_action('wp_enqueue_scripts', 'astra_child_enqueue_scripts');
function astra_child_enqueue_scripts() {

    // Кастомный JS
    wp_enqueue_script(
        'astra-child-script',
        get_stylesheet_directory_uri() . '/js/custom.js',
        array('jquery'),
        '1.0.0',
        true
    );
}

/**
 * Инициализация темы
 */
add_action('after_setup_theme', 'astra_child_theme_setup');
function astra_child_theme_setup() {

    // Поддержка заголовка темы
    add_theme_support('custom-header');

    // Поддержка фона
    add_theme_support('custom-background');

    // Поддержка изображений
    add_theme_support('post-thumbnails');
}

/**
 * Кастомные хуки и функции
 */

// Подключаем класс ДО инициализации
require_once get_stylesheet_directory() . '/includes/class-faq-manager.php';

// Initialize FAQ Manager - ИНИЦИАЛИЗИРУЕМ СРАЗУ!
$faq_manager_instance = FAQ_Manager::get_instance();
