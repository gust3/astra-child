<?php
/**
 * Template Name: Full Width FAQ
 * Description: FAQ page without sidebar
 */

get_header();
?>

    <div id="primary" class="content-area">
        <main id="main" class="site-main">
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                </header>

                <div class="entry-content">
                    <?php
                    // Выводим FAQ через шорткод
                    echo do_shortcode('[faq_accordion]');
                    ?>
                </div>
            </article>
        </main>
    </div>

<?php
get_footer();