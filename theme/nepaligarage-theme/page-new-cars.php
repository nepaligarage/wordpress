<?php
/**
 * New cars listing.
 *
 * @package NepaliGarage
 */

defined( 'ABSPATH' ) || exit;

global $wp_query;
$wp_query->is_404 = false;
status_header( 200 );

$variants = ngt_supabase_get( 'variants', [
    'select'             => 'id,name,slug,starting_price_npr,model(name,slug,body_type,brand(name,slug))',
    'is_available_nepal' => 'eq.true',
    'order'              => 'name.asc',
    'limit'              => '100',
] );
$price_rows = ngt_prices_for_variants( wp_list_pluck( $variants, 'id' ) );

get_header();
?>

<main id="primary" class="site-main">
    <section class="ng-section">
        <div class="ng-container">
            <div class="ng-section__header">
                <span class="ng-section__eyebrow">Vehicle database</span>
                <h1>New Cars in Nepal</h1>
                <p>Browse Nepal-relevant vehicles currently tracked by NepaliGarage.</p>
            </div>

            <?php if ( empty( $variants ) ) : ?>
                <p class="ng-empty">No vehicles are available yet.</p>
            <?php else : ?>
                <div class="ng-vehicle-grid">
                    <?php foreach ( $variants as $variant ) :
                        $model = $variant['model'] ?? $variant['models'] ?? [];
                        $brand = $model['brand'] ?? $model['brands'] ?? [];
                        $brand_slug = $brand['slug'] ?? '';
                        $model_slug = $model['slug'] ?? '';
                        $href = ( $brand_slug && $model_slug ) ? home_url( '/cars/' . $brand_slug . '/' . $model_slug . '/' ) : home_url( '/cars/' );
                        ?>
                        <a class="ng-vehicle-card" href="<?php echo esc_url( $href ); ?>">
                            <span class="ng-vehicle-card__badge"><?php echo esc_html( ucfirst( $model['body_type'] ?? 'Vehicle' ) ); ?></span>
                            <h2><?php echo esc_html( trim( ( $brand['name'] ?? '' ) . ' ' . ( $model['name'] ?? '' ) ) ); ?></h2>
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
