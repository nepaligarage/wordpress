<?php
defined( 'ABSPATH' ) || exit;

status_header( 404 );
nocache_headers();

get_header();
?>

<main class="ng-main">
    <section class="ng-section">
        <div class="ng-container ng-container--narrow" style="text-align:center;padding:4rem 0;">
            <h1 style="font-size:4rem;margin-bottom:0.5rem;">404</h1>
            <p style="font-size:1.25rem;margin-bottom:2rem;">Page not found</p>
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="ng-btn ng-btn--primary">Back to Home</a>
        </div>
    </section>
</main>

<?php get_footer();
