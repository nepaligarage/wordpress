<?php
/**
 * Brand archive for /cars/[brand]/.
 *
 * @package NepaliGarage
 */

defined( 'ABSPATH' ) || exit;

global $wp_query;
$wp_query->is_404 = false;
status_header( 200 );

$uri        = parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
$parts      = array_values( array_filter( explode( '/', trim( $uri, '/' ) ) ) );
$brand_slug = sanitize_title( $parts[1] ?? '' );

$brand_rows = $brand_slug ? ngt_supabase_get( 'brands', [
    'select' => 'id,name,slug,logo_url,type',
    'slug'   => 'eq.' . $brand_slug,
    'limit'  => '1',
] ) : [];
$brand = $brand_rows[0] ?? [];

if ( $brand_slug && empty( $brand ) ) {
    global $wp_query;
    $wp_query->set_404();
    status_header( 404 );
    nocache_headers();
    include get_query_template( '404' );
    return;
}

$all_variants = $brand_slug ? ngt_supabase_get( 'variants', [
    'select'             => 'id,name,slug,starting_price_npr,model(name,slug,body_type,brand(name,slug))',
    'is_available_nepal' => 'eq.true',
    'order'              => 'name.asc',
    'limit'              => '150',
] ) : [];
$variants = array_values( array_filter( $all_variants, static function ( array $variant ) use ( $brand_slug ): bool {
    $model = $variant['model'] ?? $variant['models'] ?? [];
    $brand = $model['brand'] ?? $model['brands'] ?? [];

    return ( $brand['slug'] ?? '' ) === $brand_slug;
} ) );
$price_rows = ngt_prices_for_variants( wp_list_pluck( $variants, 'id' ) );

get_header();
?>

<main id="primary" class="site-main">
    <section class="ng-section">
        <div class="ng-container">
            <div class="ng-section__header">
                <span class="ng-section__eyebrow">Brand archive</span>
                <h1><?php echo esc_html( $brand['name'] ?? ucwords( str_replace( '-', ' ', $brand_slug ) ) ); ?> vehicles in Nepal</h1>
                <p>Tracked models and variants for this brand. Specs and prices are expanded as reliable sources are added.</p>
            </div>

            <?php if ( empty( $variants ) ) : ?>
                <p class="ng-empty">No available Nepal variants are tracked for this brand yet.</p>
            <?php else : ?>
                <div class="ng-vehicle-grid">
                    <?php foreach ( $variants as $variant ) :
                        $model = $variant['model'] ?? $variant['models'] ?? [];
                        $brand_for_variant = $model['brand'] ?? $model['brands'] ?? $brand;
                        $variant_brand_slug = $brand_for_variant['slug'] ?? $brand_slug;
                        $model_slug = $model['slug'] ?? '';
                        $href = ( $variant_brand_slug && $model_slug )
                            ? home_url( '/cars/' . $variant_brand_slug . '/' . $model_slug . '/' )
                            : home_url( '/cars/' . $brand_slug . '/' );
                        ?>
                        <a class="ng-vehicle-card" href="<?php echo esc_url( $href ); ?>">
                            <span class="ng-vehicle-card__badge"><?php echo esc_html( ucfirst( $model['body_type'] ?? 'Vehicle' ) ); ?></span>
                            <h2><?php echo esc_html( $model['name'] ?? 'Vehicle' ); ?></h2>
                            <p><?php echo esc_html( $variant['name'] ?? 'Available variant' ); ?></p>
                            <?php $price = ngt_variant_price_display( $variant, $price_rows ); ?>
                            <strong><?php echo esc_html( $price['label'] ); ?></strong>
                            <span class="ng-card-price-meta"><?php echo wp_kses_post( ngt_price_badge_html( $price ) ); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
get_footer();
