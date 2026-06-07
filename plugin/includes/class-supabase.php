<?php
/**
 * Supabase API client — all requests are server-side via wp_remote_get().
 * The anon key is NEVER exposed to the browser.
 *
 * @package NepaliGarage
 */

defined( 'ABSPATH' ) || exit;

class NG_Supabase {

    private string $base_url;
    private string $anon_key;
    private int    $cache_ttl = HOUR_IN_SECONDS;

    public function __construct() {
        $this->base_url  = trailingslashit( get_option( 'ng_supabase_url', NG_SUPABASE_URL ) ) . 'rest/v1/';
        $this->anon_key  = (string) get_option( 'ng_supabase_key', '' );
    }

    // -----------------------------------------------------------------------
    // Public API
    // -----------------------------------------------------------------------

    /**
     * Fetch variants (with nested specs) for a list of slugs.
     *
     * @param  string[] $variant_slugs
     * @return array<int, array<string, mixed>>
     */
    public function get_variants_with_specs( array $variant_slugs ): array {
        if ( empty( $variant_slugs ) ) {
            return [];
        }

        $slugs_csv = implode( ',', array_map( 'sanitize_title', $variant_slugs ) );
        $cache_key = 'ng_sb_variants_' . md5( $slugs_csv );

        $cached = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $select = implode( ',', [
            '*',
            'model(*,brand(*))',
            'variant_specs(*,spec_field(*),source:spec_sources(*))',
        ] );

        $endpoint = add_query_arg(
            [
                'slug'   => 'in.(' . $slugs_csv . ')',
                'select' => $select,
            ],
            $this->base_url . 'variants'
        );

        $data = $this->request( $endpoint );

        if ( empty( $data ) ) {
            return [];
        }

        set_transient( $cache_key, $data, $this->cache_ttl );

        return $data;
    }

    /**
     * Fetch a saved comparison record by its slug.
     *
     * @param  string $slug
     * @return array<string, mixed>
     */
    public function get_comparison_by_slug( string $slug ): array {
        $slug      = sanitize_title( $slug );
        $cache_key = 'ng_sb_comparison_' . md5( $slug );

        $cached = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $endpoint = add_query_arg(
            [
                'slug'   => 'eq.' . $slug,
                'select' => '*',
                'limit'  => '1',
            ],
            $this->base_url . 'comparisons'
        );

        $data = $this->request( $endpoint );

        if ( empty( $data ) || ! isset( $data[0] ) ) {
            return [];
        }

        $record = $data[0];
        set_transient( $cache_key, $record, $this->cache_ttl );

        return $record;
    }

    /**
     * Fetch price data for a variant by its ID.
     *
     * @param  string $variant_id
     * @return array<string, mixed>
     */
    public function get_price( string $variant_id ): array {
        $variant_id = sanitize_text_field( $variant_id );
        $cache_key  = 'ng_sb_price_' . md5( $variant_id );

        $cached = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $endpoint = add_query_arg(
            [
                'variant_id' => 'eq.' . $variant_id,
                'select'     => '*',
                'limit'      => '1',
            ],
            $this->base_url . 'prices'
        );

        $data = $this->request( $endpoint );

        if ( empty( $data ) || ! isset( $data[0] ) ) {
            return [];
        }

        $record = $data[0];
        set_transient( $cache_key, $record, $this->cache_ttl );

        return $record;
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Execute an authenticated GET request to the Supabase REST API.
     *
     * @param  string $url Full URL with query params.
     * @return array<int|string, mixed> Decoded JSON or empty array on failure.
     */
    private function request( string $url ): array {
        if ( empty( $this->anon_key ) ) {
            error_log( '[NepaliGarage] Supabase anon key is not configured.' );
            return [];
        }

        $response = wp_remote_get(
            $url,
            [
                'timeout' => 10,
                'headers' => [
                    'apikey'        => $this->anon_key,
                    'Authorization' => 'Bearer ' . $this->anon_key,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ],
            ]
        );

        if ( is_wp_error( $response ) ) {
            error_log( '[NepaliGarage] Supabase request error: ' . $response->get_error_message() );
            return [];
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( $code < 200 || $code >= 300 ) {
            error_log( sprintf( '[NepaliGarage] Supabase returned HTTP %d for %s — %s', $code, $url, $body ) );
            return [];
        }

        $decoded = json_decode( $body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            error_log( '[NepaliGarage] JSON decode error: ' . json_last_error_msg() );
            return [];
        }

        return is_array( $decoded ) ? $decoded : [];
    }
}
