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

$uri  = parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
$parts = array_values( array_filter( explode( '/', trim( $uri, '/' ) ) ) );
$slug = sanitize_title( $parts[1] ?? '' );

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
$is_not_ready = empty( $comparison ) || count( $variant_slugs ) < 2;

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

get_header();
?>

<main id="primary" class="site-main">
    <section class="ng-section">
        <div class="ng-container">
            <div class="ng-section__header">
                <span class="ng-section__eyebrow">Comparison</span>
                <h1><?php echo esc_html( $comparison['title'] ?? ucwords( str_replace( '-', ' ', $slug ) ) ); ?></h1>
                <?php if ( ! empty( $comparison['meta_description'] ) ) : ?>
                    <p><?php echo esc_html( $comparison['meta_description'] ); ?></p>
                <?php else : ?>
                    <p>Side-by-side Nepal vehicle comparison with source quality shown where data is available.</p>
                <?php endif; ?>
            </div>

            <?php echo do_shortcode( '[ng_compare variant_ids="' . esc_attr( implode( ',', $variant_slugs ) ) . '"]' ); ?>
        </div>
    </section>
</main>

<?php
get_footer();
