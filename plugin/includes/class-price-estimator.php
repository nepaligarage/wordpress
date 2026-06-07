<?php
/**
 * Nepal on-road price estimator — form rendering and AJAX handler.
 *
 * Formula:
 *   base_npr     = base_usd × exchange_rate
 *   duty         = base_npr × duty_rate
 *   vat          = (base_npr + duty) × 0.13
 *   total        = base_npr + duty + vat + road_tax + handling
 *
 * @package NepaliGarage
 */

defined( 'ABSPATH' ) || exit;

class NG_Price_Estimator {

    // -----------------------------------------------------------------------
    // Bootstrap: wire up AJAX handlers once.
    // -----------------------------------------------------------------------

    public function __construct() {
        static $hooked = false;
        if ( ! $hooked ) {
            add_action( 'wp_ajax_ng_calculate_price',        [ $this, 'ajax_calculate_price' ] );
            add_action( 'wp_ajax_nopriv_ng_calculate_price', [ $this, 'ajax_calculate_price' ] );
            $hooked = true;
        }
    }

    // -----------------------------------------------------------------------
    // Public API
    // -----------------------------------------------------------------------

    /**
     * Render the estimator form HTML.
     */
    public function render_form(): string {
        ob_start();
        ?>
        <div class="ng-price-estimator" id="ng-price-estimator">
            <h3 class="ng-estimator-title">
                <?php esc_html_e( 'Nepal On-Road Price Estimator', 'nepaligarage' ); ?>
            </h3>
            <p class="ng-estimator-desc">
                <?php esc_html_e( 'Enter the international base price to estimate the total on-road cost in Nepal.', 'nepaligarage' ); ?>
            </p>

            <form id="ng-estimator-form" class="ng-estimator-form" novalidate>
                <?php wp_nonce_field( 'ng_price_estimator', 'ng_nonce' ); ?>

                <div class="ng-form-row">
                    <label for="ng-base-price"><?php esc_html_e( 'Base Price (USD)', 'nepaligarage' ); ?></label>
                    <input
                        type="number"
                        id="ng-base-price"
                        name="base_price"
                        min="1"
                        step="0.01"
                        required
                        placeholder="e.g. 25000"
                    >
                </div>

                <div class="ng-form-row">
                    <label for="ng-vehicle-type"><?php esc_html_e( 'Vehicle Type', 'nepaligarage' ); ?></label>
                    <select id="ng-vehicle-type" name="vehicle_type">
                        <option value="ev"><?php esc_html_e( 'Electric Vehicle (EV)', 'nepaligarage' ); ?></option>
                        <option value="ice"><?php esc_html_e( 'Internal Combustion (ICE)', 'nepaligarage' ); ?></option>
                    </select>
                </div>

                <div class="ng-form-row">
                    <label for="ng-engine-size"><?php esc_html_e( 'Engine / Battery Capacity', 'nepaligarage' ); ?></label>
                    <input
                        type="text"
                        id="ng-engine-size"
                        name="engine_size"
                        placeholder="e.g. 60.5 kWh or 1998cc"
                    >
                </div>

                <button type="submit" class="ng-btn ng-btn--primary">
                    <?php esc_html_e( 'Calculate Nepal Price', 'nepaligarage' ); ?>
                </button>
            </form>

            <div id="ng-estimator-result" class="ng-estimator-result" hidden aria-live="polite">
                <!-- Populated by JS -->
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    // -----------------------------------------------------------------------
    // AJAX handler
    // -----------------------------------------------------------------------

    public function ajax_calculate_price(): void {
        check_ajax_referer( 'ng_price_estimator', 'nonce' );

        $base_price   = filter_input( INPUT_POST, 'base_price',   FILTER_VALIDATE_FLOAT );
        $vehicle_type = sanitize_key( $_POST['vehicle_type'] ?? 'ev' );

        if ( ! $base_price || $base_price <= 0 ) {
            wp_send_json_error( [ 'message' => __( 'Invalid base price.', 'nepaligarage' ) ], 400 );
        }

        $vehicle_type = in_array( $vehicle_type, [ 'ev', 'ice' ], true ) ? $vehicle_type : 'ev';

        $breakdown = $this->calculate( $base_price, $vehicle_type );

        wp_send_json_success( $breakdown );
    }

    // -----------------------------------------------------------------------
    // Calculation
    // -----------------------------------------------------------------------

    /**
     * Calculate Nepal on-road price breakdown.
     *
     * @param  float  $base_usd
     * @param  string $vehicle_type  'ev' | 'ice'
     * @return array<string, mixed>
     */
    public function calculate( float $base_usd, string $vehicle_type = 'ev' ): array {
        $exchange_rate = (float) get_option( 'ng_usd_rate',      137 );
        $duty_rate_ev  = (float) get_option( 'ng_duty_rate_ev',  0.40 );
        $duty_rate_ice = (float) get_option( 'ng_duty_rate_ice', 0.60 );
        $road_tax      = (float) get_option( 'ng_road_tax',      5000 );
        $handling      = (float) get_option( 'ng_handling',      25000 );

        $duty_rate = ( $vehicle_type === 'ev' ) ? $duty_rate_ev : $duty_rate_ice;

        $base_npr  = $base_usd * $exchange_rate;
        $duty      = $base_npr * $duty_rate;
        $vat_base  = $base_npr + $duty;
        $vat       = $vat_base * 0.13;
        $total     = $base_npr + $duty + $vat + $road_tax + $handling;

        $fmt = fn( float $v ): string => 'NPR ' . number_format( $v, 0, '.', ',' );

        return [
            'base_usd'     => $base_usd,
            'exchange_rate' => $exchange_rate,
            'vehicle_type' => $vehicle_type,
            'duty_rate'    => $duty_rate,
            'breakdown'    => [
                [
                    'label'  => __( 'Base Price (NPR)', 'nepaligarage' ),
                    'value'  => $fmt( $base_npr ),
                    'raw'    => $base_npr,
                ],
                [
                    'label'  => sprintf(
                        /* translators: %s: duty percentage */
                        __( 'Customs Duty (%s%%)', 'nepaligarage' ),
                        (int) ( $duty_rate * 100 )
                    ),
                    'value'  => $fmt( $duty ),
                    'raw'    => $duty,
                ],
                [
                    'label'  => __( 'VAT (13%)', 'nepaligarage' ),
                    'value'  => $fmt( $vat ),
                    'raw'    => $vat,
                ],
                [
                    'label'  => __( 'Road Tax', 'nepaligarage' ),
                    'value'  => $fmt( $road_tax ),
                    'raw'    => $road_tax,
                ],
                [
                    'label'  => __( 'Handling / Registration', 'nepaligarage' ),
                    'value'  => $fmt( $handling ),
                    'raw'    => $handling,
                ],
            ],
            'total'        => $fmt( $total ),
            'total_raw'    => $total,
        ];
    }
}
