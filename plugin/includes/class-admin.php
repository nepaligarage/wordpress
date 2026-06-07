<?php
/**
 * WordPress admin settings page for NepaliGarage Core.
 *
 * Registered under Settings → NepaliGarage.
 *
 * @package NepaliGarage
 */

defined( 'ABSPATH' ) || exit;

class NG_Admin {

    private const PAGE_SLUG    = 'nepaligarage';
    private const OPTION_GROUP = 'ng_settings';

    public function __construct() {
        add_action( 'admin_menu',  [ $this, 'add_settings_page' ] );
        add_action( 'admin_init',  [ $this, 'register_settings' ] );
    }

    // -----------------------------------------------------------------------
    // Menu
    // -----------------------------------------------------------------------

    public function add_settings_page(): void {
        add_options_page(
            __( 'NepaliGarage Settings', 'nepaligarage' ),
            __( 'NepaliGarage', 'nepaligarage' ),
            'manage_options',
            self::PAGE_SLUG,
            [ $this, 'render_settings_page' ]
        );
    }

    // -----------------------------------------------------------------------
    // Settings API registration
    // -----------------------------------------------------------------------

    public function register_settings(): void {

        // ---- Supabase section ----
        add_settings_section(
            'ng_section_supabase',
            __( 'Supabase Connection', 'nepaligarage' ),
            '__return_false',
            self::PAGE_SLUG
        );

        $this->register_field( 'ng_supabase_url', __( 'Supabase Project URL', 'nepaligarage' ), 'url' );
        $this->register_field( 'ng_supabase_key', __( 'Supabase Anon Key', 'nepaligarage' ),    'password', 'ng_section_supabase' );

        // ---- Nepal pricing section ----
        add_settings_section(
            'ng_section_pricing',
            __( 'Nepal Pricing Defaults', 'nepaligarage' ),
            [ $this, 'render_pricing_section_intro' ],
            self::PAGE_SLUG
        );

        $this->register_field( 'ng_usd_rate',      __( 'USD → NPR Exchange Rate', 'nepaligarage' ),   'number', 'ng_section_pricing' );
        $this->register_field( 'ng_duty_rate_ev',  __( 'EV Duty Rate (decimal)', 'nepaligarage' ),    'number', 'ng_section_pricing' );
        $this->register_field( 'ng_duty_rate_ice', __( 'ICE Duty Rate (decimal)', 'nepaligarage' ),   'number', 'ng_section_pricing' );
        $this->register_field( 'ng_road_tax',      __( 'Road Tax (NPR)', 'nepaligarage' ),            'number', 'ng_section_pricing' );
        $this->register_field( 'ng_handling',      __( 'Handling / Registration (NPR)', 'nepaligarage' ), 'number', 'ng_section_pricing' );
    }

    /**
     * Helper: register one settings field and its underlying option.
     *
     * @param string $option_name
     * @param string $label
     * @param string $input_type   'text' | 'url' | 'password' | 'number'
     * @param string $section_id
     */
    private function register_field(
        string $option_name,
        string $label,
        string $input_type = 'text',
        string $section_id = 'ng_section_supabase'
    ): void {

        register_setting(
            self::OPTION_GROUP,
            $option_name,
            [
                'sanitize_callback' => $this->sanitize_callback_for( $input_type ),
                'default'           => '',
            ]
        );

        add_settings_field(
            $option_name,
            esc_html( $label ),
            function() use ( $option_name, $input_type ) {
                $this->render_input( $option_name, $input_type );
            },
            self::PAGE_SLUG,
            $section_id
        );
    }

    // -----------------------------------------------------------------------
    // Render helpers
    // -----------------------------------------------------------------------

    public function render_settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'NepaliGarage Settings', 'nepaligarage' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( self::OPTION_GROUP );
                do_settings_sections( self::PAGE_SLUG );
                submit_button( __( 'Save Settings', 'nepaligarage' ) );
                ?>
            </form>
        </div>
        <?php
    }

    public function render_pricing_section_intro(): void {
        echo '<p>' . esc_html__( 'These values drive the Nepal on-road price estimator. Keep them current.', 'nepaligarage' ) . '</p>';
    }

    private function render_input( string $option_name, string $input_type ): void {
        $value = get_option( $option_name, '' );

        // Never render the anon key in plain text.
        if ( $option_name === 'ng_supabase_key' && ! empty( $value ) ) {
            echo '<input type="password" id="' . esc_attr( $option_name ) . '" name="' . esc_attr( $option_name ) . '"'
                . ' value="' . esc_attr( $value ) . '" class="regular-text" autocomplete="new-password">';
            echo '<p class="description">' . esc_html__( 'Stored securely. Leave blank to keep existing value.', 'nepaligarage' ) . '</p>';
            return;
        }

        $step = in_array( $option_name, [ 'ng_duty_rate_ev', 'ng_duty_rate_ice' ], true ) ? ' step="0.01" min="0" max="2"' : '';

        printf(
            '<input type="%s" id="%s" name="%s" value="%s" class="regular-text"%s>',
            esc_attr( $input_type === 'password' ? 'text' : $input_type ),
            esc_attr( $option_name ),
            esc_attr( $option_name ),
            esc_attr( (string) $value ),
            $step
        );
    }

    // -----------------------------------------------------------------------
    // Sanitization
    // -----------------------------------------------------------------------

    private function sanitize_callback_for( string $input_type ): callable {
        return match ( $input_type ) {
            'url'      => 'esc_url_raw',
            'number'   => fn( $v ) => (string) floatval( $v ),
            'password' => fn( $v ) => sanitize_text_field( $v ),
            default    => 'sanitize_text_field',
        };
    }
}
