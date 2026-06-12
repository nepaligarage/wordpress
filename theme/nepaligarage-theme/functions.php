<?php
defined( 'ABSPATH' ) || exit;

define( 'NGT_VERSION', '1.0.0' );
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
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500&family=Noto+Sans+Devanagari:wght@400;500;700&display=swap',
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
function ngt_variant_price_display( array $variant, array $price_rows = [] ): array {
    $variant_id = $variant['id'] ?? '';
    $price      = $variant_id && isset( $price_rows[ $variant_id ] ) ? $price_rows[ $variant_id ] : [];
    $amount     = null;

    foreach ( [ 'price_npr', 'starting_price_npr', 'on_road_price_npr', 'amount_npr' ] as $key ) {
        if ( isset( $price[ $key ] ) && '' !== $price[ $key ] ) {
            $amount = (float) $price[ $key ];
            break;
        }
    }

    if ( null !== $amount ) {
        $source = is_array( $price['source'] ?? null ) ? $price['source'] : [];

        return [
            'label'       => 'NPR ' . number_format( $amount ),
            'confidence'  => sanitize_html_class( $price['confidence'] ?? 'unverified' ),
            'source_url'  => $source['url'] ?? $source['source_url'] ?? '',
            'source_name' => $source['label'] ?? $source['name'] ?? '',
        ];
    }

    if ( ! empty( $variant['starting_price_npr'] ) ) {
        return [
            'label'       => 'NPR ' . number_format( (float) $variant['starting_price_npr'] ),
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
    $uri   = parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
    $parts = array_values( array_filter( explode( '/', trim( $uri, '/' ) ) ) );

    // Match /cars/[brand]/[model]/ — exactly 3 path segments starting with "cars"
    if ( isset( $parts[0], $parts[1], $parts[2] ) && $parts[0] === 'cars' ) {
        $vehicle_tpl = get_template_directory() . '/page-vehicle.php';
        if ( file_exists( $vehicle_tpl ) ) {
            return $vehicle_tpl;
        }
    }

    // Match /cars/[brand]/ — exactly 2 segments → brand listing template
    if ( isset( $parts[0], $parts[1] ) && ! isset( $parts[2] ) && $parts[0] === 'cars' ) {
        $brand_tpl = get_template_directory() . '/page-brand.php';
        if ( file_exists( $brand_tpl ) ) {
            return $brand_tpl;
        }
    }

    // Match /cars/ — 1 segment → same listing as /new-cars/
    if ( isset( $parts[0] ) && ! isset( $parts[1] ) && $parts[0] === 'cars' ) {
        $cars_tpl = get_template_directory() . '/page-new-cars.php';
        if ( file_exists( $cars_tpl ) ) {
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
