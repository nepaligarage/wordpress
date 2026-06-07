<?php
/**
 * Plugin Name: NepaliGarage Core
 * Plugin URI:  https://nepaligarage.com
 * Description: Connects nepaligarage.com to Supabase and renders vehicle comparison tables.
 * Version:     0.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author:      NepaliGarage
 * Text Domain: nepaligarage
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

define( 'NG_VERSION',      '0.1.0' );
define( 'NG_PLUGIN_DIR',   plugin_dir_path( __FILE__ ) );
define( 'NG_PLUGIN_URL',   plugin_dir_url( __FILE__ ) );

/**
 * Supabase project URL — stored as WP option so admin can update it.
 * Falls back to the default project URL if option is not yet saved.
 */
define(
    'NG_SUPABASE_URL',
    get_option( 'ng_supabase_url', 'https://eukghqvlvlveshchnugk.supabase.co' )
);

// ---------------------------------------------------------------------------
// Bootstrap
// ---------------------------------------------------------------------------

add_action( 'plugins_loaded', 'ng_load_plugin' );

function ng_load_plugin(): void {
    ng_load_includes();
    ng_register_shortcodes();
}

function ng_load_includes(): void {
    require_once NG_PLUGIN_DIR . 'includes/class-supabase.php';
    require_once NG_PLUGIN_DIR . 'includes/class-comparison-renderer.php';
    require_once NG_PLUGIN_DIR . 'includes/class-price-estimator.php';

    if ( is_admin() ) {
        require_once NG_PLUGIN_DIR . 'includes/class-admin.php';
        new NG_Admin();
    }
}

// ---------------------------------------------------------------------------
// Shortcodes
// ---------------------------------------------------------------------------

function ng_register_shortcodes(): void {
    add_shortcode( 'ng_compare',         'ng_shortcode_compare' );
    add_shortcode( 'ng_price_estimator', 'ng_shortcode_price_estimator' );
}

/**
 * [ng_compare variant_ids="slug-a,slug-b"]
 */
function ng_shortcode_compare( array $atts ): string {
    $atts = shortcode_atts(
        [ 'variant_ids' => '' ],
        $atts,
        'ng_compare'
    );

    $raw = sanitize_text_field( $atts['variant_ids'] );

    if ( empty( $raw ) ) {
        return '<p class="ng-error">' . esc_html__( 'Please provide variant_ids.', 'nepaligarage' ) . '</p>';
    }

    $slugs = array_map( 'sanitize_title', array_map( 'trim', explode( ',', $raw ) ) );
    $slugs = array_filter( $slugs );

    if ( count( $slugs ) < 2 ) {
        return '<p class="ng-error">' . esc_html__( 'Please provide at least two variant IDs.', 'nepaligarage' ) . '</p>';
    }

    // Check transient cache.
    $cache_key = 'ng_compare_' . md5( implode( ',', $slugs ) );
    $cached    = get_transient( $cache_key );

    if ( false !== $cached ) {
        return $cached;
    }

    $supabase = new NG_Supabase();
    $variants = $supabase->get_variants_with_specs( $slugs );

    if ( empty( $variants ) ) {
        return '<p class="ng-error">' . esc_html__( 'Data temporarily unavailable. Please try again later.', 'nepaligarage' ) . '</p>';
    }

    $renderer = new NG_Comparison_Renderer();
    $html     = $renderer->render( $variants );

    set_transient( $cache_key, $html, HOUR_IN_SECONDS );

    return $html;
}

/**
 * [ng_price_estimator]
 */
function ng_shortcode_price_estimator( array $atts ): string {
    $estimator = new NG_Price_Estimator();
    return $estimator->render_form();
}

// ---------------------------------------------------------------------------
// Assets
// ---------------------------------------------------------------------------

add_action( 'wp_enqueue_scripts', 'ng_enqueue_assets' );

function ng_enqueue_assets(): void {
    wp_enqueue_style(
        'nepaligarage',
        NG_PLUGIN_URL . 'assets/css/nepaligarage.css',
        [],
        NG_VERSION
    );

    wp_enqueue_script(
        'nepaligarage',
        NG_PLUGIN_URL . 'assets/js/nepaligarage.js',
        [],
        NG_VERSION,
        true // footer
    );

    // Pass AJAX URL and nonce to JS — never expose Supabase key here.
    wp_localize_script(
        'nepaligarage',
        'ngData',
        [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'ng_price_estimator' ),
        ]
    );
}

// ---------------------------------------------------------------------------
// Activation / Deactivation
// ---------------------------------------------------------------------------

register_activation_hook( __FILE__, 'ng_activate' );

function ng_activate(): void {
    // Set default options on first activation.
    add_option( 'ng_supabase_url',  'https://eukghqvlvlveshchnugk.supabase.co' );
    add_option( 'ng_supabase_key',  '' );
    add_option( 'ng_usd_rate',      '137' );
    add_option( 'ng_duty_rate_ev',  '0.40' );
    add_option( 'ng_duty_rate_ice', '0.60' );
    add_option( 'ng_road_tax',      '5000' );
    add_option( 'ng_handling',      '25000' );
}

register_deactivation_hook( __FILE__, 'ng_deactivate' );

function ng_deactivate(): void {
    // Clear all comparison transients on deactivation.
    global $wpdb;
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_ng_compare_%'
            OR option_name LIKE '_transient_timeout_ng_compare_%'"
    );
}
