<?php
defined( 'ABSPATH' ) || exit;

define( 'NGT_VERSION', '1.4.0' );
define( 'NGT_DIR',     get_template_directory() );
define( 'NGT_URI',     get_template_directory_uri() );

// ── Theme support ─────────────────────────────────────────────────────────────

add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'script', 'style' ] );
    add_theme_support( 'custom-logo' );
    add_theme_support( 'responsive-embeds' );

    register_nav_menus( [
        'primary' => __( 'Primary Navigation', 'nepaligarage' ),
        'footer'  => __( 'Footer Navigation', 'nepaligarage' ),
    ] );
} );

// ── Assets ────────────────────────────────────────────────────────────────────

add_action( 'wp_enqueue_scripts', function () {
    $supabase_url = get_option( 'ng_supabase_url', 'https://eukghqvlvlveshchnugk.supabase.co' );
    $supabase_key = (string) get_option( 'ng_supabase_key', '' );

    // Google Fonts
    wp_enqueue_style(
        'ng-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500&family=Noto+Sans+Devanagari:wght@400;500;700&family=Syncopate:wght@400;700&display=swap',
        [],
        null
    );

    // Theme stylesheet
    wp_enqueue_style( 'ng-theme', NGT_URI . '/assets/css/theme.css', [ 'ng-fonts' ], NGT_VERSION );

    // Dashboard styles only on /dashboard
    if ( is_page( 'dashboard' ) ) {
        wp_enqueue_style( 'ng-dashboard', NGT_URI . '/assets/css/dashboard.css', [ 'ng-theme' ], NGT_VERSION );
    }

    // Supabase JS SDK (v2)
    wp_enqueue_script(
        'supabase-js',
        'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2/dist/umd/supabase.js',
        [],
        '2',
        true
    );

    // Nav + mobile menu
    wp_enqueue_script( 'ng-nav', NGT_URI . '/assets/js/nav.js', [], NGT_VERSION, true );

    // Compare tray (all pages)
    wp_enqueue_script(
        'ng-compare',
        get_template_directory_uri() . '/assets/js/compare.js',
        array(),
        '1.0.0',
        true
    );

    // Compare builder (only on /compare/) — pickers + catalog for the two-slot builder
    if ( is_page( 'compare' ) ) {
        wp_enqueue_script(
            'ng-compare-builder',
            get_template_directory_uri() . '/assets/js/compare-builder.js',
            array( 'ng-compare' ),
            NGT_VERSION,
            true
        );
        wp_localize_script( 'ng-compare-builder', 'ngCompareBuilder', [
            'catalog'     => ngt_compare_catalog(),
            'compareBase' => home_url( '/compare/' ),
        ] );
    }

    // Auth modal (all pages)
    wp_enqueue_script( 'ng-auth', NGT_URI . '/assets/js/auth.js', [ 'supabase-js' ], NGT_VERSION, true );

    // Dashboard app (only on /dashboard)
    if ( is_page( 'dashboard' ) ) {
        wp_enqueue_script( 'ng-dashboard-app', NGT_URI . '/assets/js/dashboard.js', [ 'ng-auth' ], NGT_VERSION, true );
    }

    // Vehicle detail template
    if ( is_page_template( 'page-vehicle.php' ) ) {
        wp_enqueue_style( 'ng-vehicle', NGT_URI . '/assets/css/vehicle.css', [ 'ng-theme' ], NGT_VERSION );
        wp_enqueue_script( 'ng-enquiry', NGT_URI . '/assets/js/enquiry.js', [ 'ng-auth' ], NGT_VERSION, true );
    }

    // New-cars listing filter
    if ( is_page( 'new-cars' ) ) {
        wp_enqueue_script( 'ng-filter', NGT_URI . '/assets/js/filter.js', [], NGT_VERSION, true );
    }

    // Homepage hero tabbed search (Find / Estimate / Compare).
    // Note: this install has show_on_front=page with page_on_front=0, so the root
    // renders front-page.php as the blog home — is_front_page() is false there but
    // is_home() is true. Gate on both so the widget loads regardless of that config.
    if ( is_front_page() || is_home() ) {
        wp_enqueue_script( 'ng-home', NGT_URI . '/assets/js/home.js', [], NGT_VERSION, true );
        wp_localize_script( 'ng-home', 'ngHome', [
            'catalog'     => ngt_compare_catalog(),
            'carsBase'    => home_url( '/cars/' ),
            'newCarsBase' => home_url( '/new-cars/' ),
            'compareBase' => home_url( '/compare/' ),
            'estimatorUrl'=> home_url( '/nepal-car-price-estimator/' ),
        ] );
    }

    // Pass config to JS — anon key is safe in browser; all data is RLS-protected
    wp_localize_script( 'ng-auth', 'ngConfig', [
        'supabaseUrl'  => $supabase_url,
        'supabaseKey'  => $supabase_key,
        'siteUrl'      => home_url(),
        'dashboardUrl' => home_url( '/dashboard/' ),
        'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
        'nonce'        => wp_create_nonce( 'ng_nonce' ),
    ] );
} );

// ── Excerpt length ────────────────────────────────────────────────────────────

add_filter( 'excerpt_length', fn() => 20 );
add_filter( 'excerpt_more',   fn() => '&hellip;' );

// ── Helper: fetch Supabase REST (server-side, cached) ─────────────────────────

function ngt_supabase_get( string $endpoint, array $params = [], int $ttl = HOUR_IN_SECONDS ): array {
    $url       = trailingslashit( get_option( 'ng_supabase_url', 'https://eukghqvlvlveshchnugk.supabase.co' ) ) . 'rest/v1/' . $endpoint;
    $anon_key  = (string) get_option( 'ng_supabase_key', '' );
    $cache_key = 'ngt2_' . md5( $url . serialize( $params ) );

    $cached = get_transient( $cache_key );
    if ( false !== $cached ) {
        return $cached;
    }

    $response = wp_remote_get( add_query_arg( $params, $url ), [
        'timeout' => 10,
        'headers' => [
            'apikey'        => $anon_key,
            'Authorization' => 'Bearer ' . $anon_key,
            'Accept'        => 'application/json',
        ],
    ] );

    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) >= 300 ) {
        return [];
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    $data = is_array( $data ) ? $data : [];

    // Only cache non-empty results — empty may mean bad key or cold data
    if ( ! empty( $data ) ) {
        set_transient( $cache_key, $data, $ttl );
    }

    return $data;
}

/**
 * Fetch latest price rows for a set of variant IDs, indexed by variant_id.
 *
 * @param array<int, string> $variant_ids Variant UUIDs.
 * @return array<string, array<string, mixed>>
 */
function ngt_prices_for_variants( array $variant_ids ): array {
    $variant_ids = array_values( array_filter( array_unique( array_map( 'sanitize_text_field', $variant_ids ) ) ) );
    if ( empty( $variant_ids ) ) {
        return [];
    }

    $rows = ngt_supabase_get( 'prices', [
        'select'     => '*,source:spec_sources(*)',
        'variant_id' => 'in.(' . implode( ',', $variant_ids ) . ')',
        'order'      => 'effective_date.desc',
        'limit'      => '500',
    ] );

    $prices = [];
    foreach ( $rows as $row ) {
        $variant_id = $row['variant_id'] ?? '';
        if ( $variant_id && ! isset( $prices[ $variant_id ] ) ) {
            $prices[ $variant_id ] = $row;
        }
    }

    return $prices;
}

/**
 * Normalize a variant price for display. Falls back to denormalized variant price as unverified.
 */
function ngt_variant_price_amount( array $variant, array $price_rows = [] ): ?float {
    $variant_id = $variant['id'] ?? '';
    $price      = $variant_id && isset( $price_rows[ $variant_id ] ) ? $price_rows[ $variant_id ] : [];

    foreach ( [ 'price_npr', 'starting_price_npr', 'on_road_price_npr', 'amount_npr' ] as $key ) {
        if ( isset( $price[ $key ] ) && '' !== $price[ $key ] ) {
            return (float) $price[ $key ];
        }
    }

    if ( ! empty( $variant['starting_price_npr'] ) ) {
        return (float) $variant['starting_price_npr'];
    }

    return null;
}

function ngt_variant_price_display( array $variant, array $price_rows = [] ): array {
    $variant_id = $variant['id'] ?? '';
    $price      = $variant_id && isset( $price_rows[ $variant_id ] ) ? $price_rows[ $variant_id ] : [];
    $amount     = ngt_variant_price_amount( $variant, $price_rows );

    if ( null !== $amount && ! empty( $price ) ) {
        $source = is_array( $price['source'] ?? null ) ? $price['source'] : [];

        return [
            'label'       => 'NPR ' . number_format( $amount ),
            'confidence'  => sanitize_html_class( $price['confidence'] ?? 'unverified' ),
            'source_url'  => $source['url'] ?? $source['source_url'] ?? '',
            'source_name' => $source['label'] ?? $source['name'] ?? '',
        ];
    }

    if ( null !== $amount ) {
        return [
            'label'       => 'NPR ' . number_format( $amount ),
            'confidence'  => 'unverified',
            'source_url'  => '',
            'source_name' => 'Unverified price cache',
        ];
    }

    return [
        'label'       => 'Price on request',
        'confidence'  => 'unverified',
        'source_url'  => '',
        'source_name' => '',
    ];
}

/**
 * Build the compare-builder catalog: one entry per available model, resolved to its
 * cheapest available variant. Used by the /compare/ builder pickers (client-side filtering).
 *
 * @return array<int, array<string, mixed>>
 */
function ngt_compare_catalog(): array {
    $rows = ngt_supabase_get(
        'variants',
        [
            'select'             => 'slug,name,thumbnail_url,image_url,starting_price_npr,models!inner(slug,name,body_type,is_ev,brands!inner(name,slug))',
            'is_available_nepal' => 'eq.true',
            'order'              => 'starting_price_npr.asc.nullslast',
            'limit'              => '500',
        ],
        15 * MINUTE_IN_SECONDS
    );

    $by_model = [];
    foreach ( $rows as $row ) {
        $model = $row['models'] ?? [];
        $brand = $model['brands'] ?? [];
        $model_slug = sanitize_title( (string) ( $model['slug'] ?? '' ) );
        if ( '' === $model_slug || isset( $by_model[ $model_slug ] ) ) {
            continue; // first occurrence is cheapest thanks to the ordering above.
        }

        $price = isset( $row['starting_price_npr'] ) && '' !== $row['starting_price_npr']
            ? (int) $row['starting_price_npr']
            : null;

        $by_model[ $model_slug ] = [
            'variant_slug' => sanitize_title( (string) ( $row['slug'] ?? '' ) ),
            'model_slug'   => $model_slug,
            'model_name'   => (string) ( $model['name'] ?? '' ),
            'brand_name'   => (string) ( $brand['name'] ?? '' ),
            'brand_slug'   => sanitize_title( (string) ( $brand['slug'] ?? '' ) ),
            'body_type'    => (string) ( $model['body_type'] ?? '' ),
            'is_ev'        => ! empty( $model['is_ev'] ),
            'price'        => $price,
            'thumb'        => (string) ( $row['thumbnail_url'] ?? $row['image_url'] ?? '' ),
        ];
    }

    $catalog = array_values( $by_model );
    usort(
        $catalog,
        static function ( $a, $b ) {
            return strcasecmp( $a['brand_name'] . ' ' . $a['model_name'], $b['brand_name'] . ' ' . $b['model_name'] );
        }
    );

    return $catalog;
}

/**
 * Official finance programs for a vehicle, sourced from Supabase `brand_finance_programs`.
 * Model-specific programs rank ahead of brand-wide ones. Returns the existing JS contract shape.
 *
 * @return array<int, array<string, mixed>>
 */
function ngt_vehicle_finance_programs( array $brand, array $model ): array {
    $brand_id = sanitize_text_field( (string) ( $brand['id'] ?? '' ) );
    $model_id = sanitize_text_field( (string) ( $model['id'] ?? '' ) );
    if ( '' === $brand_id ) {
        return [];
    }

    // model-specific OR brand-wide (model_id null) active programs.
    $or = $model_id
        ? '(model_id.eq.' . $model_id . ',and(model_id.is.null,brand_id.eq.' . $brand_id . '))'
        : '(and(model_id.is.null,brand_id.eq.' . $brand_id . '))';

    $rows = ngt_supabase_get(
        'brand_finance_programs',
        [
            'select'    => 'id,partner_name,program_name,interest_type,interest_rate,min_downpayment_percent,max_tenure_months,processing_fee_npr,insurance_notes,source_url,source_label,verified_at,is_manual_estimate,model_id,terms:brand_finance_program_terms(tenure_months,interest_rate,min_downpayment_percent,display_order)',
            'is_active' => 'eq.true',
            'or'        => $or,
            'order'     => 'model_id.desc.nullslast,min_downpayment_percent.asc',
        ],
        15 * MINUTE_IN_SECONDS
    );

    $programs = [];
    foreach ( $rows as $row ) {
        $programs[] = ngt_map_finance_program( $row );
    }

    return $programs;
}

/**
 * Map a Supabase brand_finance_programs row to the front-end program contract
 * consumed by page-vehicle.php + enquiry.js.
 *
 * @return array<string, mixed>
 */
function ngt_map_finance_program( array $row ): array {
    $rate          = (float) ( $row['interest_rate'] ?? 0 );
    $interest_type = ( 'flat' === ( $row['interest_type'] ?? 'reducing' ) ) ? 'flat' : 'reducing';
    $min_down      = (float) ( $row['min_downpayment_percent'] ?? 20 );
    $max_tenure    = max( 12, (int) ( $row['max_tenure_months'] ?? 84 ) );

    // Tenure options: from child terms when present, else yearly steps up to max.
    $terms = is_array( $row['terms'] ?? null ) ? $row['terms'] : [];
    if ( ! empty( $terms ) ) {
        $years = [];
        foreach ( $terms as $term ) {
            $m = (int) ( $term['tenure_months'] ?? 0 );
            if ( $m > 0 ) {
                $years[] = max( 1, (int) round( $m / 12 ) );
            }
        }
        $years = array_values( array_unique( $years ) );
        sort( $years );
    } else {
        $max_years = max( 1, (int) floor( $max_tenure / 12 ) );
        $years     = range( 1, $max_years );
    }
    $default_years = ! empty( $years ) ? (int) end( $years ) : 7;

    $partner = trim( (string) ( $row['partner_name'] ?? '' ) );
    $program = trim( (string) ( $row['program_name'] ?? 'Finance program' ) );
    $label   = $partner ? $program . ' — ' . $partner : $program;

    $verified = '';
    if ( ! empty( $row['verified_at'] ) ) {
        $ts = strtotime( (string) $row['verified_at'] );
        if ( $ts ) {
            $verified = ' · Verified ' . gmdate( 'j M Y', $ts );
        }
    }

    $note_bits = [];
    $note_bits[] = sprintf(
        '%s scheme at %s%% p.a. with a minimum %s%% down payment.',
        'flat' === $interest_type ? 'Flat-rate' : 'Reducing-balance',
        rtrim( rtrim( number_format( $rate, 2 ), '0' ), '.' ),
        rtrim( rtrim( number_format( $min_down, 2 ), '0' ), '.' )
    );
    if ( ! empty( $row['insurance_notes'] ) ) {
        $note_bits[] = (string) $row['insurance_notes'];
    }

    $fee_note = 'Dealer and bank processing fees are not included in this EMI preview.';
    if ( ! empty( $row['processing_fee_npr'] ) ) {
        $fee_note = 'One-time processing fee of NPR ' . number_format( (float) $row['processing_fee_npr'] ) . ' applies on top of this EMI preview.';
    }

    return [
        'id'                      => (string) ( $row['id'] ?? wp_generate_uuid4() ),
        'label'                   => $label,
        'source_name'             => trim( (string) ( $row['source_label'] ?? '' ) . $verified ) ?: 'Official source',
        'source_url'              => (string) ( $row['source_url'] ?? '' ),
        'source_note'             => implode( ' ', $note_bits ),
        'interest_type'           => $interest_type,
        'interest_rate'           => $rate,
        'min_downpayment_percent' => $min_down,
        'max_downpayment_percent' => 90,
        'supported_years'         => array_values( $years ),
        'default_years'           => $default_years,
        'processing_fee_note'     => $fee_note,
        'is_generic'              => false,
    ];
}

/**
 * Generic indicative EMI fallback used when no official scheme exists for a vehicle.
 * Spec §5: never hide EMI — show an editable indicative calculator with a clear disclaimer.
 *
 * @return array<string, mixed>
 */
function ngt_generic_finance_program(): array {
    return [
        'id'                      => 'generic-indicative',
        'label'                   => 'Indicative EMI estimate',
        'source_name'             => 'NepaliGarage indicative calculator',
        'source_url'              => '',
        'source_note'             => 'No official finance scheme is published for this vehicle yet. This is an indicative estimate using a typical Nepal auto-loan rate — adjust the rate to match a quote from your bank.',
        'interest_type'           => 'reducing',
        'interest_rate'           => 12.0,
        'min_downpayment_percent' => 20,
        'max_downpayment_percent' => 90,
        'supported_years'         => [ 1, 2, 3, 4, 5, 6, 7 ],
        'default_years'           => 5,
        'processing_fee_note'     => 'Indicative only — not a quoted offer. Confirm the exact rate, tenure, and fees with your bank or dealer.',
        'is_generic'              => true,
    ];
}

function ngt_price_badge_html( array $price ): string {
    $confidence = sanitize_html_class( $price['confidence'] ?? 'unverified' );
    $source_url = $price['source_url'] ?? '';
    $source_name = $price['source_name'] ?? '';

    $html = '<span class="ng-badge ng-badge--' . esc_attr( $confidence ) . '">' . esc_html( $confidence ) . '</span>';
    if ( $source_url ) {
        $html .= ' <a class="ng-price-source" href="' . esc_url( $source_url ) . '" target="_blank" rel="noopener nofollow">Source</a>';
    } elseif ( $source_name ) {
        $html .= ' <span class="ng-price-source">' . esc_html( $source_name ) . '</span>';
    }

    return $html;
}

/**
 * Extract a YouTube video ID from common YouTube URL formats.
 */
function ngt_extract_youtube_id( string $url ): string {
    $url = trim( $url );
    if ( '' === $url ) {
        return '';
    }

    $pattern = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([A-Za-z0-9_-]{11})%';
    if ( preg_match( $pattern, $url, $matches ) ) {
        return $matches[1];
    }

    return '';
}

function ngt_request_path_parts(): array {
    $uri = parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
    return array_values( array_filter( explode( '/', trim( (string) $uri, '/' ) ) ) );
}

function ngt_is_cars_route_request(): bool {
    $parts = ngt_request_path_parts();
    return isset( $parts[0] ) && 'cars' === $parts[0];
}

function ngt_is_vehicle_route_request(): bool {
    $parts = ngt_request_path_parts();
    return isset( $parts[0], $parts[1], $parts[2] ) && 'cars' === $parts[0];
}

function ngt_mark_current_request_success(): void {
    global $wp_query;

    if ( isset( $wp_query ) && $wp_query instanceof WP_Query ) {
        $wp_query->is_404      = false;
        $wp_query->is_page     = true;
        $wp_query->is_singular = true;
        $wp_query->is_home     = false;
    }

    status_header( 200 );
}

add_filter( 'redirect_canonical', function ( $redirect_url, $requested_url ) {
    if ( ngt_is_cars_route_request() ) {
        return false;
    }

    return $redirect_url;
}, 10, 2 );

add_filter( 'do_redirect_guess_404_permalink', function ( $do_redirect ) {
    if ( ngt_is_cars_route_request() ) {
        return false;
    }

    return $do_redirect;
} );

add_action( 'template_redirect', function () {
    if ( ngt_is_cars_route_request() ) {
        remove_action( 'template_redirect', 'redirect_canonical' );
        remove_action( 'template_redirect', 'wp_old_slug_redirect' );
        ngt_mark_current_request_success();
    }
}, 0 );

add_filter( 'pre_handle_404', function ( $preempt, $wp_query ) {
    if ( ngt_is_cars_route_request() ) {
        if ( $wp_query instanceof WP_Query ) {
            $wp_query->is_404 = false;
        }
        return true;
    }

    return $preempt;
}, 10, 2 );

// ── Team dashboard template routing ──────────────────────────────────────────

add_filter( 'template_include', function ( string $template ): string {
    $uri   = parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
    $parts = array_values( array_filter( explode( '/', trim( $uri, '/' ) ) ) );
    if ( isset( $parts[0] ) && $parts[0] === 'team' && ! isset( $parts[1] ) ) {
        $tpl = get_template_directory() . '/page-team.php';
        if ( file_exists( $tpl ) ) return $tpl;
    }
    return $template;
}, 5 );

// Enqueue team dashboard assets for /team/ only
add_action( 'wp_enqueue_scripts', function () {
    $uri   = parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
    $parts = array_values( array_filter( explode( '/', trim( $uri, '/' ) ) ) );
    if ( isset( $parts[0] ) && $parts[0] === 'team' && ! isset( $parts[1] ) ) {
        wp_enqueue_style( 'ng-team', get_template_directory_uri() . '/assets/css/team.css', [ 'ng-theme' ], NGT_VERSION );
        wp_enqueue_script( 'ng-team', get_template_directory_uri() . '/assets/js/team.js', [ 'ng-auth' ], NGT_VERSION, true );
    }
}, 20 );

// ── Vehicle template auto-routing ────────────────────────────────────────────
// Public utility pages that should render even before matching WP pages are created.
add_filter( 'template_include', function ( string $template ): string {
    $uri   = parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
    $parts = array_values( array_filter( explode( '/', trim( $uri, '/' ) ) ) );
    $slug  = $parts[0] ?? '';

    $routes = [
        'about'                     => 'page-about.php',
        'cars'                      => 'page-new-cars.php',
        'contact'                   => 'page-contact.php',
        'compare'                   => 'page-compare.php',
        'electric-vehicles'         => 'page-electric-vehicles.php',
        'nepal-car-price-estimator' => 'page-price-estimator.php',
        'new-cars'                  => 'page-new-cars.php',
        'news'                      => 'page-news.php',
        'privacy-policy'            => 'page-privacy-policy.php',
    ];

    // Root URL → homepage template
    if ( empty( $parts ) ) {
        $home_tpl = get_template_directory() . '/front-page.php';
        if ( file_exists( $home_tpl ) ) return $home_tpl;
    }

    if ( isset( $routes[ $slug ] ) && ! isset( $parts[1] ) ) {
        $route_tpl = get_template_directory() . '/' . $routes[ $slug ];
        if ( file_exists( $route_tpl ) ) {
            return $route_tpl;
        }
    }

    if ( 'compare' === $slug && isset( $parts[1] ) && ! isset( $parts[2] ) ) {
        $compare_tpl = get_template_directory() . '/page-comparison-detail.php';
        if ( file_exists( $compare_tpl ) ) {
            return $compare_tpl;
        }
    }

    return $template;
}, 6 );

// Any WordPress page whose URL contains /cars/[brand]/[model]/ loads page-vehicle.php
// automatically — no manual template assignment needed.

add_filter( 'template_include', function ( string $template ): string {
    $parts = ngt_request_path_parts();

    // Match /cars/[brand]/[model]/ — exactly 3 path segments starting with "cars"
    if ( ngt_is_vehicle_route_request() ) {
        $vehicle_tpl = get_template_directory() . '/page-vehicle.php';
        if ( file_exists( $vehicle_tpl ) ) {
            ngt_mark_current_request_success();
            return $vehicle_tpl;
        }
    }

    // Match /cars/[brand]/ — exactly 2 segments → brand listing template
    if ( isset( $parts[0], $parts[1] ) && ! isset( $parts[2] ) && $parts[0] === 'cars' ) {
        $brand_tpl = get_template_directory() . '/page-brand.php';
        if ( file_exists( $brand_tpl ) ) {
            ngt_mark_current_request_success();
            return $brand_tpl;
        }
    }

    // Match /cars/ — 1 segment → same listing as /new-cars/
    if ( isset( $parts[0] ) && ! isset( $parts[1] ) && $parts[0] === 'cars' ) {
        $cars_tpl = get_template_directory() . '/page-new-cars.php';
        if ( file_exists( $cars_tpl ) ) {
            ngt_mark_current_request_success();
            return $cars_tpl;
        }
    }

    return $template;
} );

// Also enqueue vehicle assets for /cars/[brand]/[model]/ pages
add_action( 'wp_enqueue_scripts', function () {
    $uri   = parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
    $parts = array_values( array_filter( explode( '/', trim( $uri, '/' ) ) ) );
    if ( isset( $parts[0], $parts[1], $parts[2] ) && $parts[0] === 'cars' ) {
        wp_enqueue_style( 'ng-vehicle', get_template_directory_uri() . '/assets/css/vehicle.css', [ 'ng-theme' ], NGT_VERSION );
        wp_enqueue_script( 'ng-enquiry', get_template_directory_uri() . '/assets/js/enquiry.js', [ 'ng-auth' ], NGT_VERSION, true );
    }
}, 20 );

// ── Events: CPT + admin fields ───────────────────────────────────────────────

add_action( 'init', function () {
    register_post_type( 'event', [
        'labels' => [
            'name'               => __( 'Events', 'nepaligarage' ),
            'singular_name'      => __( 'Event', 'nepaligarage' ),
            'add_new'            => __( 'Add Event', 'nepaligarage' ),
            'add_new_item'       => __( 'Add New Event', 'nepaligarage' ),
            'edit_item'          => __( 'Edit Event', 'nepaligarage' ),
            'new_item'           => __( 'New Event', 'nepaligarage' ),
            'view_item'          => __( 'View Event', 'nepaligarage' ),
            'search_items'       => __( 'Search Events', 'nepaligarage' ),
            'not_found'          => __( 'No events found', 'nepaligarage' ),
            'not_found_in_trash' => __( 'No events found in Trash', 'nepaligarage' ),
            'menu_name'          => __( 'Events', 'nepaligarage' ),
        ],
        'public'       => true,
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-calendar-alt',
        'has_archive'  => 'events',
        'rewrite'      => [ 'slug' => 'events', 'with_front' => false ],
        'supports'     => [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ],
    ] );

    if ( ! get_option( 'ngt_events_rewrite_flushed' ) ) {
        flush_rewrite_rules( false );
        update_option( 'ngt_events_rewrite_flushed', 1, false );
    }
} );

function ngt_event_fields(): array {
    return [
        'event_date'       => 'Event date',
        'event_end_date'   => 'End date',
        'event_venue'      => 'Venue',
        'event_city'       => 'City',
        'event_organizer'  => 'Organizer',
        'event_link'       => 'Registration / external link',
        'event_gallery'    => 'Gallery image URLs (one per line)',
    ];
}

add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'ng_event_details',
        __( 'Event Details', 'nepaligarage' ),
        function ( WP_Post $post ) {
            wp_nonce_field( 'ng_save_event_details', 'ng_event_details_nonce' );
            $values = [];
            foreach ( ngt_event_fields() as $key => $label ) {
                $values[ $key ] = get_post_meta( $post->ID, $key, true );
            }
            ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="ng-event-date"><?php esc_html_e( 'Event date', 'nepaligarage' ); ?></label></th>
                        <td><input type="date" id="ng-event-date" name="ng_event_fields[event_date]" value="<?php echo esc_attr( $values['event_date'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ng-event-end-date"><?php esc_html_e( 'End date', 'nepaligarage' ); ?></label></th>
                        <td><input type="date" id="ng-event-end-date" name="ng_event_fields[event_end_date]" value="<?php echo esc_attr( $values['event_end_date'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ng-event-venue"><?php esc_html_e( 'Venue', 'nepaligarage' ); ?></label></th>
                        <td><input type="text" id="ng-event-venue" name="ng_event_fields[event_venue]" value="<?php echo esc_attr( $values['event_venue'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ng-event-city"><?php esc_html_e( 'City', 'nepaligarage' ); ?></label></th>
                        <td><input type="text" id="ng-event-city" name="ng_event_fields[event_city]" value="<?php echo esc_attr( $values['event_city'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ng-event-organizer"><?php esc_html_e( 'Organizer', 'nepaligarage' ); ?></label></th>
                        <td><input type="text" id="ng-event-organizer" name="ng_event_fields[event_organizer]" value="<?php echo esc_attr( $values['event_organizer'] ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ng-event-link"><?php esc_html_e( 'Registration / external link', 'nepaligarage' ); ?></label></th>
                        <td><input type="url" id="ng-event-link" name="ng_event_fields[event_link]" value="<?php echo esc_attr( $values['event_link'] ); ?>" class="regular-text code"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ng-event-gallery"><?php esc_html_e( 'Gallery image URLs', 'nepaligarage' ); ?></label></th>
                        <td>
                            <textarea id="ng-event-gallery" name="ng_event_fields[event_gallery]" rows="5" class="large-text code"><?php echo esc_textarea( $values['event_gallery'] ); ?></textarea>
                            <p class="description"><?php esc_html_e( 'Paste one image URL per line for additional event photos.', 'nepaligarage' ); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php
        },
        'event',
        'normal',
        'default'
    );
} );

add_action( 'save_post_event', function ( int $post_id ) {
    if ( ! isset( $_POST['ng_event_details_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ng_event_details_nonce'] ) ), 'ng_save_event_details' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $raw = isset( $_POST['ng_event_fields'] ) && is_array( $_POST['ng_event_fields'] ) ? wp_unslash( $_POST['ng_event_fields'] ) : [];
    foreach ( ngt_event_fields() as $key => $label ) {
        $value = $raw[ $key ] ?? '';
        switch ( $key ) {
            case 'event_link':
                $value = esc_url_raw( $value );
                break;
            case 'event_gallery':
                $lines = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $value ) ) );
                $lines = array_map( 'esc_url_raw', $lines );
                $value = implode( "\n", array_filter( $lines ) );
                break;
            default:
                $value = sanitize_text_field( $value );
                break;
        }

        if ( '' === $value ) {
            delete_post_meta( $post_id, $key );
        } else {
            update_post_meta( $post_id, $key, $value );
        }
    }
} );

function ngt_event_meta( int $post_id, string $key ): string {
    return (string) get_post_meta( $post_id, $key, true );
}

function ngt_event_gallery_urls( int $post_id ): array {
    $raw = ngt_event_meta( $post_id, 'event_gallery' );
    if ( '' === $raw ) {
        return [];
    }

    return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $raw ) ) ) );
}

// ── Widget areas ──────────────────────────────────────────────────────────────

add_action( 'widgets_init', function () {
    register_sidebar( [
        'name'          => __( 'Sidebar', 'nepaligarage' ),
        'id'            => 'sidebar-1',
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ] );
} );
