<?php
/**
 * Template Name: Vehicle Detail Page
 *
 * URL pattern: /cars/[brand]/[model]/
 *
 * @package NepaliGarage
 */

defined( 'ABSPATH' ) || exit;

// ── Resolve slugs from URL ────────────────────────────────────────────────────
$path_parts = array_values( array_filter( explode( '/', trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' ) ) ) );
$brand_slug = isset( $path_parts[1] ) ? sanitize_title( $path_parts[1] ) : '';
$model_slug = isset( $path_parts[2] ) ? sanitize_title( $path_parts[2] ) : '';

// ── Fetch variants + model + brand ────────────────────────────────────────────
$variants = [];
$model    = null;
$brand    = null;

if ( $model_slug ) {
    $rows = ngt_supabase_get(
        'variants',
        [
            'select'             => 'id,name,slug,year_from,starting_price_npr,body_description,image_url,thumbnail_url,is_featured,model_id,models!inner(id,name,slug,body_type,brochure_url,brands!inner(id,name,slug,logo_url)),variant_specs(*,spec_field:spec_fields(*),source:spec_sources(*))',
            'models.slug'        => 'eq.' . $model_slug,
            'is_available_nepal' => 'eq.true',
            'order'              => 'starting_price_npr.asc',
        ],
        30 * MINUTE_IN_SECONDS
    );
    if ( ! empty( $rows ) ) {
        $variants = $rows;
        $model    = $rows[0]['models'] ?? null;
        $brand    = $model ? ( $model['brands'] ?? null ) : null;
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

// ── Fetch accessories ─────────────────────────────────────────────────────────
$accessories = [];
if ( ! empty( $variants[0]['id'] ) ) {
    $acc_rows = ngt_supabase_get(
        'vehicle_accessories',
        [
            'select'     => 'display_order,is_oem,accessories!inner(id,name,slug,category,description,price_npr,image_url,affiliate_url)',
            'variant_id' => 'eq.' . $variants[0]['id'],
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

// ── Fetch gallery / pros-cons / issues / competition ──────────────────────────
$model_id       = $model['id'] ?? '';
$gallery_images = [];
$pros_cons      = [];
$known_issues   = [];
$competition    = [];
$review_videos  = [];
$common_parts   = [];

if ( $model_id ) {
    $gallery_images = ngt_supabase_get( 'vehicle_images', [
        'select'   => 'id,url,alt,type,display_order',
        'model_id' => 'eq.' . $model_id,
        'order'    => 'display_order.asc',
        'limit'    => '12',
    ], 30 * MINUTE_IN_SECONDS );

    $pros_cons = ngt_supabase_get( 'vehicle_pros_cons', [
        'select'      => 'id,type,content,source_url,source_name',
        'model_id'    => 'eq.' . $model_id,
        'is_verified' => 'eq.true',
        'order'       => 'type.asc,display_order.asc',
    ], 30 * MINUTE_IN_SECONDS );

    $known_issues = ngt_supabase_get( 'vehicle_issues', [
        'select'      => 'id,title,description,severity,source_url,source_name',
        'model_id'    => 'eq.' . $model_id,
        'is_verified' => 'eq.true',
        'order'       => 'severity.desc,created_at.desc',
        'limit'       => '6',
    ], 30 * MINUTE_IN_SECONDS );

    $competition = ngt_supabase_get( 'vehicle_competition', [
        'select'   => 'display_order,competitor:models!competitor_model_id(id,name,slug,body_type,brands!inner(name,slug,logo_url))',
        'model_id' => 'eq.' . $model_id,
        'order'    => 'display_order.asc',
        'limit'    => '4',
    ], 30 * MINUTE_IN_SECONDS );

    $video_rows = ngt_supabase_get( 'vehicle_videos', [
        'select'      => 'id,title,youtube_url,youtube_video_id,thumbnail_url,video_type,source_name,display_order,is_featured,is_verified',
        'model_id'    => 'eq.' . $model_id,
        'is_verified' => 'eq.true',
        'order'       => 'is_featured.desc,display_order.asc,created_at.desc',
        'limit'       => '4',
    ], 30 * MINUTE_IN_SECONDS );

    $parts_rows = ngt_supabase_get( 'vehicle_parts', [
        'select'      => 'id,part_name,part_category,price_npr,image_url,notes,source_url,display_order,is_common,is_verified',
        'model_id'    => 'eq.' . $model_id,
        'is_verified' => 'eq.true',
        'order'       => 'is_common.desc,display_order.asc,created_at.desc',
        'limit'       => '8',
    ], 30 * MINUTE_IN_SECONDS );

    foreach ( $video_rows as $video ) {
        $video_id = sanitize_text_field( $video['youtube_video_id'] ?? '' );
        if ( ! $video_id && ! empty( $video['youtube_url'] ) ) {
            $video_id = ngt_extract_youtube_id( (string) $video['youtube_url'] );
        }

        if ( ! $video_id ) {
            continue;
        }

        $thumbnail_url = $video['thumbnail_url'] ?? '';
        if ( ! $thumbnail_url ) {
            $thumbnail_url = 'https://i.ytimg.com/vi/' . rawurlencode( $video_id ) . '/hqdefault.jpg';
        }

        $review_videos[] = [
            'id'             => $video['id'] ?? '',
            'title'          => $video['title'] ?? 'Review Video',
            'video_type'     => $video['video_type'] ?? 'review',
            'source_name'    => $video['source_name'] ?? '',
            'youtube_url'    => $video['youtube_url'] ?? '',
            'youtube_video_id' => $video_id,
            'thumbnail_url'  => $thumbnail_url,
            'embed_url'      => 'https://www.youtube-nocookie.com/embed/' . rawurlencode( $video_id ),
            'is_featured'    => ! empty( $video['is_featured'] ),
        ];
    }

    usort(
        $review_videos,
        static function ( array $left, array $right ): int {
            return (int) $right['is_featured'] <=> (int) $left['is_featured'];
        }
    );

    foreach ( $parts_rows as $part ) {
        if ( empty( $part['part_name'] ) ) {
            continue;
        }
        $common_parts[] = $part;
    }
}

// ── Active variant + prices ───────────────────────────────────────────────────
$active         = ! empty( $variants ) ? $variants[0] : null;
$price_rows     = ngt_prices_for_variants( wp_list_pluck( $variants, 'id' ) );
$active_price   = $active ? ngt_variant_price_display( $active, $price_rows ) : [];
$active_amount  = $active ? ngt_variant_price_amount( $active, $price_rows ) : null;
$featured_video = $review_videos[0] ?? null;
$supporting_videos = count( $review_videos ) > 1 ? array_slice( $review_videos, 1, 3 ) : [];

$finance_programs = ( $brand && $model ) ? ngt_vehicle_finance_programs( $brand, $model ) : [];
$has_finance      = ! empty( $finance_programs ) && null !== $active_amount;
$finance_programs_json = $has_finance ? wp_json_encode( array_values( $finance_programs ) ) : '';
$default_compare_url = '';
if ( ( $brand['slug'] ?? '' ) === 'byd' && ( $model['slug'] ?? '' ) === 'atto-2' ) {
    $default_compare_url = home_url( '/compare/byd-atto-2-vs-toyota-urban-cruiser-ebella/' );
}

// Spec display uses the first variant that has populated spec data.
// The cheapest variant ($active) may have no specs if only one trim is populated.
$spec_variant = $active;
foreach ( $variants as $v ) {
    if ( ! empty( $v['variant_specs'] ) ) {
        $spec_variant = $v;
        break;
    }
}

if ( ! $active ) {
    get_header();
    echo '<main class="ng-main"><div class="ng-container" style="padding:80px 0;text-align:center"><h1>Vehicle Not Found</h1><p><a href="' . esc_url( home_url() ) . '">Back to Home</a></p></div></main>';
    get_footer();
    exit;
}

// ── Key specs (slot per DB key — first match per label wins) ──────────────────
// Supports both ICE (engine_cc, engine_power_bhp) and EV (battery_capacity_kwh, motor_power_kw)
$key_spec_map = [
    'engine_cc'            => 'Engine',
    'battery_capacity_kwh' => 'Battery',
    'engine_power_bhp'     => 'Power',
    'motor_power_kw'       => 'Power',
    'fuel_type'            => 'Fuel',
    'transmission'         => 'Transmission',
    'seating_capacity'     => 'Seats',
];
$key_specs_display = [];
foreach ( ( $spec_variant['variant_specs'] ?? [] ) as $spec ) {
    $sk = $spec['spec_field']['key'] ?? '';
    if ( ! isset( $key_spec_map[ $sk ] ) ) {
        continue;
    }
    $slot_label = $key_spec_map[ $sk ];
    if ( isset( $key_specs_display[ $slot_label ] ) ) {
        continue; // first match per slot wins
    }
    $unit = $spec['spec_field']['unit'] ?? '';
    $key_specs_display[ $slot_label ] = [
        'slot'  => $slot_label,
        'value' => trim( ( $spec['value'] ?? '' ) . ( $unit ? ' ' . $unit : '' ) ),
    ];
}

// ── Group specs by category ───────────────────────────────────────────────────
$spec_categories = [];
foreach ( ( $spec_variant['variant_specs'] ?? [] ) as $spec ) {
    $cat = $spec['spec_field']['category'] ?? 'other';
    if ( ! isset( $spec_categories[ $cat ] ) ) {
        $spec_categories[ $cat ] = [
            'label' => ucwords( str_replace( '_', ' ', $cat ) ),
            'specs' => [],
        ];
    }
    $spec_categories[ $cat ]['specs'][] = $spec;
}

// ── SEO ───────────────────────────────────────────────────────────────────────
$page_title     = $brand['name'] . ' ' . $model['name'] . ' Price in Nepal — NepaliGarage';
$starting_price = $active_price['label'] ?? 'Price on request';
add_filter( 'pre_get_document_title', fn() => $page_title );

get_header();
?>

<main class="ng-main ng-vehicle-page" id="ng-vehicle-main"
      data-model-slug="<?php echo esc_attr( $model_slug ); ?>"
      data-brand-slug="<?php echo esc_attr( $brand_slug ); ?>">

<?php // ── Hero ───────────────────────────────────────────────────────────── ?>
<section class="ng-vehicle-hero">
    <div class="ng-container">
        <nav class="ng-vehicle-hero__breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> ›
            <a href="<?php echo esc_url( home_url( '/cars/' ) ); ?>">Cars</a> ›
            <a href="<?php echo esc_url( home_url( '/cars/' . ( $brand['slug'] ?? '' ) . '/' ) ); ?>"><?php echo esc_html( $brand['name'] ?? '' ); ?></a> ›
            <span><?php echo esc_html( $model['name'] ?? '' ); ?></span>
        </nav>
        <div class="ng-vehicle-hero__content">
            <div class="ng-vehicle-hero__info">
                <?php if ( ! empty( $brand['logo_url'] ) ) : ?>
                <img src="<?php echo esc_url( $brand['logo_url'] ); ?>"
                     alt="<?php echo esc_attr( $brand['name'] ); ?>"
                     class="ng-vehicle-hero__brand-logo" loading="lazy">
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
                    <button class="ng-btn ng-btn--red ng-btn--lg" id="ng-enquire-btn"
                            data-variant-id="<?php echo esc_attr( $active['id'] ); ?>"
                            data-lead-type="test_drive">Book a Test Drive</button>
                    <button class="ng-btn ng-btn--outline-white ng-btn--lg ng-vehicle-hero__quote-btn" id="ng-quote-btn"
                            data-variant-id="<?php echo esc_attr( $active['id'] ); ?>"
                            data-lead-type="quote_request">Get a Quote</button>
                    <button class="ng-btn ng-btn--outline-white ng-btn--lg ng-vehicle-hero__compare-btn" id="ng-compare-btn"
                            type="button"
                            data-compare-url="<?php echo esc_url( $default_compare_url ); ?>"
                            data-brand-slug="<?php echo esc_attr( $brand['slug'] ?? '' ); ?>"
                            data-model-slug="<?php echo esc_attr( $model['slug'] ?? '' ); ?>"
                            data-model-name="<?php echo esc_attr( trim( ( $brand['name'] ?? '' ) . ' ' . ( $model['name'] ?? '' ) ) ); ?>">
                        Add to Compare
                    </button>
                </div>
                <div class="ng-vehicle-hero__compare-note" id="ng-compare-note" aria-live="polite"></div>
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

<?php // ── Variant Tabs ───────────────────────────────────────────────────── ?>
<?php if ( count( $variants ) > 1 ) : ?>
<section class="ng-vehicle-variants">
    <div class="ng-container">
        <h2 class="ng-vehicle-section__title">Choose a Variant</h2>
        <div class="ng-variant-tabs" id="ng-variant-tabs">
            <?php foreach ( $variants as $i => $v ) :
                $vp = ngt_variant_price_display( $v, $price_rows );
                $variant_price_amount = ngt_variant_price_amount( $v, $price_rows ); ?>
            <button class="ng-variant-tab<?php echo $i === 0 ? ' is-active' : ''; ?>"
                    data-variant-id="<?php echo esc_attr( $v['id'] ); ?>"
                    data-price="<?php echo esc_attr( $v['starting_price_npr'] ?? '' ); ?>"
                    data-price-amount="<?php echo esc_attr( null !== $variant_price_amount ? (string) $variant_price_amount : '' ); ?>"
                    data-price-label="<?php echo esc_attr( $vp['label'] ); ?>"
                    data-image="<?php echo esc_attr( $v['image_url'] ?? '' ); ?>">
                <span class="ng-variant-tab__name"><?php echo esc_html( $v['name'] ); ?></span>
                <span class="ng-variant-tab__price"><?php echo esc_html( $vp['label'] ); ?></span>
                <span class="ng-variant-tab__price-meta"><?php echo wp_kses_post( ngt_price_badge_html( $vp ) ); ?></span>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ( $has_finance ) :
    $primary_finance = $finance_programs[0];
    $min_downpayment_percent = (float) ( $primary_finance['min_downpayment_percent'] ?? 0 );
    $default_years = (int) ( $primary_finance['default_years'] ?? 0 );
    $supported_years = array_values( array_filter( array_map( 'intval', (array) ( $primary_finance['supported_years'] ?? [] ) ) ) );
?>
<section class="ng-vehicle-finance" id="ng-vehicle-finance"
         data-price-amount="<?php echo esc_attr( null !== $active_amount ? (string) $active_amount : '' ); ?>"
         data-finance-programs="<?php echo esc_attr( $finance_programs_json ); ?>">
    <div class="ng-container">
        <div class="ng-vehicle-finance__shell">
            <div class="ng-vehicle-finance__intro">
                <span class="ng-vehicle-finance__eyebrow">BYD Atto 2 finance snapshot</span>
                <h2 class="ng-vehicle-section__title">EMI planning with the lowest official down payment pre-filled</h2>
                <p class="ng-vehicle-finance__copy"><?php echo esc_html( $primary_finance['source_note'] ?? '' ); ?></p>
                <div class="ng-vehicle-finance__source-row">
                    <span class="ng-vehicle-finance__source-label"><?php echo esc_html( $primary_finance['source_name'] ?? 'Official source' ); ?></span>
                    <?php if ( ! empty( $primary_finance['source_url'] ) ) : ?>
                    <a href="<?php echo esc_url( $primary_finance['source_url'] ); ?>" target="_blank" rel="noopener nofollow">Open official EMI source ↗</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="ng-vehicle-finance__layout">
                <div class="ng-vehicle-finance__controls">
                    <div class="ng-vehicle-finance__field ng-vehicle-finance__field--full">
                        <label for="ng-finance-program">Finance baseline</label>
                        <select id="ng-finance-program">
                            <?php foreach ( $finance_programs as $program ) : ?>
                            <option value="<?php echo esc_attr( $program['id'] ); ?>"><?php echo esc_html( $program['label'] ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="ng-vehicle-finance__field ng-vehicle-finance__field--full">
                        <label for="ng-finance-downpayment">Down payment (NPR)</label>
                        <input id="ng-finance-downpayment" type="number" min="0" step="10000"
                               value="<?php echo esc_attr( (string) round( ( (float) $active_amount ) * ( $min_downpayment_percent / 100 ) ) ); ?>">
                        <p class="ng-vehicle-finance__helper">Lowest official down payment for this baseline: <strong><?php echo esc_html( number_format_i18n( $min_downpayment_percent, 0 ) ); ?>%</strong></p>
                    </div>

                    <div class="ng-vehicle-finance__field">
                        <label for="ng-finance-years">Years to pay back</label>
                        <select id="ng-finance-years">
                            <?php foreach ( $supported_years as $years ) : ?>
                            <option value="<?php echo esc_attr( (string) $years ); ?>"<?php selected( $years, $default_years ); ?>><?php echo esc_html( (string) $years ); ?> years</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="ng-vehicle-finance__field">
                        <label for="ng-finance-interest">Interest rate</label>
                        <input id="ng-finance-interest" type="text" value="<?php echo esc_attr( number_format_i18n( (float) ( $primary_finance['interest_rate'] ?? 0 ), 2 ) ); ?>% p.a." readonly>
                        <p class="ng-vehicle-finance__helper">Locked to the current official BYD Nepal baseline.</p>
                    </div>
                </div>

                <aside class="ng-vehicle-finance__results" aria-label="Finance estimate results">
                    <div class="ng-vehicle-finance__result-card">
                        <span>Vehicle price</span>
                        <strong id="ng-finance-price">—</strong>
                    </div>
                    <div class="ng-vehicle-finance__result-card">
                        <span>Down payment</span>
                        <strong id="ng-finance-downpayment-display">—</strong>
                        <small id="ng-finance-downpayment-percent">—</small>
                    </div>
                    <div class="ng-vehicle-finance__result-card ng-vehicle-finance__result-card--emphasis">
                        <span>Estimated monthly EMI</span>
                        <strong id="ng-finance-monthly-emi">—</strong>
                        <small id="ng-finance-term-summary">—</small>
                    </div>
                    <div class="ng-vehicle-finance__result-card">
                        <span>Loan amount</span>
                        <strong id="ng-finance-loan-amount">—</strong>
                    </div>
                    <div class="ng-vehicle-finance__result-card">
                        <span>Total repayment</span>
                        <strong id="ng-finance-total-payable">—</strong>
                    </div>
                    <div class="ng-vehicle-finance__result-card">
                        <span>Total interest</span>
                        <strong id="ng-finance-total-interest">—</strong>
                    </div>
                </aside>
            </div>

            <div class="ng-vehicle-finance__footnote">
                <p><?php echo esc_html( $primary_finance['processing_fee_note'] ?? '' ); ?></p>
                <button class="ng-btn ng-btn--red" type="button" id="ng-finance-quote-btn">Get finance quote for this setup</button>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php // ── Gallery ────────────────────────────────────────────────────────── ?>
<?php if ( ! empty( $gallery_images ) || ! empty( $active['image_url'] ) ) : ?>
<section class="ng-vehicle-gallery">
    <div class="ng-container">
        <div class="ng-gallery-strip" id="ng-gallery-strip">
            <?php if ( ! empty( $gallery_images ) ) : ?>
                <?php foreach ( $gallery_images as $index => $img ) : ?>
                <div class="ng-gallery-thumb<?php echo 0 === $index ? ' is-active' : ''; ?>">
                    <img src="<?php echo esc_url( $img['url'] ); ?>"
                         alt="<?php echo esc_attr( $img['alt'] ?? $brand['name'] . ' ' . $model['name'] ); ?>"
                         loading="lazy">
                </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="ng-gallery-thumb ng-gallery-thumb--single">
                    <img src="<?php echo esc_url( $active['image_url'] ); ?>"
                         alt="<?php echo esc_attr( $brand['name'] . ' ' . $model['name'] ); ?>"
                         loading="lazy">
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php // ── Key Specs Strip ────────────────────────────────────────────────── ?>
<?php if ( ! empty( $key_specs_display ) ) : ?>
<section class="ng-vehicle-keyspecs">
    <div class="ng-container">
        <div class="ng-keyspecs-grid">
            <?php foreach ( $key_specs_display as $ks ) : ?>
            <div class="ng-keyspec-card">
                <div class="ng-keyspec-card__slot"><?php echo esc_html( $ks['slot'] ); ?></div>
                <div class="ng-keyspec-card__value"><?php echo esc_html( $ks['value'] ?: '—' ); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php // ── Tabbed Specs ───────────────────────────────────────────────────── ?>
<?php if ( ! empty( $spec_categories ) ) :
$first_cat = true; ?>
<section class="ng-vehicle-specs-tabbed">
    <div class="ng-container">
        <h2 class="ng-vehicle-section__title">Full Specifications</h2>
        <div class="ng-spec-tabs" role="tablist">
            <?php foreach ( $spec_categories as $cat => $data ) : ?>
            <button class="ng-spec-tab<?php echo $first_cat ? ' is-active' : ''; ?>"
                    data-cat="<?php echo esc_attr( $cat ); ?>" role="tab"
                    aria-selected="<?php echo $first_cat ? 'true' : 'false'; ?>">
                <?php echo esc_html( $data['label'] ); ?>
            </button>
            <?php $first_cat = false; endforeach; ?>
        </div>
        <?php $first_cat = true; foreach ( $spec_categories as $cat => $data ) : ?>
        <div class="ng-spec-category<?php echo $first_cat ? ' is-active' : ''; ?>" data-cat="<?php echo esc_attr( $cat ); ?>">
            <div class="ng-sourced-spec-grid">
                <?php foreach ( $data['specs'] as $spec ) :
                    $field      = $spec['spec_field'] ?? [];
                    $source     = $spec['source'] ?? [];
                    $label      = $field['label'] ?? ucwords( str_replace( '_', ' ', $field['key'] ?? 'Spec' ) );
                    $unit       = $field['unit'] ?? '';
                    $value      = trim( ( $spec['value'] ?? '' ) . ( $unit ? ' ' . $unit : '' ) );
                    $confidence = sanitize_html_class( $spec['confidence'] ?? 'unverified' );
                    $source_url = $source['url'] ?? $source['source_url'] ?? '';
                    $source_lbl = $source['label'] ?? $source['name'] ?? '';
                ?>
                <div class="ng-sourced-spec">
                    <div class="ng-sourced-spec__label"><?php echo esc_html( $label ); ?></div>
                    <div class="ng-sourced-spec__value"><?php echo esc_html( $value ?: 'N/A' ); ?></div>
                    <div class="ng-sourced-spec__meta">
                        <span class="ng-badge ng-badge--<?php echo esc_attr( $confidence ); ?>"><?php echo esc_html( $confidence ); ?></span>
                        <?php if ( $source_url ) : ?>
                        <a href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener nofollow">Source</a>
                        <?php elseif ( $source_lbl ) : ?>
                        <span><?php echo esc_html( $source_lbl ); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php $first_cat = false; endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php // ── Pros & Cons ────────────────────────────────────────────────────── ?>
<?php
$pros = array_values( array_filter( $pros_cons, fn( $p ) => $p['type'] === 'pro' ) );
$cons = array_values( array_filter( $pros_cons, fn( $p ) => $p['type'] === 'con' ) );
?>
<section class="ng-vehicle-proscons">
    <div class="ng-container">
        <h2 class="ng-vehicle-section__title">Pros &amp; Cons</h2>
        <?php if ( empty( $pros_cons ) ) : ?>
        <p class="ng-section-empty">Community research in progress — check back soon.</p>
        <?php else : ?>
        <div class="ng-proscons-grid">
            <div class="ng-proscons-col ng-proscons-col--pros">
                <h3>Pros</h3>
                <?php foreach ( $pros as $p ) : ?>
                <div class="ng-pc-item ng-pc-item--pro">
                    <span class="ng-pc-icon">✓</span>
                    <span><?php echo esc_html( $p['content'] ); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="ng-proscons-col ng-proscons-col--cons">
                <h3>Cons</h3>
                <?php foreach ( $cons as $p ) : ?>
                <div class="ng-pc-item ng-pc-item--con">
                    <span class="ng-pc-icon">✗</span>
                    <span><?php echo esc_html( $p['content'] ); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php // ── Known Issues ───────────────────────────────────────────────────── ?>
<section class="ng-vehicle-issues">
    <div class="ng-container">
        <h2 class="ng-vehicle-section__title">Known Issues</h2>
        <?php if ( empty( $known_issues ) ) : ?>
        <p class="ng-section-empty">No known issues reported yet for this model.</p>
        <?php else : ?>
        <div class="ng-issues-grid">
            <?php foreach ( $known_issues as $issue ) :
                $severity = $issue['severity'] ?? 'minor'; ?>
            <div class="ng-issue-card ng-issue-card--<?php echo esc_attr( $severity ); ?>">
                <div class="ng-issue-card__head">
                    <span class="ng-issue-severity"><?php echo esc_html( ucfirst( $severity ) ); ?></span>
                    <h3 class="ng-issue-card__title"><?php echo esc_html( $issue['title'] ); ?></h3>
                </div>
                <?php if ( ! empty( $issue['description'] ) ) : ?>
                <p class="ng-issue-card__desc"><?php echo esc_html( $issue['description'] ); ?></p>
                <?php endif; ?>
                <?php if ( ! empty( $issue['source_url'] ) ) : ?>
                <a class="ng-issue-card__source" href="<?php echo esc_url( $issue['source_url'] ); ?>" target="_blank" rel="noopener nofollow">
                    <?php echo esc_html( $issue['source_name'] ?? 'Source' ); ?> ↗
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php // ── Review Videos ───────────────────────────────────────────────────── ?>
<?php if ( $featured_video ) : ?>
<section class="ng-vehicle-videos">
    <div class="ng-container">
        <div class="ng-vehicle-section__head">
            <h2 class="ng-vehicle-section__title">Watch Review Videos</h2>
            <p>Use walkarounds, reviews, and owner impressions to understand what the car feels like beyond the brochure and spec sheet.</p>
        </div>

        <div class="ng-video-feature">
            <div class="ng-video-feature__player-wrap">
                <iframe
                    class="ng-video-feature__player"
                    src="<?php echo esc_url( $featured_video['embed_url'] ); ?>"
                    title="<?php echo esc_attr( $featured_video['title'] ); ?>"
                    loading="lazy"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin"
                    allowfullscreen></iframe>
            </div>
            <div class="ng-video-feature__body">
                <span class="ng-video-feature__eyebrow"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $featured_video['video_type'] ?? 'review' ) ) ); ?></span>
                <h3 class="ng-video-feature__title"><?php echo esc_html( $featured_video['title'] ); ?></h3>
                <?php if ( ! empty( $featured_video['source_name'] ) ) : ?>
                <p class="ng-video-feature__meta">Source: <?php echo esc_html( $featured_video['source_name'] ); ?></p>
                <?php endif; ?>
                <p class="ng-video-feature__copy">A strong product page should help users move from paper specs to real-world impressions. This featured video gives that quick context.</p>
            </div>
        </div>

        <?php if ( ! empty( $supporting_videos ) ) : ?>
        <div class="ng-video-grid">
            <?php foreach ( $supporting_videos as $video ) : ?>
            <article class="ng-video-card">
                <a class="ng-video-card__thumb" href="<?php echo esc_url( $video['youtube_url'] ?: $video['embed_url'] ); ?>" target="_blank" rel="noopener nofollow">
                    <img src="<?php echo esc_url( $video['thumbnail_url'] ); ?>" alt="<?php echo esc_attr( $video['title'] ); ?>" loading="lazy">
                    <span class="ng-video-card__play">Play</span>
                </a>
                <div class="ng-video-card__body">
                    <span class="ng-video-card__type"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $video['video_type'] ?? 'review' ) ) ); ?></span>
                    <h3 class="ng-video-card__title"><?php echo esc_html( $video['title'] ); ?></h3>
                    <?php if ( ! empty( $video['source_name'] ) ) : ?>
                    <p class="ng-video-card__meta"><?php echo esc_html( $video['source_name'] ); ?></p>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php // ── Common Parts ────────────────────────────────────────────────────── ?>
<?php if ( ! empty( $common_parts ) ) : ?>
<section class="ng-vehicle-parts">
    <div class="ng-container">
        <div class="ng-vehicle-section__head">
            <h2 class="ng-vehicle-section__title">Common Parts &amp; Maintenance</h2>
            <p>These are the basic, presentable ownership resources that make a product page more useful: common replacement items, routine maintenance references, and fast source links.</p>
        </div>

        <div class="ng-parts-grid">
            <?php foreach ( $common_parts as $part ) : ?>
            <article class="ng-part-card">
                <div class="ng-part-card__media">
                    <?php if ( ! empty( $part['image_url'] ) ) : ?>
                    <img src="<?php echo esc_url( $part['image_url'] ); ?>" alt="<?php echo esc_attr( $part['part_name'] ); ?>" loading="lazy">
                    <?php else : ?>
                    <div class="ng-part-card__placeholder">Part image</div>
                    <?php endif; ?>
                </div>
                <div class="ng-part-card__body">
                    <?php if ( ! empty( $part['part_category'] ) ) : ?>
                    <span class="ng-part-card__category"><?php echo esc_html( str_replace( '_', ' ', $part['part_category'] ) ); ?></span>
                    <?php endif; ?>
                    <h3 class="ng-part-card__name"><?php echo esc_html( $part['part_name'] ); ?></h3>
                    <?php if ( ! empty( $part['price_npr'] ) ) : ?>
                    <div class="ng-part-card__price">NPR <?php echo esc_html( number_format( (float) $part['price_npr'] ) ); ?></div>
                    <?php endif; ?>
                    <?php if ( ! empty( $part['notes'] ) ) : ?>
                    <p class="ng-part-card__notes"><?php echo esc_html( $part['notes'] ); ?></p>
                    <?php endif; ?>
                    <?php if ( ! empty( $part['source_url'] ) ) : ?>
                    <a class="ng-part-card__link" href="<?php echo esc_url( $part['source_url'] ); ?>" target="_blank" rel="noopener nofollow">View source ↗</a>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php // ── Competition ────────────────────────────────────────────────────── ?>
<?php if ( ! empty( $competition ) ) : ?>
<section class="ng-vehicle-competition">
    <div class="ng-container">
        <h2 class="ng-vehicle-section__title">You May Also Consider</h2>
        <div class="ng-competition-grid">
            <?php foreach ( $competition as $comp ) :
                $cm = $comp['competitor'] ?? null;
                if ( ! $cm ) continue;
                $cb     = $cm['brands'] ?? [];
                $cm_url = home_url( '/cars/' . ( $cb['slug'] ?? '' ) . '/' . ( $cm['slug'] ?? '' ) . '/' );
            ?>
            <a class="ng-competition-card" href="<?php echo esc_url( $cm_url ); ?>">
                <div class="ng-competition-card__img">
                    <?php if ( ! empty( $cb['logo_url'] ) ) : ?>
                    <img src="<?php echo esc_url( $cb['logo_url'] ); ?>"
                         alt="<?php echo esc_attr( $cb['name'] ?? '' ); ?>"
                         loading="lazy">
                    <?php endif; ?>
                </div>
                <div class="ng-competition-card__body">
                    <div class="ng-competition-card__brand"><?php echo esc_html( $cb['name'] ?? '' ); ?></div>
                    <div class="ng-competition-card__name"><?php echo esc_html( $cm['name'] ?? '' ); ?></div>
                    <div class="ng-competition-card__type"><?php echo esc_html( $cm['body_type'] ?? '' ); ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php // ── Brochure CTA ───────────────────────────────────────────────────── ?>
<?php if ( ! empty( $model['brochure_url'] ) ) : ?>
<section class="ng-vehicle-brochure">
    <div class="ng-container">
        <div class="ng-brochure-cta">
            <div class="ng-brochure-cta__body">
                <span class="ng-brochure-cta__eyebrow">Official brochure</span>
                <strong><?php echo esc_html( $brand['name'] . ' ' . $model['name'] ); ?> brochure</strong>
                <p>Use the official PDF to verify trim highlights, dimensions, standard features, and manufacturer-claimed figures before you speak to a dealer.</p>
            </div>
            <a class="ng-btn ng-btn--outline" href="<?php echo esc_url( $model['brochure_url'] ); ?>" target="_blank" rel="noopener">
                Download PDF ↓
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php // ── Accessories ────────────────────────────────────────────────────── ?>
<?php if ( ! empty( $accessories ) ) : ?>
<section class="ng-vehicle-accessories">
    <div class="ng-container">
        <h2 class="ng-vehicle-section__title">Popular Accessories</h2>
        <div class="ng-accessories-grid">
            <?php foreach ( $accessories as $acc ) : ?>
            <div class="ng-accessory-card">
                <?php if ( ! empty( $acc['image_url'] ) ) : ?>
                <div class="ng-accessory-card__img-wrap">
                    <img src="<?php echo esc_url( $acc['image_url'] ); ?>"
                         alt="<?php echo esc_attr( $acc['name'] ); ?>" loading="lazy">
                </div>
                <?php endif; ?>
                <div class="ng-accessory-card__body">
                    <div class="ng-accessory-card__category"><?php echo esc_html( str_replace( '_', ' ', $acc['category'] ) ); ?></div>
                    <h3 class="ng-accessory-card__name"><?php echo esc_html( $acc['name'] ); ?></h3>
                    <?php if ( ! empty( $acc['price_npr'] ) ) : ?>
                    <div class="ng-accessory-card__price">NPR <?php echo esc_html( number_format( $acc['price_npr'] ) ); ?></div>
                    <?php endif; ?>
                    <?php if ( ! empty( $acc['affiliate_url'] ) ) : ?>
                    <a href="<?php echo esc_url( $acc['affiliate_url'] ); ?>" target="_blank" rel="noopener sponsored"
                       class="ng-btn ng-btn--outline ng-btn--sm ng-acc-buy-btn"
                       data-acc-id="<?php echo esc_attr( $acc['id'] ); ?>"
                       data-acc-name="<?php echo esc_attr( $acc['name'] ); ?>"
                       data-acc-price="<?php echo esc_attr( $acc['price_npr'] ?? 0 ); ?>">Buy Now</a>
                    <?php else : ?>
                    <button class="ng-btn ng-btn--outline ng-btn--sm ng-acc-order-btn"
                            data-acc-id="<?php echo esc_attr( $acc['id'] ); ?>"
                            data-acc-name="<?php echo esc_attr( $acc['name'] ); ?>"
                            data-acc-price="<?php echo esc_attr( $acc['price_npr'] ?? 0 ); ?>">Order</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php // ── JSON-LD ────────────────────────────────────────────────────────── ?>
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

<?php // ── Enquiry Modal ──────────────────────────────────────────────────── ?>
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
                    <option>Kathmandu</option><option>Pokhara</option><option>Lalitpur</option>
                    <option>Bhaktapur</option><option>Chitwan</option><option>Butwal</option>
                    <option>Biratnagar</option><option>Dharan</option><option>Birgunj</option>
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

<?php // ── Order Modal ────────────────────────────────────────────────────── ?>
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

<script>
(function () {
    var tabs = document.querySelectorAll('.ng-spec-tab');
    var cats = document.querySelectorAll('.ng-spec-category');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var cat = tab.dataset.cat;
            tabs.forEach(function (t) {
                t.classList.toggle('is-active', t.dataset.cat === cat);
                t.setAttribute('aria-selected', t.dataset.cat === cat ? 'true' : 'false');
            });
            cats.forEach(function (c) { c.classList.toggle('is-active', c.dataset.cat === cat); });
        });
    });
}());
</script>

<?php get_footer(); ?>
