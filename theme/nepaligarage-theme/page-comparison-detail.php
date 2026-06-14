<?php
/**
 * Saved comparison detail page for /compare/[slug]/.
 *
 * @package NepaliGarage
 */

defined( 'ABSPATH' ) || exit;

global $wp_query;
$wp_query->is_404 = false;
status_header( 200 );

$uri   = parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
$parts = array_values( array_filter( explode( '/', trim( $uri, '/' ) ) ) );
$slug  = sanitize_title( $parts[1] ?? '' );

$comparison = [];
if ( $slug && class_exists( 'NG_Supabase' ) ) {
    $supabase   = new NG_Supabase();
    $comparison = $supabase->get_comparison_by_slug( $slug );
    if ( array_key_exists( 'published', $comparison ) && ! filter_var( $comparison['published'], FILTER_VALIDATE_BOOLEAN ) ) {
        $comparison = [];
    }
}

if ( empty( $comparison ) && $slug ) {
    $rows       = ngt_supabase_get( 'comparisons', [
        'select'    => '*',
        'slug'      => 'eq.' . $slug,
        'published' => 'eq.true',
        'limit'     => '1',
    ] );
    $comparison = $rows[0] ?? [];
}

$extract_slugs = static function ( array $record ): array {
    $candidate_keys = [
        'variant_slugs',
        'variants',
        'vehicle_slugs',
        'vehicles',
    ];

    foreach ( $candidate_keys as $key ) {
        if ( ! isset( $record[ $key ] ) || '' === $record[ $key ] ) {
            continue;
        }

        $value = $record[ $key ];
        if ( is_string( $value ) ) {
            $decoded = json_decode( $value, true );
            $value   = JSON_ERROR_NONE === json_last_error() ? $decoded : explode( ',', $value );
        }

        if ( ! is_array( $value ) ) {
            continue;
        }

        $slugs = [];
        foreach ( $value as $item ) {
            if ( is_array( $item ) ) {
                $item = $item['slug'] ?? $item['variant_slug'] ?? '';
            }

            $item = sanitize_title( (string) $item );
            if ( $item ) {
                $slugs[] = $item;
            }
        }

        $slugs = array_values( array_unique( $slugs ) );
        if ( count( $slugs ) >= 2 ) {
            return array_slice( $slugs, 0, 2 );
        }
    }

    return [];
};

$variant_slugs = $extract_slugs( $comparison );
$is_not_ready  = empty( $comparison ) || count( $variant_slugs ) < 2;

if ( $is_not_ready ) {
    global $wp_query;
    $wp_query->set_404();
    status_header( 404 );
    nocache_headers();
    $tpl_404 = get_query_template( '404' );
    if ( $tpl_404 ) {
        include $tpl_404;
    } else {
        get_header();
        echo '<main class="ng-main"><section class="ng-section"><div class="ng-container ng-container--narrow" style="text-align:center;padding:4rem 0;"><h1>404</h1><p>Comparison not found.</p><a href="' . esc_url( home_url( '/compare/' ) ) . '">Browse comparisons</a></div></section></main>';
        get_footer();
    }
    exit;
}

$page_title = $comparison['title'] ?? ucwords( str_replace( '-', ' ', $slug ) );
$page_copy  = ! empty( $comparison['meta_description'] )
    ? $comparison['meta_description']
    : 'A Nepal-first side-by-side comparison focused on real price, specification, and ownership tradeoffs.';

$methodology_points = [
    'Compare pricing, specifications, and category differences in one place.',
    'Use the Nepal context row where data exists to understand market-specific fit.',
    'Treat confidence badges seriously: official beats estimated, estimated beats unverified.',
];

get_header();
?>

<main id="primary" class="site-main ng-comparison-page">
    <section class="ng-comparison-hero">
        <div class="ng-container ng-container--wide">
            <nav class="ng-comparison-hero__breadcrumb" aria-label="Breadcrumb">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
                <span>/</span>
                <a href="<?php echo esc_url( home_url( '/compare/' ) ); ?>">Compare</a>
                <span>/</span>
                <span><?php echo esc_html( $page_title ); ?></span>
            </nav>

            <div class="ng-comparison-hero__layout">
                <div class="ng-comparison-hero__content">
                    <span class="ng-comparison-hero__eyebrow">Head-to-head research</span>
                    <h1 class="ng-comparison-hero__title"><?php echo esc_html( $page_title ); ?></h1>
                    <p class="ng-comparison-hero__copy"><?php echo esc_html( $page_copy ); ?></p>

                    <div class="ng-comparison-hero__actions">
                        <a href="#ng-comparison-workspace" class="ng-btn ng-btn--red ng-btn--lg">See Full Comparison</a>
                        <a href="<?php echo esc_url( home_url( '/compare/' ) ); ?>" class="ng-btn ng-btn--outline-white ng-btn--lg">More Comparisons</a>
                    </div>
                </div>

                <aside class="ng-comparison-hero__aside">
                    <div class="ng-comparison-hero__aside-card">
                        <span class="ng-comparison-hero__aside-label">What this page helps answer</span>
                        <p>Which option fits your budget, ownership expectations, and day-to-day needs better in Nepal.</p>
                    </div>

                    <div class="ng-comparison-hero__chips" aria-label="Comparison characteristics">
                        <span>Side-by-side specs</span>
                        <span>Nepal buyer context</span>
                        <span>Confidence-labeled data</span>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <section class="ng-comparison-intro">
        <div class="ng-container ng-container--wide">
            <div class="ng-comparison-intro__grid">
                <article class="ng-comparison-intro__card">
                    <h2>How to read this comparison</h2>
                    <ul class="ng-comparison-intro__list">
                        <?php foreach ( $methodology_points as $point ) : ?>
                        <li><?php echo esc_html( $point ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </article>

                <article class="ng-comparison-intro__card ng-comparison-intro__card--compact">
                    <h2>Decision mindset</h2>
                    <p>Don’t just look for the bigger spec. Look for the better fit: value for money, practicality, service confidence, and how much compromise each choice asks from you.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="ng-comparison-workspace" id="ng-comparison-workspace">
        <div class="ng-container ng-container--wide">
            <?php echo do_shortcode( '[ng_compare variant_ids="' . esc_attr( implode( ',', $variant_slugs ) ) . '"]' ); ?>
        </div>
    </section>
</main>

<?php
get_footer();
