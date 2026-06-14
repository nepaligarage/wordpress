<?php
/*
 * Template Name: Compare Cars
 */
global $wp_query;
$wp_query->is_404 = false;
status_header( 200 );

get_header();

$comparisons = ngt_supabase_get( 'comparisons', [
    'select'    => 'id,slug,title,meta_description,created_at',
    'published' => 'eq.true',
    'order'     => 'created_at.desc',
] );

$total_comparisons = count( $comparisons );
$featured_comparisons = array_slice( $comparisons, 0, 3 );
$latest_comparisons   = array_slice( $comparisons, 0, 9 );

$hero_title = 'Compare Cars in Nepal with Real Buying Context';
$hero_copy  = 'Research side-by-side pricing, specifications, known issues, and practical ownership tradeoffs before you shortlist your next car.';
?>

<main class="ng-main ng-compare-hub">
    <section class="ng-compare-hub-hero">
        <div class="ng-container ng-container--wide">
            <div class="ng-compare-hub-hero__layout">
                <div class="ng-compare-hub-hero__content">
                    <span class="ng-compare-hub-hero__eyebrow">NepaliGarage Comparison Engine</span>
                    <h1 class="ng-compare-hub-hero__title"><?php echo esc_html( $hero_title ); ?></h1>
                    <p class="ng-compare-hub-hero__copy"><?php echo esc_html( $hero_copy ); ?></p>

                    <div class="ng-compare-hub-hero__actions">
                        <a href="#ng-compare-library" class="ng-btn ng-btn--red ng-btn--lg">Browse Comparisons</a>
                        <a href="<?php echo esc_url( home_url( '/new-cars/' ) ); ?>" class="ng-btn ng-btn--outline-white ng-btn--lg">Research Vehicles</a>
                    </div>

                    <div class="ng-compare-hub-hero__chips" aria-label="Platform highlights">
                        <span>Nepal pricing context</span>
                        <span>Spec differences that matter</span>
                        <span>Known issues and tradeoffs</span>
                        <span>Fast shortlist support</span>
                    </div>
                </div>

                <aside class="ng-compare-hub-hero__panel" aria-label="Comparison engine overview">
                    <div class="ng-compare-hub-metric-grid">
                        <div class="ng-compare-hub-metric-card">
                            <strong><?php echo esc_html( (string) $total_comparisons ); ?></strong>
                            <span>Published head-to-heads</span>
                        </div>
                        <div class="ng-compare-hub-metric-card">
                            <strong>2 cars</strong>
                            <span>Side-by-side at a time</span>
                        </div>
                        <div class="ng-compare-hub-metric-card">
                            <strong>Nepal-first</strong>
                            <span>Pricing, specs, and buyer context</span>
                        </div>
                        <div class="ng-compare-hub-metric-card">
                            <strong>Clear verdicts</strong>
                            <span>Useful framing over raw tables</span>
                        </div>
                    </div>

                    <div class="ng-compare-hub-note">
                        <p class="ng-compare-hub-note__label">What makes this useful</p>
                        <p class="ng-compare-hub-note__body">Every comparison is designed to answer the real buyer question: which vehicle fits your budget, use case, and compromise profile better in Nepal?</p>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <section class="ng-compare-hub-benefits">
        <div class="ng-container ng-container--wide">
            <div class="ng-compare-hub-section-head">
                <span class="ng-compare-hub-section-head__eyebrow">Why compare here</span>
                <h2>Built for decision-making, not just data display</h2>
                <p>Use NepaliGarage when you want the answer behind the numbers — where one option fits better, where another asks fewer compromises, and what matters in Nepal specifically.</p>
            </div>

            <div class="ng-compare-hub-benefit-grid">
                <article class="ng-compare-hub-benefit-card">
                    <h3>Understand real price gaps</h3>
                    <p>See where one option stretches the budget and whether the extra money buys meaningful value.</p>
                </article>
                <article class="ng-compare-hub-benefit-card">
                    <h3>Read specs in plain English</h3>
                    <p>We surface the category differences buyers actually compare instead of forcing you through a spreadsheet mindset.</p>
                </article>
                <article class="ng-compare-hub-benefit-card">
                    <h3>Evaluate Nepal fit</h3>
                    <p>Pricing context, service considerations, and ownership practicality matter more than global brochure claims.</p>
                </article>
                <article class="ng-compare-hub-benefit-card">
                    <h3>Spot tradeoffs quickly</h3>
                    <p>It should be obvious where each vehicle wins, where it loses, and who each one suits better.</p>
                </article>
            </div>
        </div>
    </section>

    <?php if ( ! empty( $featured_comparisons ) ) : ?>
    <section class="ng-compare-hub-featured">
        <div class="ng-container ng-container--wide">
            <div class="ng-compare-hub-section-head ng-compare-hub-section-head--inline">
                <div>
                    <span class="ng-compare-hub-section-head__eyebrow">Editor’s picks</span>
                    <h2>Start with the most useful head-to-heads</h2>
                </div>
                <a href="#ng-compare-library" class="ng-link--more">See full library →</a>
            </div>

            <div class="ng-compare-hub-featured-grid">
                <?php foreach ( $featured_comparisons as $index => $comp ) : ?>
                <a href="<?php echo esc_url( home_url( '/compare/' . sanitize_title( $comp['slug'] ) . '/' ) ); ?>" class="ng-compare-feature-card">
                    <span class="ng-compare-feature-card__rank">0<?php echo esc_html( (string) ( $index + 1 ) ); ?></span>
                    <span class="ng-compare-feature-card__tag">Featured comparison</span>
                    <h3 class="ng-compare-feature-card__title"><?php echo esc_html( $comp['title'] ); ?></h3>
                    <?php if ( ! empty( $comp['meta_description'] ) ) : ?>
                        <p class="ng-compare-feature-card__copy"><?php echo esc_html( wp_trim_words( $comp['meta_description'], 22 ) ); ?></p>
                    <?php else : ?>
                        <p class="ng-compare-feature-card__copy">A decision-focused side-by-side comparison built for Nepal buyers.</p>
                    <?php endif; ?>
                    <span class="ng-compare-feature-card__cta">Open comparison →</span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="ng-compare-hub-library" id="ng-compare-library">
        <div class="ng-container ng-container--wide">
            <div class="ng-compare-hub-section-head ng-compare-hub-section-head--inline">
                <div>
                    <span class="ng-compare-hub-section-head__eyebrow">Comparison library</span>
                    <h2>Browse every published comparison</h2>
                </div>
                <p class="ng-compare-hub-section-head__meta"><?php echo esc_html( (string) $total_comparisons ); ?> published comparisons</p>
            </div>

            <?php if ( ! empty( $latest_comparisons ) ) : ?>
            <div class="ng-cards ng-cards--3col">
                <?php foreach ( $latest_comparisons as $comp ) : ?>
                <a href="<?php echo esc_url( home_url( '/compare/' . sanitize_title( $comp['slug'] ) . '/' ) ); ?>" class="ng-compare-card ng-compare-card--library">
                    <div class="ng-compare-card__tag">Comparison</div>
                    <h2 class="ng-compare-card__title"><?php echo esc_html( $comp['title'] ); ?></h2>
                    <?php if ( ! empty( $comp['meta_description'] ) ) : ?>
                        <p class="ng-compare-card__excerpt"><?php echo esc_html( wp_trim_words( $comp['meta_description'], 18 ) ); ?></p>
                    <?php else : ?>
                        <p class="ng-compare-card__excerpt">Side-by-side research with pricing, specs, buyer-fit context, and key tradeoffs.</p>
                    <?php endif; ?>
                    <span class="ng-compare-card__cta">View Comparison →</span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else : ?>
            <div class="ng-compare-hub-empty">
                <h2>Comparisons are being prepared</h2>
                <p>We are building decision-useful head-to-head pages with pricing, specs, and Nepal ownership context. Check back shortly.</p>
            </div>
            <?php endif; ?>

            <?php the_content(); ?>
        </div>
    </section>
</main>

<?php get_footer(); ?>
