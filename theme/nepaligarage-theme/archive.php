<?php get_header(); ?>

<main class="ng-main ng-container">
    <header class="ng-archive-header">
        <h1><?php
            if ( is_category() ) {
                echo 'Category: ' . esc_html( single_cat_title( '', false ) );
            } elseif ( is_tag() ) {
                echo 'Tagged: ' . esc_html( single_tag_title( '', false ) );
            } else {
                echo 'News &amp; Reviews';
            }
        ?></h1>
    </header>

    <?php if ( have_posts() ) : ?>
        <div class="ng-post-grid">
            <?php while ( have_posts() ) : the_post(); ?>
                <article <?php post_class( 'ng-post-card' ); ?>>
                    <?php if ( has_post_thumbnail() ) : ?>
                        <a href="<?php the_permalink(); ?>" class="ng-post-card__thumb">
                            <?php the_post_thumbnail( 'medium_large' ); ?>
                        </a>
                    <?php endif; ?>
                    <div class="ng-post-card__body">
                        <p class="ng-post-card__meta"><?php echo esc_html( get_the_date( 'M j, Y' ) ); ?></p>
                        <h2 class="ng-post-card__title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h2>
                        <p class="ng-post-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></p>
                        <a href="<?php the_permalink(); ?>" class="ng-btn ng-btn--outline ng-btn--sm">Read More</a>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
        <div class="ng-pagination">
            <?php the_posts_pagination( [ 'mid_size' => 2 ] ); ?>
        </div>
    <?php else : ?>
        <p class="ng-no-results">No posts found.</p>
    <?php endif; ?>
</main>

<?php get_footer(); ?>
