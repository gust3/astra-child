<?php
/**
 * FAQ Manager Class
 *
 * @package Astra_Child
 */

if (!defined('ABSPATH')) {
    exit;
}

class FAQ_Manager {

    private static $instance = null;
    private $option_name = 'faq_questions';

    private function __construct() {
        $this->init_hooks();
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function init_hooks() {
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        // AJAX
        add_action('wp_ajax_add_faq_question', array($this, 'handle_ajax_add_question'));
        add_action('wp_ajax_remove_faq_question', array($this, 'handle_ajax_remove_question'));

        // Scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Shortcode
        add_shortcode('faq_accordion', array($this, 'render_shortcode'));

        // Auto insert on FAQ page (безопасная версия)
        add_filter('the_content', array($this, 'auto_insert_on_faq_page'), 100);
    }

    public function auto_insert_on_faq_page($content) {
        global $post;

        // Защита от вызовов в виджетах, сайдбарах и других местах
        if (!is_main_query() || !in_the_loop() || !is_page() || !$post) {
            return $content;
        }

        // Только для страниц с ярлыком 'faq' или 'help'
        if (!in_array($post->post_name, ['faq', 'help'])) {
            return $content;
        }

        // Заменяем контент на шорткод
        return do_shortcode('[faq_accordion]');
    }


    public function add_admin_menu() {
        add_menu_page(
            'FAQ Settings',
            'FAQ',
            'manage_options',
            'faq-settings',
            array($this, 'render_admin_page'),
            'dashicons-editor-help',
            6
        );
    }

    public function register_settings() {
        register_setting('faq_settings_group', $this->option_name);
    }

    public function render_admin_page() {
        // Handle save
        if (isset($_POST['faq_questions']) && current_user_can('manage_options')) {
            check_admin_referer('faq_settings_nonce');

            $questions = $_POST['faq_questions'];
            $clean_questions = [];

            foreach ($questions as $question) {
                if (!empty(trim($question['question']))) {
                    $clean_questions[] = [
                        'question' => sanitize_text_field($question['question']),
                        'answer' => wp_kses_post($question['answer'])
                    ];
                }
            }

            update_option($this->option_name, json_encode($clean_questions));

            echo '<div class="notice notice-success is-dismissible"><p><strong>FAQ saved!</strong></p></div>';
        }

        $faq_array = $this->get_questions();

        if (empty($faq_array)) {
            $faq_array = $this->get_default_questions();
            update_option($this->option_name, json_encode($faq_array));
        }
        ?>
        <div class="wrap">
            <h1>FAQ Settings</h1>
            <form method="post" id="faq-settings-form">
                <?php wp_nonce_field('faq_settings_nonce'); ?>
                <div id="faq-items-container">
                    <?php foreach ($faq_array as $index => $item): ?>
                        <div class="faq-item" data-index="<?php echo esc_attr($index); ?>">
                            <div class="faq-item-header">
                                <h3>Q<?php echo esc_html($index + 1); ?></h3>
                                <button type="button" class="button remove-faq-item" data-index="<?php echo esc_attr($index); ?>"<?php echo count($faq_array) <= 1 ? ' disabled' : ''; ?>>
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                            <input type="text" name="faq_questions[<?php echo esc_attr($index); ?>][question]"
                                   value="<?php echo esc_attr($item['question']); ?>" required
                                   placeholder="Question" class="regular-text">
                            <?php wp_editor($item['answer'], 'faq_answer_' . $index, [
                                'textarea_name' => 'faq_questions[' . $index . '][answer]',
                                'textarea_rows' => 5,
                                'media_buttons' => false,
                                'teeny' => true
                            ]); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" id="add-faq-item" class="button button-primary">
                    <span class="dashicons dashicons-plus"></span> Add Question
                </button>
                <p class="submit"><input type="submit" class="button-primary" value="Save"></p>
            </form>

            <style>
                .faq-item { margin: 15px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
                .faq-item-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
                .remove-faq-item { background: #dc3232; color: white; border: none; width: 36px; height: 36px; border-radius: 50%; padding: 0; }
                #add-faq-item { margin: 20px 0; }
            </style>

            <script>
                jQuery(document).ready(function($) {
                    $('#add-faq-item').on('click', function() {
                        $.post(ajaxurl, {action: 'add_faq_question'}, function() {
                            location.reload();
                        });
                    });

                    $(document).on('click', '.remove-faq-item:not(:disabled)', function() {
                        if (confirm('Delete?')) {
                            var index = $(this).data('index');
                            $.post(ajaxurl, {action: 'remove_faq_question', index: index}, function() {
                                location.reload();
                            });
                        }
                    });

                    $('#faq-settings-form').on('submit', function() {
                        if (typeof tinymce !== 'undefined') tinymce.triggerSave();
                    });
                });
            </script>
        </div>
        <?php
    }

    public function get_questions() {
        $data = get_option($this->option_name);
        return $data ? json_decode($data, true) : [];
    }

    private function get_default_questions() {
        return [
            ['question' => 'How to register?', 'answer' => 'Click "Register" button.'],
            ['question' => 'Payment methods?', 'answer' => 'We accept credit cards and PayPal.']
        ];
    }

    public function handle_ajax_add_question() {
        if (!current_user_can('manage_options')) wp_die('Nope');
        $faq = $this->get_questions();
        $faq[] = ['question' => '', 'answer' => ''];
        update_option($this->option_name, json_encode($faq));
        wp_send_json_success();
    }

    public function handle_ajax_remove_question() {
        if (!current_user_can('manage_options')) wp_die('Nope');
        $index = intval($_POST['index'] ?? -1);
        $faq = $this->get_questions();
        if (isset($faq[$index])) {
            unset($faq[$index]);
            update_option($this->option_name, json_encode(array_values($faq)));
            wp_send_json_success();
        }
        wp_send_json_error();
    }

    public function enqueue_scripts() {
        if (!is_page()) return;

        global $post;
        if (!$post || !in_array($post->post_name, ['faq', 'help'])) return;

        wp_enqueue_style('faq-styles', get_stylesheet_directory_uri() . '/css/faq-styles.css');
        wp_enqueue_script('faq-script', get_stylesheet_directory_uri() . '/js/faq-script.js', ['jquery'], '1.0', true);
        wp_localize_script('faq-script', 'faqData', [
            'questions' => $this->get_questions()
        ]);
    }

    public function render_shortcode() {
        $this->enqueue_scripts();
        $questions = $this->get_questions();

        if (empty($questions)) {
            return '<div class="faq-container"><div class="faq-error">No FAQ questions available. Please add questions in the admin panel.</div></div>';
        }

        ob_start();
        ?>
        <div class="faq-container">
            <!-- Поисковое поле -->
            <div class="faq-search-container">
                <input type="text" id="faq-search" placeholder="Search questions..." aria-label="Search FAQ">
                <div class="faq-no-results">No questions found matching your search.</div>
            </div>

            <div class="faq-header">
                <h2 class="faq-title" style="text-align: center; color: #2c3e50; margin-bottom: 20px; font-size: 2em;">Frequently Asked Questions</h2>
                <p class="faq-description" style="text-align: center; color: #7f8c8d; margin-bottom: 30px; font-size: 1.1em;">Find answers to common questions below</p>
            </div>

            <div id="faq-accordion" class="faq-accordion">
                <?php foreach ($questions as $index => $item): ?>
                    <div class="faq-item" data-index="<?php echo esc_attr($index); ?>">
                        <div class="faq-question" role="button" aria-expanded="false" tabindex="0">
                            <span class="faq-question-text"><?php echo esc_html($item['question']); ?></span>
                        </div>
                        <div class="faq-answer">
                            <div class="faq-answer-content"><?php echo wp_kses_post($item['answer']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}