<?php get_header(); ?>

<main class="ng-main ng-container ng-container--article">
    <?php while ( have_posts() ) : the_post(); ?>

    <article <?php post_class( 'ng-article' ); ?> itemscope itemtype="https://schema.org/Article">

        <header class="ng-article__header">
            <div class="ng-article__meta">
                <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>" itemprop="datePublished">
                    <?php echo esc_html( get_the_date( 'F j, Y' ) ); ?>
                </time>
                <?php
                $cats = get_the_category();
                if ( $cats ) : ?>
                    <span class="ng-article__cat"><?php echo esc_html( $cats[0]->name ); ?></span>
                <?php endif; ?>
            </div>
            <h1 class="ng-article__title" itemprop="headline"><?php the_title(); ?></h1>
            <?php if ( has_post_thumbnail() ) : ?>
                <div class="ng-article__thumb">
                    <?php the_post_thumbnail( 'large' ); ?>
                </div>
            <?php endif; ?>
        </header>

        <div class="ng-article__content" itemprop="articleBody">
            <?php the_content(); ?>
        </div>

        <footer class="ng-article__footer">
            <div class="ng-article__tags">
                <?php the_tags( '<span class="ng-tag">', '</span><span class="ng-tag">', '</span>' ); ?>
            </div>
        </footer>

    </article>

    <?php endwhile; ?>
</main>

<?php get_footer(); ?>
