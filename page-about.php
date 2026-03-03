<?php
/**
 * Template Name: About Us Page
 * Description: About Us page rendered from Gutenberg block content.
 */

get_header();
?>

<main class="wp-block-group has-global-padding is-layout-constrained wp-block-group-is-layout-constrained">
    <div class="entry-content wp-block-post-content has-global-padding is-layout-constrained wp-block-post-content-is-layout-constrained">
        <?php
        if (have_posts()) :
            while (have_posts()) : the_post();
                the_content();
            endwhile;
        endif;
        ?>
    </div>
</main>

<?php get_footer();
