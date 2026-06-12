<?php
/**
 * Template Name: Vehicle Detail Page
 *
 * Fetches vehicle data from Supabase and renders a full vehicle detail page.
 * URL pattern: /cars/[brand]/[model]/
 *
 * @package NepaliGarage
 */

defined( 'ABSPATH' ) || exit;

// ── Resolve variant slug from URL ─────────────────────────────────────────────

$path_parts  = array_values( array_filter( explode( '/', trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' ) ) ) );
// /cars/toyota/fortuner/ → ['cars','toyota','fortuner']
$model_slug  = isset( $path_parts[2] ) ? sanitize_title( $path_parts[2] ) : '';
$brand_slug  = isset( $path_parts[1] ) ? sanitize_title( $path_parts[1] ) : '';

// ── Fetch model + variants from Supabase (server-side, cached) ────────────────

$variants = [];
$model    = null;
$brand    = null;

if ( $model_slug ) {
    // Get all variants for this model with model+brand join
    $rows = ngt_supabase_get(
        'variants',
        [
            'select'            => 'id,name,slug,year_from,starting_price_npr,body_description,image_url,thumbnail_url,is_featured,model_id,models!inner(id,name,slug,body_type,brands!inner(id,name,slug,logo_url)),variant_specs(*,spec_field(*),source:spec_sources(*))',
            'models.slug'       => 'eq.' . $model_slug,
            'is_available_nepal'=> 'eq.true',
            'order'             => 'starting_price_npr.asc',
        ],
        30 * MINUTE_IN_SECONDS
    );

    if ( ! empty( $rows ) ) {
        $variants = $rows;
        $model    = $rows[0]['models'] ?? null;
        $brand    = $model ? $model['brands'] ?? null : null;
    }
}

if ( $brand && ! empty( $brand['slug'] ) && $brand_slug !== $brand['slug'] ) {
    global $wp_query;
    $wp_query->set_404();
    status_header( 404 );
    nocache_headers();
    include get_query_template( '404' );
    return;
}

// ── Fetch accessories for first variant ──────────────────────────────────────

$accessories = [];
if ( ! empty( $variants ) ) {
    $first_id = $variants[0]['id'] ?? '';
    if ( $first_id ) {
        $acc_rows = ngt_supabase_get(
            'vehicle_accessories',
            [
                'select'     => 'display_order,is_oem,accessories!inner(id,name,slug,category,description,price_npr,image_url,affiliate_url)',
                'variant_id' => 'eq.' . $first_id,
                'order'      => 'display_order.asc',
                'limit'      => '6',
            ],
            30 * MINUTE_IN_SECONDS
        );
        foreach ( $acc_rows as $row ) {
            if ( ! empty( $row['accessories'] ) ) {
                $accessories[] = array_merge( $row['accessories'], [ 'is_oem' => $row['is_oem'] ?? false ] );
            }
        }
    }
}

// ── Active variant (first by default) ────────────────────────────────────────

$active = ! empty( $variants ) ? $variants[0] : null;
$price_rows = ngt_prices_for_variants( wp_list_pluck( $variants, 'id' ) );
$active_price = $active ? ngt_variant_price_display( $active, $price_rows ) : [];

if ( ! $active ) {
    // 404 fallback
    get_header();
    echo '<main class="ng-main"><div class="ng-container" style="padding:80px 0;text-align:center"><h1>Vehicle Not Found</h1><p><a href="' . esc_url( home_url() ) . '">Back to Home</a></p></div></main>';
    get_footer();
    exit;
}

// ── SEO / head ────────────────────────────────────────────────────────────────

$page_title    = esc_html( $brand['name'] . ' ' . $model['name'] . ' Price in Nepal — NepaliGarage' );
$page_desc     = esc_attr( wp_strip_all_tags( $active['body_description'] ?? '' ) );
$starting_price = $active_price['label'] ?? 'Price on request';

// Override WP SEO title for this template
add_filter( 'pre_get_document_title', fn() => $page_title );

get_header();
?>

<main class="ng-main ng-vehicle-page" id="ng-vehicle-main"
      data-model-slug="<?php echo esc_attr( $model_slug ); ?>"
      data-brand-slug="<?php echo esc_attr( $brand_slug ); ?>">

    <?php // ── Vehicle Hero ──────────────────────────────────────────────────── ?>
    <section class="ng-vehicle-hero">
        <div class="ng-container">
            <div class="ng-vehicle-hero__breadcrumb">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
                <span>›</span>
                <a href="<?php echo esc_url( home_url( '/cars/' ) ); ?>">Cars</a>
                <span>›</span>
                <a href="<?php echo esc_url( home_url( '/cars/' . ( $brand['slug'] ?? '' ) . '/' ) ); ?>"><?php echo esc_html( $brand['name'] ?? '' ); ?></a>
                <span>›</span>
                <span><?php echo esc_html( $model['name'] ?? '' ); ?></span>
            </div>

            <div class="ng-vehicle-hero__content">
                <div class="ng-vehicle-hero__info">
                    <?php if ( ! empty( $brand['logo_url'] ) ) : ?>
                        <img src="<?php echo esc_url( $brand['logo_url'] ); ?>" alt="<?php echo esc_attr( $brand['name'] ); ?>" class="ng-vehicle-hero__brand-logo" loading="lazy">
                    <?php endif; ?>

                    <div class="ng-vehicle-hero__badge"><?php echo esc_html( $model['body_type'] ?? 'Car' ); ?></div>
                    <h1 class="ng-vehicle-hero__title"><?php echo esc_html( $brand['name'] . ' ' . $model['name'] ); ?></h1>

                    <div class="ng-vehicle-hero__pricing">
                        <span class="ng-vehicle-hero__price-label">Starting from</span>
                        <span class="ng-vehicle-hero__price" id="ng-active-price"><?php echo esc_html( $starting_price ); ?></span>
                        <span class="ng-vehicle-hero__price-meta" id="ng-active-price-meta"><?php echo wp_kses_post( ngt_price_badge_html( $active_price ) ); ?></span>
                    </div>

                    <p class="ng-vehicle-hero__desc"><?php echo esc_html( $active['body_description'] ?? '' ); ?></p>

                    <div class="ng-vehicle-hero__actions">
                        <button class="ng-btn ng-btn--red ng-btn--lg" id="ng-enquire-btn" data-variant-id="<?php echo esc_attr( $active['id'] ); ?>" data-lead-type="test_drive">
                            Book a Test Drive
                        </button>
                        <button class="ng-btn ng-btn--outline ng-btn--lg" id="ng-quote-btn" data-variant-id="<?php echo esc_attr( $active['id'] ); ?>" data-lead-type="quote_request">
                            Get a Quote
                        </button>
                    </div>
                </div>

                <div class="ng-vehicle-hero__image-wrap">
                    <?php if ( ! empty( $active['image_url'] ) ) : ?>
                        <img src="<?php echo esc_url( $active['image_url'] ); ?>"
                             alt="<?php echo esc_attr( $brand['name'] . ' ' . $model['name'] ); ?>"
                             class="ng-vehicle-hero__image" loading="eager" id="ng-active-image">
                    <?php else : ?>
                        <div class="ng-vehicle-hero__image-placeholder">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" width="80" height="80"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v5"/><path d="M14 17h7m-7 0v4m7-4v4M3 11h4"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                            <span>Image coming soon</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php // ── Variant Selector ──────────────────────────────────────────────── ?>
    <?php if ( count( $variants ) > 1 ) : ?>
    <section class="ng-vehicle-variants">
        <div class="ng-container">
            <h2 class="ng-vehicle-section__title">Choose a Variant</h2>
            <div class="ng-variant-tabs" id="ng-variant-tabs">
                <?php foreach ( $variants as $i => $v ) : ?>
                    <?php $variant_price = ngt_variant_price_display( $v, $price_rows ); ?>
                <button class="ng-variant-tab<?php echo $i === 0 ? ' is-active' : ''; ?>"
                        data-variant-id="<?php echo esc_attr( $v['id'] ); ?>"
                            data-price="<?php echo esc_attr( $v['starting_price_npr'] ?? '' ); ?>"
                            data-price-label="<?php echo esc_attr( $variant_price['label'] ); ?>"
                        data-image="<?php echo esc_attr( $v['image_url'] ?? '' ); ?>">
                    <span class="ng-variant-tab__name"><?php echo esc_html( $v['name'] ); ?></span>
                    <span class="ng-variant-tab__price"><?php echo esc_html( $variant_price['label'] ); ?></span>
                    <span class="ng-variant-tab__price-meta"><?php echo wp_kses_post( ngt_price_badge_html( $variant_price ) ); ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php // ── Highlights strip ──────────────────────────────────────────────── ?>
    <section class="ng-vehicle-highlights">
        <div class="ng-container">
            <div class="ng-highlights-grid" id="ng-highlights">
                <?php
                $highlights = [
                    [ 'icon' => '⚡', 'label' => 'Starting Price', 'val' => $starting_price ],
                    [ 'icon' => '🚗', 'label' => 'Body Type',      'val' => $model['body_type'] ?? '—' ],
                    [ 'icon' => '📅', 'label' => 'Year',            'val' => $active['year_from'] ?? '—' ],
                ];
                foreach ( $highlights as $h ) : ?>
                <div class="ng-highlight-card">
                    <div class="ng-highlight-card__icon"><?php echo $h['icon']; ?></div>
                    <div class="ng-highlight-card__value"><?php echo esc_html( $h['val'] ); ?></div>
                    <div class="ng-highlight-card__label"><?php echo esc_html( $h['label'] ); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php // ── Accessories ───────────────────────────────────────────────────── ?>
    <?php if ( ! empty( $active['variant_specs'] ) ) : ?>
    <section class="ng-vehicle-sourced-specs">
        <div class="ng-container">
            <div class="ng-vehicle-section__head">
                <h2 class="ng-vehicle-section__title">Sourced specs for <?php echo esc_html( $active['name'] ?? 'this variant' ); ?></h2>
                <p>Each value shows the current confidence label and source when available.</p>
            </div>

            <div class="ng-sourced-spec-grid">
                <?php foreach ( $active['variant_specs'] as $spec ) :
                    $field      = $spec['spec_field'] ?? [];
                    $source     = $spec['source'] ?? [];
                    $label      = $field['label'] ?? $field['name'] ?? $field['field_key'] ?? 'Spec';
                    $unit       = $field['unit'] ?? '';
                    $value      = trim( (string) ( $spec['value'] ?? '' ) . ( $unit ? ' ' . $unit : '' ) );
                    $confidence = sanitize_html_class( $spec['confidence'] ?? 'unverified' );
                    $source_url = $source['url'] ?? $source['source_url'] ?? '';
                    $source_label = $source['label'] ?? $source['name'] ?? '';
                    ?>
                    <div class="ng-sourced-spec">
                        <div class="ng-sourced-spec__label"><?php echo esc_html( $label ); ?></div>
                        <div class="ng-sourced-spec__value"><?php echo esc_html( $value ?: 'N/A' ); ?></div>
                        <div class="ng-sourced-spec__meta">
                            <span class="ng-badge ng-badge--<?php echo esc_attr( $confidence ); ?>"><?php echo esc_html( $confidence ); ?></span>
                            <?php if ( $source_url ) : ?>
                                <a href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener nofollow">View source</a>
                            <?php elseif ( $source_label ) : ?>
                                <span><?php echo esc_html( $source_label ); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if ( ! empty( $accessories ) ) : ?>
    <section class="ng-vehicle-accessories">
        <div class="ng-container">
            <h2 class="ng-vehicle-section__title">Popular Accessories</h2>
            <div class="ng-accessories-grid">
                <?php foreach ( $accessories as $acc ) : ?>
                <div class="ng-accessory-card">
                    <?php if ( ! empty( $acc['image_url'] ) ) : ?>
                    <div class="ng-accessory-card__img-wrap">
                        <img src="<?php echo esc_url( $acc['image_url'] ); ?>" alt="<?php echo esc_attr( $acc['name'] ); ?>" loading="lazy">
                    </div>
                    <?php endif; ?>
                    <div class="ng-accessory-card__body">
                        <div class="ng-accessory-card__category"><?php echo esc_html( str_replace( '_', ' ', $acc['category'] ) ); ?></div>
                        <h3 class="ng-accessory-card__name"><?php echo esc_html( $acc['name'] ); ?></h3>
                        <?php if ( ! empty( $acc['price_npr'] ) ) : ?>
                        <div class="ng-accessory-card__price">NPR <?php echo esc_html( number_format( $acc['price_npr'] ) ); ?></div>
                        <?php endif; ?>
                        <?php if ( ! empty( $acc['affiliate_url'] ) ) : ?>
                        <a href="<?php echo esc_url( $acc['affiliate_url'] ); ?>" target="_blank" rel="noopener sponsored" class="ng-btn ng-btn--outline ng-btn--sm ng-acc-buy-btn"
                           data-acc-id="<?php echo esc_attr( $acc['id'] ); ?>"
                           data-acc-name="<?php echo esc_attr( $acc['name'] ); ?>"
                           data-acc-price="<?php echo esc_attr( $acc['price_npr'] ?? 0 ); ?>">
                            Buy Now
                        </a>
                        <?php else : ?>
                        <button class="ng-btn ng-btn--outline ng-btn--sm ng-acc-order-btn"
                                data-acc-id="<?php echo esc_attr( $acc['id'] ); ?>"
                                data-acc-name="<?php echo esc_attr( $acc['name'] ); ?>"
                                data-acc-price="<?php echo esc_attr( $acc['price_npr'] ?? 0 ); ?>">
                            Order
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php // ── JSON-LD structured data ────────────────────────────────────────── ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Product",
        "name": "<?php echo esc_js( $brand['name'] . ' ' . $model['name'] ); ?>",
        "description": "<?php echo esc_js( $active['body_description'] ?? '' ); ?>",
        "brand": { "@type": "Brand", "name": "<?php echo esc_js( $brand['name'] ?? '' ); ?>" },
        "offers": {
            "@type": "Offer",
            "priceCurrency": "NPR",
            "price": "<?php echo esc_js( $active['starting_price_npr'] ?? '' ); ?>",
            "availability": "https://schema.org/InStock",
            "url": "<?php echo esc_js( get_permalink() ); ?>"
        }
    }
    </script>

</main>

<?php // ── Enquiry Modal ──────────────────────────────────────────────────────── ?>
<div id="ng-enquiry-overlay" class="ng-modal__overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ng-enquiry-title">
    <div class="ng-modal__box ng-modal__box--sm">
        <button class="ng-modal__close" id="ng-enquiry-close" aria-label="Close">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
        <h2 class="ng-modal__title" id="ng-enquiry-title">Book a Test Drive</h2>
        <p class="ng-modal__sub" id="ng-enquiry-subtitle">We'll contact you within 24 hours to confirm.</p>

        <form id="ng-enquiry-form" novalidate>
            <input type="hidden" id="ng-enq-variant-id" name="variant_id" value="">
            <input type="hidden" id="ng-enq-lead-type" name="lead_type" value="test_drive">

            <div class="ng-form-group">
                <label class="ng-label" for="ng-enq-name">Full Name *</label>
                <input class="ng-input" type="text" id="ng-enq-name" name="name" required autocomplete="name" placeholder="Ramesh Sharma">
            </div>
            <div class="ng-form-group">
                <label class="ng-label" for="ng-enq-phone">Phone *</label>
                <input class="ng-input" type="tel" id="ng-enq-phone" name="phone" required autocomplete="tel" placeholder="98XXXXXXXX">
            </div>
            <div class="ng-form-group">
                <label class="ng-label" for="ng-enq-email">Email</label>
                <input class="ng-input" type="email" id="ng-enq-email" name="email" autocomplete="email" placeholder="ramesh@example.com">
            </div>
            <div class="ng-form-group">
                <label class="ng-label" for="ng-enq-location">Preferred Location</label>
                <select class="ng-input" id="ng-enq-location" name="message">
                    <option value="">Select city</option>
                    <option>Kathmandu</option>
                    <option>Pokhara</option>
                    <option>Lalitpur</option>
                    <option>Bhaktapur</option>
                    <option>Chitwan</option>
                    <option>Butwal</option>
                    <option>Biratnagar</option>
                    <option>Dharan</option>
                    <option>Birgunj</option>
                    <option>Other</option>
                </select>
            </div>

            <div id="ng-enquiry-error" class="ng-form-error" hidden></div>
            <div id="ng-enquiry-success" class="ng-form-success" hidden>
                <strong>Request sent!</strong> We'll call you within 24 hours.
            </div>

            <button type="submit" class="ng-btn ng-btn--red ng-btn--full" id="ng-enquiry-submit">Submit Request</button>
        </form>
    </div>
</div>

<?php // ── Order Modal (for accessories without affiliate URL) ─────────────────── ?>
<div id="ng-order-overlay" class="ng-modal__overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ng-order-title">
    <div class="ng-modal__box ng-modal__box--sm">
        <button class="ng-modal__close" id="ng-order-close" aria-label="Close">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
        <h2 class="ng-modal__title" id="ng-order-title">Order Accessory</h2>
        <p class="ng-modal__sub" id="ng-order-item-name"></p>

        <form id="ng-order-form" novalidate>
            <input type="hidden" id="ng-ord-acc-id" name="accessory_id" value="">
            <input type="hidden" id="ng-ord-acc-name" name="item_name" value="">
            <input type="hidden" id="ng-ord-acc-price" name="unit_price" value="">

            <div class="ng-form-group">
                <label class="ng-label" for="ng-ord-name">Full Name *</label>
                <input class="ng-input" type="text" id="ng-ord-name" name="name" required autocomplete="name">
            </div>
            <div class="ng-form-group">
                <label class="ng-label" for="ng-ord-phone">Phone *</label>
                <input class="ng-input" type="tel" id="ng-ord-phone" name="phone" required autocomplete="tel">
            </div>
            <div class="ng-form-group">
                <label class="ng-label" for="ng-ord-qty">Quantity</label>
                <input class="ng-input" type="number" id="ng-ord-qty" name="quantity" value="1" min="1" max="10">
            </div>

            <div id="ng-order-error" class="ng-form-error" hidden></div>
            <div id="ng-order-success" class="ng-form-success" hidden>
                <strong>Order placed!</strong> We'll contact you shortly.
            </div>

            <button type="submit" class="ng-btn ng-btn--red ng-btn--full" id="ng-order-submit">Place Order</button>
        </form>
    </div>
</div>

<?php get_footer(); ?>
