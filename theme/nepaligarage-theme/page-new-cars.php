<?php
/**
 * New cars & bikes listing — filterable, schema.org-annotated.
 *
 * @package NepaliGarage
 */

defined( 'ABSPATH' ) || exit;

global $wp_query;
$wp_query->is_404 = false;
status_header( 200 );

$min_year = (int) date( 'Y' ) - 2;

$raw = ngt_supabase_get( 'variants', [
    'select'             => 'id,name,slug,starting_price_npr,year_from,model_id,models!inner(id,name,slug,body_type,is_ev,brands!inner(name,slug,logo_url))',
    'is_available_nepal' => 'eq.true',
    'year_from'          => 'gte.' . $min_year,
    'order'              => 'year_from.desc,name.asc',
    'limit'              => '300',
] );

// One card per model — keep first (highest year_from) variant per model
$seen    = [];
$display = [];
foreach ( $raw as $v ) {
    $mid = $v['model_id'] ?? '';
    if ( ! $mid || isset( $seen[ $mid ] ) ) continue;
    $seen[ $mid ] = true;
    $display[]    = $v;
}

$variant_ids = array_column( $display, 'id' );
$price_rows  = ngt_prices_for_variants( $variant_ids );

// Batch-fetch fuel_type spec values (field ID is stable — set when schema was created)
$fuel_map = [];
if ( $variant_ids ) {
    $fuel_rows = ngt_supabase_get( 'variant_specs', [
        'select'        => 'variant_id,value',
        'spec_field_id' => 'eq.f185469b-d8de-4a89-a822-888e8517187a',
        'variant_id'    => 'in.(' . implode( ',', $variant_ids ) . ')',
        'limit'         => '500',
    ] );
    foreach ( $fuel_rows as $row ) {
        $fuel_map[ $row['variant_id'] ] = strtolower( trim( $row['value'] ?? '' ) );
    }
}

$bike_types = [ 'motorcycle', 'street bike', 'big bike', 'off-road', 'sport bike', 'adventure bike', 'commuter' ];

if ( ! function_exists( 'ngt_fuel_slug' ) ) {
    function ngt_fuel_slug( string $raw, bool $is_ev ): string {
        if ( $is_ev ) return 'ev';
        if ( strpos( $raw, 'electric' ) !== false ) return 'ev';
        if ( strpos( $raw, 'hybrid'   ) !== false ) return 'hybrid';
        if ( strpos( $raw, 'diesel'   ) !== false ) return 'diesel';
        return 'petrol';
    }
}

// Build unique brand list for sidebar checkboxes (sorted alphabetically)
$brand_options = [];
foreach ( $display as $v ) {
    $brand = $v['models']['brands'] ?? [];
    $slug  = $brand['slug'] ?? '';
    $name  = $brand['name'] ?? '';
    if ( $slug && $name && ! isset( $brand_options[ $slug ] ) ) {
        $brand_options[ $slug ] = $name;
    }
}
asort( $brand_options );

// Split into cars vs bikes
$cars = $bikes = [];
foreach ( $display as $v ) {
    $bt = strtolower( $v['models']['body_type'] ?? '' );
    if ( in_array( $bt, $bike_types, true ) ) {
        $bikes[] = $v;
    } else {
        $cars[] = $v;
    }
}

// JSON-LD ItemList for AI readability (output in <head>)
add_action( 'wp_head', function () use ( $display, $fuel_map, $bike_types, $price_rows, $min_year ) {
    $items = [];
    foreach ( $display as $pos => $v ) {
        $model   = $v['models'] ?? [];
        $brand   = $model['brands'] ?? [];
        $is_ev   = ! empty( $model['is_ev'] );
        $is_bike = in_array( strtolower( $model['body_type'] ?? '' ), $bike_types, true );
        $fuel_raw  = $fuel_map[ $v['id'] ] ?? '';
        $fuel_slug = ngt_fuel_slug( $fuel_raw, $is_ev );

        $price_num = 0;
        $vid = $v['id'];
        if ( isset( $price_rows[ $vid ] ) ) {
            foreach ( [ 'price_npr', 'starting_price_npr', 'on_road_price_npr', 'amount_npr' ] as $k ) {
                if ( ! empty( $price_rows[ $vid ][ $k ] ) ) {
                    $price_num = (float) $price_rows[ $vid ][ $k ];
                    break;
                }
            }
        }
        if ( ! $price_num && ! empty( $v['starting_price_npr'] ) ) {
            $price_num = (float) $v['starting_price_npr'];
        }

        $item = [
            '@type'            => $is_bike ? 'Motorcycle' : 'Car',
            'name'             => trim( ( $brand['name'] ?? '' ) . ' ' . ( $model['name'] ?? '' ) ),
            'brand'            => [ '@type' => 'Brand', 'name' => $brand['name'] ?? '' ],
            'vehicleModelDate' => (string) ( $v['year_from'] ?? '' ),
            'fuelType'         => ucfirst( $fuel_slug ),
        ];
        if ( $price_num > 0 ) {
            $item['offers'] = [
                '@type'         => 'Offer',
                'price'         => $price_num,
                'priceCurrency' => 'NPR',
                'availability'  => 'https://schema.org/InStock',
                'areaServed'    => 'NP',
            ];
        }
        $items[] = [ '@type' => 'ListItem', 'position' => $pos + 1, 'item' => $item ];
    }
    $ld = [
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        'name'            => 'New Cars & Bikes in Nepal ' . date( 'Y' ),
        'description'     => count( $display ) . ' vehicles available in Nepal — ' . $min_year . '–' . date( 'Y' ) . ' model years with prices',
        'numberOfItems'   => count( $display ),
        'itemListElement' => $items,
    ];
    echo '<script type="application/ld+json">' . wp_json_encode( $ld ) . "</script>\n";
}, 15 );

get_header();
?>

<main id="primary" class="site-main">

    <section class="ng-page-hero">
        <div class="ng-container">
            <h1 class="ng-page-hero__title">New Cars &amp; Bikes in Nepal</h1>
            <p class="ng-page-hero__sub">
                Browse <?php echo count( $display ); ?> research-ready vehicles across <?php echo $min_year; ?>&ndash;<?php echo date( 'Y' ); ?> model years, then filter by price, type, fuel, and buyer fit.
            </p>
        </div>
    </section>

    <?php
    if ( ! function_exists( 'ngt_card_data' ) ) :
    function ngt_card_data( array $v, array $price_rows, array $fuel_map, array $bike_types ): string {
        $model     = $v['models'] ?? [];
        $brand     = $model['brands'] ?? [];
        $is_ev     = ! empty( $model['is_ev'] );
        $bt        = strtolower( $model['body_type'] ?? '' );
        $is_bike   = in_array( $bt, $bike_types, true );
        $fuel_raw  = $fuel_map[ $v['id'] ] ?? '';
        $fuel_slug = ngt_fuel_slug( $fuel_raw, $is_ev );

        $price_num = 0;
        $vid = $v['id'];
        if ( isset( $price_rows[ $vid ] ) ) {
            foreach ( [ 'price_npr', 'starting_price_npr', 'on_road_price_npr', 'amount_npr' ] as $k ) {
                if ( ! empty( $price_rows[ $vid ][ $k ] ) ) {
                    $price_num = (float) $price_rows[ $vid ][ $k ]; break;
                }
            }
        }
        if ( ! $price_num && ! empty( $v['starting_price_npr'] ) ) {
            $price_num = (float) $v['starting_price_npr'];
        }

        $schema_type = $is_bike ? 'https://schema.org/Motorcycle' : 'https://schema.org/Car';
        $bt_slug     = sanitize_title( $model['body_type'] ?? '' );

        $attrs  = 'data-brand="'      . esc_attr( $brand['slug']    ?? '' ) . '"';
        $attrs .= ' data-body-type="' . esc_attr( $bt_slug           ) . '"';
        $attrs .= ' data-type="'      . ( $is_bike ? 'bike' : 'car' )     . '"';
        $attrs .= ' data-fuel="'      . esc_attr( $fuel_slug         ) . '"';
        $attrs .= ' data-year="'      . esc_attr( (string) ( $v['year_from'] ?? '' ) ) . '"';
        $attrs .= ' data-price="'     . esc_attr( (string) $price_num  ) . '"';
        $attrs .= ' data-ev="'        . ( $is_ev ? 'true' : 'false'  ) . '"';
        $attrs .= ' itemscope itemtype="' . esc_attr( $schema_type ) . '"';

        $meta  = '<meta itemprop="name" content="' . esc_attr( trim( ( $brand['name'] ?? '' ) . ' ' . ( $model['name'] ?? '' ) ) ) . '">';
        $meta .= '<span itemprop="brand" itemscope itemtype="https://schema.org/Brand"><meta itemprop="name" content="' . esc_attr( $brand['name'] ?? '' ) . '"></span>';
        $meta .= '<meta itemprop="vehicleModelDate" content="' . esc_attr( (string) ( $v['year_from'] ?? '' ) ) . '">';
        $meta .= '<meta itemprop="fuelType" content="' . esc_attr( ucfirst( $fuel_slug ) ) . '">';
        if ( $price_num > 0 ) {
            $meta .= '<meta itemprop="price" content="' . esc_attr( (string) $price_num ) . '">';
            $meta .= '<meta itemprop="priceCurrency" content="NPR">';
        }

        return $attrs . '|||' . $meta;
    }
    endif;
    ?>

    <?php if ( ! empty( $display ) ) : ?>

    <!-- SVG chevron reused in each accordion toggle -->
    <?php
    $chevron_svg = '<svg class="ng-sb-chevron" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M2 5l5 5 5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    ?>

    <div class="ng-listing-wrap">

        <!-- Mobile filter trigger (CSS hides on desktop) -->
        <div class="ng-mobile-filter-bar">
            <button class="ng-filter-btn-mobile" id="ngFilterDrawerOpen">
                Filters <span class="ng-filter-badge" id="ngFilterCount" hidden>0</span>
            </button>
            <select class="ng-filter-sort" aria-label="Sort by">
                <option value="year-desc">Year ↓</option>
                <option value="price-asc">Price ↑</option>
                <option value="price-desc">Price ↓</option>
                <option value="az">A–Z</option>
            </select>
        </div>

        <div class="ng-listing-layout">

            <!-- ── Sidebar ────────────────────────────────────── -->
            <aside class="ng-sidebar">
                <div class="ng-sidebar__header">
                    <span class="ng-sidebar__title">Filters</span>
                    <button class="ng-filter-clear-all ng-sidebar__clear">Clear All</button>
                </div>

                <!-- Vehicle Type -->
                <div class="ng-sb-section is-open" data-filter-section="type">
                    <button class="ng-sb-toggle" type="button">
                        Vehicle Type
                        <span class="ng-sb-right">
                            <span class="ng-sb-count" hidden>0</span>
                            <?php echo $chevron_svg; ?>
                        </span>
                    </button>
                    <div class="ng-sb-body">
                        <div class="ng-rb-group">
                            <button class="ng-rb-btn ng-filter-pill--active" data-filter="type" data-value="">All Vehicles</button>
                            <button class="ng-rb-btn" data-filter="type" data-value="car">Cars &amp; SUVs</button>
                            <button class="ng-rb-btn" data-filter="type" data-value="bike">Motorcycles &amp; Bikes</button>
                        </div>
                    </div>
                </div>

                <!-- Brand (multi-select checkboxes) -->
                <div class="ng-sb-section is-open" data-filter-section="brand">
                    <button class="ng-sb-toggle" type="button">
                        Brand
                        <span class="ng-sb-right">
                            <span class="ng-sb-count" hidden>0</span>
                            <?php echo $chevron_svg; ?>
                        </span>
                    </button>
                    <div class="ng-sb-body">
                        <?php foreach ( $brand_options as $slug => $name ) : ?>
                        <label class="ng-check">
                            <input type="checkbox" data-filter="brand" data-value="<?php echo esc_attr( $slug ); ?>">
                            <?php echo esc_html( $name ); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Fuel Type -->
                <div class="ng-sb-section is-open" data-filter-section="fuel">
                    <button class="ng-sb-toggle" type="button">
                        Fuel Type
                        <span class="ng-sb-right">
                            <span class="ng-sb-count" hidden>0</span>
                            <?php echo $chevron_svg; ?>
                        </span>
                    </button>
                    <div class="ng-sb-body">
                        <div class="ng-rb-group">
                            <button class="ng-rb-btn ng-filter-pill--active" data-filter="fuel" data-value="">All</button>
                            <button class="ng-rb-btn" data-filter="fuel" data-value="petrol">Petrol</button>
                            <button class="ng-rb-btn" data-filter="fuel" data-value="diesel">Diesel</button>
                            <button class="ng-rb-btn" data-filter="fuel" data-value="ev">Electric (EV)</button>
                            <button class="ng-rb-btn" data-filter="fuel" data-value="hybrid">Hybrid</button>
                        </div>
                    </div>
                </div>

                <!-- Year Model -->
                <div class="ng-sb-section is-open" data-filter-section="year">
                    <button class="ng-sb-toggle" type="button">
                        Year Model
                        <span class="ng-sb-right">
                            <span class="ng-sb-count" hidden>0</span>
                            <?php echo $chevron_svg; ?>
                        </span>
                    </button>
                    <div class="ng-sb-body">
                        <div class="ng-rb-group">
                            <?php for ( $y = (int) date( 'Y' ); $y >= $min_year; $y-- ) : ?>
                            <button class="ng-rb-btn" data-filter="year" data-value="<?php echo $y; ?>"><?php echo $y; ?></button>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <!-- Price (NPR) -->
                <div class="ng-sb-section is-open" data-filter-section="price">
                    <button class="ng-sb-toggle" type="button">
                        Price (NPR)
                        <span class="ng-sb-right">
                            <span class="ng-sb-count" hidden>0</span>
                            <?php echo $chevron_svg; ?>
                        </span>
                    </button>
                    <div class="ng-sb-body">
                        <div class="ng-rb-group">
                            <button class="ng-rb-btn" data-filter="price" data-value="under-5l">Under 5 Lakh</button>
                            <button class="ng-rb-btn" data-filter="price" data-value="5l-15l">5L &ndash; 15 Lakh</button>
                            <button class="ng-rb-btn" data-filter="price" data-value="15l-50l">15L &ndash; 50 Lakh</button>
                            <button class="ng-rb-btn" data-filter="price" data-value="50l-1cr">50L &ndash; 1 Crore</button>
                            <button class="ng-rb-btn" data-filter="price" data-value="above-1cr">Above 1 Crore</button>
                        </div>
                    </div>
                </div>

            </aside><!-- .ng-sidebar -->

            <!-- ── Card area ──────────────────────────────────── -->
            <div class="ng-listing-content">

                <!-- Results topbar -->
                <div class="ng-listing-topbar">
                    <p class="ng-results-count" id="ngResultsCount"></p>
                    <div class="ng-listing-topbar__right">
                        <div class="ng-filter-active-row" id="ngFilterActiveRow" hidden></div>
                        <select class="ng-filter-sort" aria-label="Sort by">
                            <option value="year-desc">Year ↓</option>
                            <option value="price-asc">Price: Low–High</option>
                            <option value="price-desc">Price: High–Low</option>
                            <option value="az">A–Z</option>
                        </select>
                    </div>
                </div>

                <?php if ( ! empty( $cars ) ) : ?>
                <section class="ng-listing-section" data-section-type="car">
                    <div class="ng-listing-section__hdr">
                        <h2>Cars &amp; SUVs</h2>
                        <p><?php echo count( $cars ); ?> models</p>
                    </div>
                    <div class="ng-listing-grid">
                        <?php foreach ( $cars as $v ) :
                            $model      = $v['models'] ?? [];
                            $brand      = $model['brands'] ?? [];
                            $brand_slug = $brand['slug'] ?? '';
                            $model_slug = $model['slug'] ?? '';
                            $href       = ( $brand_slug && $model_slug )
                                          ? home_url( '/cars/' . $brand_slug . '/' . $model_slug . '/' )
                                          : home_url( '/cars/' );
                            $price      = ngt_variant_price_display( $v, $price_rows );
                            $logo_url   = $brand['logo_url'] ?? '';
                            $is_ev      = ! empty( $model['is_ev'] );
                            $parts      = explode( '|||', ngt_card_data( $v, $price_rows, $fuel_map, $bike_types ) );
                            $data_attrs  = $parts[0];
                            $schema_meta = $parts[1] ?? '';
                        ?>
                        <a class="ng-vehicle-card" href="<?php echo esc_url( $href ); ?>" <?php echo $data_attrs; ?>>
                            <?php echo $schema_meta; ?>
                            <div class="ng-vehicle-card__img" style="background:var(--ng-gray-800);display:flex;align-items:center;justify-content:center;min-height:120px;">
                                <?php if ( $logo_url ) : ?>
                                    <img src="<?php echo esc_url( $logo_url ); ?>"
                                         alt="<?php echo esc_attr( $brand['name'] ?? '' ); ?>"
                                         style="max-height:60px;max-width:120px;object-fit:contain;filter:brightness(0) invert(1);opacity:.7;" loading="lazy">
                                <?php else : ?>
                                    <span style="color:var(--ng-gray-500);font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;"><?php echo esc_html( $brand['name'] ?? '' ); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="ng-vehicle-card__body">
                                <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                                    <span class="ng-vehicle-card__badge"><?php echo esc_html( $model['body_type'] ?? '' ); ?></span>
                                    <?php if ( $is_ev ) : ?><span class="ng-vehicle-card__badge ng-badge--ev">EV</span><?php endif; ?>
                                </div>
                                <div class="ng-vehicle-card__brand"><?php echo esc_html( $brand['name'] ?? '' ); ?></div>
                                <div class="ng-vehicle-card__name"><?php echo esc_html( $model['name'] ?? '' ); ?></div>
                                <strong style="font-size:.95rem;color:var(--ng-text);"><?php echo esc_html( $price['label'] ); ?></strong>
                                <div class="ng-card-price-meta"><?php echo wp_kses_post( ngt_price_badge_html( $price ) ); ?></div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div><!-- .ng-listing-grid -->
                </section>
                <?php endif; ?>

                <?php if ( ! empty( $bikes ) ) : ?>
                <section class="ng-listing-section" data-section-type="bike">
                    <div class="ng-listing-section__hdr">
                        <h2>Motorcycles &amp; Bikes</h2>
                        <p><?php echo count( $bikes ); ?> models</p>
                    </div>
                    <div class="ng-listing-grid">
                        <?php foreach ( $bikes as $v ) :
                            $model      = $v['models'] ?? [];
                            $brand      = $model['brands'] ?? [];
                            $brand_slug = $brand['slug'] ?? '';
                            $model_slug = $model['slug'] ?? '';
                            $href       = ( $brand_slug && $model_slug )
                                          ? home_url( '/cars/' . $brand_slug . '/' . $model_slug . '/' )
                                          : home_url( '/cars/' );
                            $price      = ngt_variant_price_display( $v, $price_rows );
                            $logo_url   = $brand['logo_url'] ?? '';
                            $parts      = explode( '|||', ngt_card_data( $v, $price_rows, $fuel_map, $bike_types ) );
                            $data_attrs  = $parts[0];
                            $schema_meta = $parts[1] ?? '';
                        ?>
                        <a class="ng-vehicle-card" href="<?php echo esc_url( $href ); ?>" <?php echo $data_attrs; ?>>
                            <?php echo $schema_meta; ?>
                            <div class="ng-vehicle-card__img" style="background:var(--ng-gray-800);display:flex;align-items:center;justify-content:center;min-height:120px;">
                                <?php if ( $logo_url ) : ?>
                                    <img src="<?php echo esc_url( $logo_url ); ?>"
                                         alt="<?php echo esc_attr( $brand['name'] ?? '' ); ?>"
                                         style="max-height:60px;max-width:120px;object-fit:contain;filter:brightness(0) invert(1);opacity:.7;" loading="lazy">
                                <?php else : ?>
                                    <span style="color:var(--ng-gray-500);font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;"><?php echo esc_html( $brand['name'] ?? '' ); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="ng-vehicle-card__body">
                                <span class="ng-vehicle-card__badge"><?php echo esc_html( $model['body_type'] ?? '' ); ?></span>
                                <div class="ng-vehicle-card__brand" style="margin-top:4px;"><?php echo esc_html( $brand['name'] ?? '' ); ?></div>
                                <div class="ng-vehicle-card__name"><?php echo esc_html( $model['name'] ?? '' ); ?></div>
                                <strong style="font-size:.95rem;color:var(--ng-text);"><?php echo esc_html( $price['label'] ); ?></strong>
                                <div class="ng-card-price-meta"><?php echo wp_kses_post( ngt_price_badge_html( $price ) ); ?></div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div><!-- .ng-listing-grid -->
                </section>
                <?php endif; ?>

            </div><!-- .ng-listing-content -->
        </div><!-- .ng-listing-layout -->
    </div><!-- .ng-listing-wrap -->

    <?php endif; ?>

    <?php if ( empty( $display ) ) : ?>
    <section class="ng-section">
        <div class="ng-container">
            <p class="ng-empty">No vehicles found for <?php echo $min_year; ?>&ndash;<?php echo date( 'Y' ); ?>.</p>
        </div>
    </section>
    <?php endif; ?>

</main>

<!-- Mobile filter drawer -->
<div class="ng-filter-drawer" id="ngFilterDrawer" role="dialog" aria-modal="true" aria-label="Filters">
    <div class="ng-filter-drawer__header">
        <span>Filters</span>
        <button class="ng-filter-drawer__close" id="ngFilterDrawerClose" aria-label="Close filters">&times;</button>
    </div>
    <div class="ng-filter-drawer__section">
        <div class="ng-filter-drawer__section-title">Vehicle Type</div>
        <div class="ng-filter-drawer__pills">
            <button class="ng-filter-pill ng-filter-pill--active" data-filter="type" data-value="">All</button>
            <button class="ng-filter-pill" data-filter="type" data-value="car">Cars &amp; SUVs</button>
            <button class="ng-filter-pill" data-filter="type" data-value="bike">Bikes</button>
        </div>
    </div>
    <div class="ng-filter-drawer__section">
        <div class="ng-filter-drawer__section-title">Brand</div>
        <div class="ng-filter-drawer__pills">
            <?php foreach ( $brand_options as $slug => $name ) : ?>
            <button class="ng-filter-pill" data-filter="brand" data-value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></button>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="ng-filter-drawer__section">
        <div class="ng-filter-drawer__section-title">Fuel Type</div>
        <div class="ng-filter-drawer__pills">
            <button class="ng-filter-pill ng-filter-pill--active" data-filter="fuel" data-value="">All</button>
            <button class="ng-filter-pill" data-filter="fuel" data-value="petrol">Petrol</button>
            <button class="ng-filter-pill" data-filter="fuel" data-value="diesel">Diesel</button>
            <button class="ng-filter-pill" data-filter="fuel" data-value="ev">EV</button>
            <button class="ng-filter-pill" data-filter="fuel" data-value="hybrid">Hybrid</button>
        </div>
    </div>
    <div class="ng-filter-drawer__section">
        <div class="ng-filter-drawer__section-title">Year Model</div>
        <div class="ng-filter-drawer__pills">
            <?php for ( $y = (int) date( 'Y' ); $y >= $min_year; $y-- ) : ?>
            <button class="ng-filter-pill" data-filter="year" data-value="<?php echo $y; ?>"><?php echo $y; ?></button>
            <?php endfor; ?>
        </div>
    </div>
    <div class="ng-filter-drawer__section">
        <div class="ng-filter-drawer__section-title">Price (NPR)</div>
        <div class="ng-filter-drawer__pills">
            <button class="ng-filter-pill" data-filter="price" data-value="under-5l">Under 5L</button>
            <button class="ng-filter-pill" data-filter="price" data-value="5l-15l">5L&ndash;15L</button>
            <button class="ng-filter-pill" data-filter="price" data-value="15l-50l">15L&ndash;50L</button>
            <button class="ng-filter-pill" data-filter="price" data-value="50l-1cr">50L&ndash;1Cr</button>
            <button class="ng-filter-pill" data-filter="price" data-value="above-1cr">Above 1Cr</button>
        </div>
    </div>
    <div class="ng-filter-drawer__actions">
        <button class="ng-filter-drawer__clear ng-filter-clear-all">Clear All</button>
        <button class="ng-filter-drawer__apply" id="ngFilterDrawerApply">Apply Filters</button>
    </div>
</div>
<div class="ng-filter-overlay" id="ngFilterOverlay"></div>

<?php get_footer();
