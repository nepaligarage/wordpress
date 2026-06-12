<?php
/**
 * News listing.
 *
 * @package NepaliGarage
 */

defined( 'ABSPATH' ) || exit;

global $wp_query;
$wp_query->is_404 = false;
status_header( 200 );

get_header();

$posts = get_posts( [
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
] );
?>

<main id="primary" class="site-main">
    <section class="ng-section">
        <div class="ng-container">
            <div class="ng-section__header">
                <span class="ng-section__eyebrow">News</span>
                <h1>Nepal vehicle news and guides</h1>
                <p>Published NepaliGarage articles and research notes.</p>
            </div>

            <?php if ( empty( $posts ) ) : ?>
                <p class="ng-empty">No articles are published yet.</p>
            <?php else : ?>
                <div class="ng-cards ng-cards--3col">
                    <?php foreach ( $posts as $post ) : setup_postdata( $post ); ?>
                        <a class="ng-post-card" href="<?php the_permalink(); ?>">
                            <h2 class="ng-post-card__title"><?php the_title(); ?></h2>
                            <p class="ng-post-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?></p>
                            <span class="ng-post-card__meta"><?php echo esc_html( get_the_date() ); ?></span>
                        </a>
                    <?php endforeach; wp_reset_postdata(); ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
get_footer();

