<?php
/**
 * Generic page template — renders full page content (shortcodes included).
 *
 * @package NepaliGarage
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main class="ng-main">
    <?php while ( have_posts() ) : the_post(); ?>

    <section class="ng-page-hero">
        <div class="ng-container">
            <h1 class="ng-page-hero__title"><?php the_title(); ?></h1>
        </div>
    </section>

    <section class="ng-section">
        <div class="ng-container ng-container--narrow ng-page-content">
            <?php the_content(); ?>
        </div>
    </section>

    <?php endwhile; ?>
</main>

<?php get_footer(); ?>
