<?php
/*
 * Template Name: Compare Cars
 */
get_header();

$comparisons = ngt_supabase_get( 'comparisons', [
    'select'    => 'id,slug,title,meta_description,created_at',
    'published' => 'eq.true',
    'order'     => 'created_at.desc',
] );
?>

<main class="ng-main">

    <div class="ng-page-hero ng-page-hero--blue">
        <div class="ng-container">
            <h1>Compare Cars in Nepal</h1>
            <p>Side-by-side specs, Nepal on-road prices, and honest verdicts — all from official sources.</p>
        </div>
    </div>

    <div class="ng-container ng-container--wide">

        <?php if ( ! empty( $comparisons ) ) : ?>
        <div class="ng-cards ng-cards--3col" style="margin-top:2rem;">
            <?php foreach ( $comparisons as $comp ) : ?>
            <a href="<?php echo esc_url( home_url( '/compare/' . sanitize_title( $comp['slug'] ) . '/' ) ); ?>" class="ng-compare-card">
                <div class="ng-compare-card__tag">Comparison</div>
                <h2 class="ng-compare-card__title"><?php echo esc_html( $comp['title'] ); ?></h2>
                <?php if ( ! empty( $comp['meta_description'] ) ) : ?>
                    <p class="ng-compare-card__excerpt"><?php echo esc_html( wp_trim_words( $comp['meta_description'], 18 ) ); ?></p>
                <?php endif; ?>
                <span class="ng-compare-card__cta">View Comparison →</span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
        <div style="text-align:center; padding:4rem 2rem;">
            <p style="color:#64748b; font-size:1.1rem;">Our first comparisons are coming soon. Check back shortly.</p>
        </div>
        <?php endif; ?>

        <?php the_content(); ?>

    </div>
</main>

<?php get_footer(); ?>
