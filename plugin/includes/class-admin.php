<?php
/**
 * WordPress admin for NepaliGarage Core.
 * Settings + 4 operator sub-pages: Leads, Orders, Vehicles, Accessories.
 *
 * @package NepaliGarage
 */

defined( 'ABSPATH' ) || exit;

class NG_Admin {

    private const PAGE_SLUG    = 'nepaligarage';
    private const OPTION_GROUP = 'ng_settings';

    // Supabase REST base URL (server-side calls use service key)
    private function sb_url( string $endpoint ): string {
        return trailingslashit( get_option( 'ng_supabase_url', 'https://eukghqvlvlveshchnugk.supabase.co' ) )
             . 'rest/v1/' . $endpoint;
    }

    private function service_key(): string {
        return (string) get_option( 'ng_supabase_service_key', '' );
    }

    private function sb_get( string $endpoint, array $params ): array {
        $key = $this->service_key();
        if ( empty( $key ) ) return [];

        $response = wp_remote_get( add_query_arg( $params, $this->sb_url( $endpoint ) ), [
            'timeout' => 15,
            'headers' => [
                'apikey'        => $key,
                'Authorization' => 'Bearer ' . $key,
                'Accept'        => 'application/json',
                'Prefer'        => 'count=exact',
            ],
        ] );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) >= 300 ) {
            return [];
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        return is_array( $data ) ? $data : [];
    }

    private function sb_patch( string $endpoint, array $params, array $body ): bool {
        $key = $this->service_key();
        if ( empty( $key ) ) return false;

        $response = wp_remote_request( add_query_arg( $params, $this->sb_url( $endpoint ) ), [
            'method'  => 'PATCH',
            'timeout' => 15,
            'headers' => [
                'apikey'        => $key,
                'Authorization' => 'Bearer ' . $key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode( $body ),
        ] );

        return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) < 300;
    }

    private function clear_comparison_caches(): void {
        global $wpdb;

        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_ng_compare_%'
                OR option_name LIKE '_transient_timeout_ng_compare_%'
                OR option_name LIKE '_transient_ng_sb_comparison_%'
                OR option_name LIKE '_transient_timeout_ng_sb_comparison_%'
                OR option_name LIKE '_transient_ngt2_%'
                OR option_name LIKE '_transient_timeout_ngt2_%'"
        );
    }

    private function comparison_variant_slugs( array $record ): array {
        foreach ( [ 'variant_slugs', 'variants', 'vehicle_slugs', 'vehicles' ] as $key ) {
            if ( ! isset( $record[ $key ] ) || '' === $record[ $key ] ) {
                continue;
            }

            $value = $record[ $key ];
            if ( is_string( $value ) ) {
                $decoded = json_decode( $value, true );
                $value   = JSON_ERROR_NONE === json_last_error() ? $decoded : explode( ',', $value );
            }

            if ( ! is_array( $value ) ) {
                continue;
            }

            $slugs = [];
            foreach ( $value as $item ) {
                if ( is_array( $item ) ) {
                    $item = $item['slug'] ?? $item['variant_slug'] ?? '';
                }

                $item = sanitize_title( (string) $item );
                if ( $item ) {
                    $slugs[] = $item;
                }
            }

            $slugs = array_values( array_unique( $slugs ) );
            if ( count( $slugs ) >= 2 ) {
                return array_slice( $slugs, 0, 2 );
            }
        }

        return [];
    }

    public function __construct() {
        add_action( 'admin_menu',  [ $this, 'register_menus' ] );
        add_action( 'admin_init',  [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    // ── Menu registration ─────────────────────────────────────────────────────

    public function register_menus(): void {
        // Top-level NG menu
        add_menu_page(
            'NepaliGarage',
            'NepaliGarage',
            'read',
            'ng-dashboard',
            [ $this, 'render_overview' ],
            'dashicons-car',
            30
        );

        // Sub-pages
        add_submenu_page( 'ng-dashboard', 'Overview',    'Overview',    'read',              'ng-dashboard',     [ $this, 'render_overview' ] );
        add_submenu_page( 'ng-dashboard', 'Leads',       'Leads',       'ng_view_leads',     'ng-leads',         [ $this, 'render_leads' ] );
        add_submenu_page( 'ng-dashboard', 'Orders',      'Orders',      'ng_view_orders',    'ng-orders',        [ $this, 'render_orders' ] );
        add_submenu_page( 'ng-dashboard', 'Vehicles',    'Vehicles',    'ng_edit_vehicles',  'ng-vehicles',      [ $this, 'render_vehicles' ] );
        add_submenu_page( 'ng-dashboard', 'Comparisons', 'Comparisons', 'ng_edit_vehicles',  'ng-comparisons',   [ $this, 'render_comparisons' ] );
        add_submenu_page( 'ng-dashboard', 'Accessories', 'Accessories', 'ng_manage_accessories', 'ng-accessories', [ $this, 'render_accessories' ] );

        // Settings (manage_options only)
        add_submenu_page( 'ng-dashboard', 'Settings',   'Settings',    'manage_options',    self::PAGE_SLUG,    [ $this, 'render_settings_page' ] );
    }

    public function enqueue_admin_assets( string $hook ): void {
        if ( strpos( $hook, 'ng-' ) === false && strpos( $hook, 'nepaligarage' ) === false ) return;
        wp_enqueue_style( 'ng-admin', plugins_url( 'assets/css/admin.css', dirname( __FILE__ ) ), [], '1.0.0' );
    }

    // ── Settings registration ─────────────────────────────────────────────────

    public function register_settings(): void {

        add_settings_section( 'ng_section_supabase', 'Supabase Connection', '__return_false', self::PAGE_SLUG );
        $this->register_field( 'ng_supabase_url',         'Supabase Project URL',   'url' );
        $this->register_field( 'ng_supabase_key',         'Supabase Anon Key',      'password' );
        $this->register_field( 'ng_supabase_service_key', 'Supabase Service Key',   'password' );

        add_settings_section( 'ng_section_pricing', 'Nepal Pricing Defaults', [ $this, 'render_pricing_section_intro' ], self::PAGE_SLUG );
        $this->register_field( 'ng_usd_rate',      'USD → NPR Exchange Rate',    'number', 'ng_section_pricing' );
        $this->register_field( 'ng_duty_rate_ev',  'EV Duty Rate (decimal)',     'number', 'ng_section_pricing' );
        $this->register_field( 'ng_duty_rate_ice', 'ICE Duty Rate (decimal)',    'number', 'ng_section_pricing' );
        $this->register_field( 'ng_road_tax',      'Road Tax (NPR)',             'number', 'ng_section_pricing' );
        $this->register_field( 'ng_handling',      'Handling / Registration (NPR)', 'number', 'ng_section_pricing' );
    }

    private function register_field( string $option_name, string $label, string $input_type = 'text', string $section_id = 'ng_section_supabase' ): void {
        register_setting( self::OPTION_GROUP, $option_name, [
            'sanitize_callback' => $this->sanitize_callback_for( $input_type ),
            'default'           => '',
        ] );
        add_settings_field( $option_name, esc_html( $label ), function () use ( $option_name, $input_type ) {
            $this->render_input( $option_name, $input_type );
        }, self::PAGE_SLUG, $section_id );
    }

    // ── Overview ──────────────────────────────────────────────────────────────

    public function render_overview(): void {
        $leads   = count( $this->sb_get( 'leads',  [ 'select' => 'id', 'limit' => '1000' ] ) );
        $orders  = count( $this->sb_get( 'orders', [ 'select' => 'id', 'limit' => '1000' ] ) );
        $variants = count( $this->sb_get( 'variants', [ 'select' => 'id', 'is_available_nepal' => 'eq.true', 'limit' => '1000' ] ) );
        ?>
        <div class="wrap ng-admin">
            <h1>NepaliGarage Dashboard</h1>
            <div class="ng-admin-stats">
                <div class="ng-admin-stat-card"><div class="ng-admin-stat-val"><?php echo (int) $leads; ?></div><div class="ng-admin-stat-lbl">Total Leads</div></div>
                <div class="ng-admin-stat-card"><div class="ng-admin-stat-val"><?php echo (int) $orders; ?></div><div class="ng-admin-stat-lbl">Orders</div></div>
                <div class="ng-admin-stat-card"><div class="ng-admin-stat-val"><?php echo (int) $variants; ?></div><div class="ng-admin-stat-lbl">Live Variants</div></div>
            </div>
            <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=ng-leads' ) ); ?>" class="button button-primary">View Leads</a>
               <a href="<?php echo esc_url( admin_url( 'admin.php?page=ng-orders' ) ); ?>" class="button">View Orders</a></p>
        </div>
        <?php
    }

    // ── Leads ─────────────────────────────────────────────────────────────────

    public function render_leads(): void {
        if ( ! current_user_can( 'ng_view_leads' ) ) { wp_die( 'Access denied.' ); }

        // Handle status update
        if ( isset( $_POST['ng_lead_status_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ng_lead_status_nonce'] ), 'ng_lead_status' ) ) {
            $lead_id = sanitize_text_field( $_POST['lead_id'] ?? '' );
            $status  = sanitize_text_field( $_POST['status'] ?? 'new' );
            if ( $lead_id ) {
                $this->sb_patch( 'leads', [ 'id' => 'eq.' . $lead_id ], [ 'status' => $status ] );
            }
        }

        $filter_type = isset( $_GET['lead_type'] ) ? sanitize_text_field( $_GET['lead_type'] ) : '';
        $params = [
            'select' => 'id,created_at,name,phone,email,lead_type,status,message,variants(name,models(name,brands(name)))',
            'order'  => 'created_at.desc',
            'limit'  => '200',
        ];
        if ( $filter_type ) {
            $params['lead_type'] = 'eq.' . $filter_type;
        }

        $leads = $this->sb_get( 'leads', $params );
        ?>
        <div class="wrap ng-admin">
            <h1>Leads Manager</h1>

            <div class="ng-admin-filters">
                <form method="get" action="">
                    <input type="hidden" name="page" value="ng-leads">
                    <select name="lead_type" onchange="this.form.submit()">
                        <option value="">All Lead Types</option>
                        <option value="test_drive" <?php selected( $filter_type, 'test_drive' ); ?>>Test Drive</option>
                        <option value="quote_request" <?php selected( $filter_type, 'quote_request' ); ?>>Quote Request</option>
                        <option value="general_inquiry" <?php selected( $filter_type, 'general_inquiry' ); ?>>General Inquiry</option>
                    </select>
                </form>
                <span class="ng-admin-count"><?php echo count( $leads ); ?> leads</span>
            </div>

            <table class="wp-list-table widefat striped ng-admin-table">
                <thead><tr>
                    <th>Date</th><th>Name</th><th>Phone</th><th>Email</th>
                    <th>Type</th><th>Vehicle</th><th>Location</th><th>Status</th><th>Action</th>
                </tr></thead>
                <tbody>
                <?php if ( empty( $leads ) ) : ?>
                    <tr><td colspan="9" style="text-align:center;padding:20px">No leads yet.</td></tr>
                <?php else : ?>
                    <?php foreach ( $leads as $lead ) :
                        $model_name  = $lead['variants']['models']['name'] ?? '—';
                        $brand_name  = $lead['variants']['models']['brands']['name'] ?? '';
                        $vehicle     = $brand_name ? $brand_name . ' ' . $model_name : ( $lead['variants']['name'] ?? '—' );
                        $status      = $lead['status'] ?? 'new';
                        $type_label  = str_replace( '_', ' ', ucwords( $lead['lead_type'] ?? '', '_' ) );
                        $created     = date( 'j M Y, g:ia', strtotime( $lead['created_at'] ?? '' ) );
                    ?>
                    <tr>
                        <td><?php echo esc_html( $created ); ?></td>
                        <td><?php echo esc_html( $lead['name'] ?? '—' ); ?></td>
                        <td><strong><?php echo esc_html( $lead['phone'] ?? '—' ); ?></strong></td>
                        <td><?php echo esc_html( $lead['email'] ?? '—' ); ?></td>
                        <td><span class="ng-admin-badge ng-admin-badge--<?php echo esc_attr( $lead['lead_type'] ?? '' ); ?>"><?php echo esc_html( $type_label ); ?></span></td>
                        <td><?php echo esc_html( $vehicle ); ?></td>
                        <td><?php echo esc_html( $lead['message'] ?? '—' ); ?></td>
                        <td><span class="ng-admin-status ng-admin-status--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $status ); ?></span></td>
                        <td>
                            <form method="post" style="display:inline">
                                <?php wp_nonce_field( 'ng_lead_status', 'ng_lead_status_nonce' ); ?>
                                <input type="hidden" name="lead_id" value="<?php echo esc_attr( $lead['id'] ?? '' ); ?>">
                                <select name="status">
                                    <?php foreach ( [ 'new', 'contacted', 'follow_up', 'converted', 'closed' ] as $s ) : ?>
                                    <option value="<?php echo esc_attr($s); ?>" <?php selected( $status, $s ); ?>><?php echo esc_html( ucfirst( str_replace('_',' ',$s) ) ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="button button-small">Save</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    // ── Orders ────────────────────────────────────────────────────────────────

    public function render_orders(): void {
        if ( ! current_user_can( 'ng_view_orders' ) ) { wp_die( 'Access denied.' ); }

        if ( isset( $_POST['ng_order_status_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ng_order_status_nonce'] ), 'ng_order_status' ) ) {
            $order_id = sanitize_text_field( $_POST['order_id'] ?? '' );
            $status   = sanitize_text_field( $_POST['status'] ?? 'pending' );
            if ( $order_id ) {
                $this->sb_patch( 'orders', [ 'id' => 'eq.' . $order_id ], [ 'status' => $status ] );
            }
        }

        $filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
        $params = [ 'select' => 'id,created_at,order_type,item_name,quantity,total_npr,status,name,phone,email,affiliate_redirect', 'order' => 'created_at.desc', 'limit' => '200' ];
        if ( $filter ) $params['status'] = 'eq.' . $filter;

        $orders = $this->sb_get( 'orders', $params );
        ?>
        <div class="wrap ng-admin">
            <h1>Orders Manager</h1>

            <div class="ng-admin-filters">
                <form method="get" action="">
                    <input type="hidden" name="page" value="ng-orders">
                    <select name="status" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <?php foreach ( [ 'pending','confirmed','cancelled','fulfilled','redirect' ] as $s ) : ?>
                        <option value="<?php echo esc_attr($s); ?>" <?php selected($filter,$s); ?>><?php echo esc_html(ucfirst($s)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <span class="ng-admin-count"><?php echo count($orders); ?> orders</span>
            </div>

            <table class="wp-list-table widefat striped ng-admin-table">
                <thead><tr>
                    <th>Date</th><th>Customer</th><th>Phone</th><th>Item</th>
                    <th>Qty</th><th>Total (NPR)</th><th>Type</th><th>Status</th><th>Action</th>
                </tr></thead>
                <tbody>
                <?php if ( empty( $orders ) ) : ?>
                    <tr><td colspan="9" style="text-align:center;padding:20px">No orders yet.</td></tr>
                <?php else : ?>
                    <?php foreach ( $orders as $order ) :
                        $status  = $order['status'] ?? 'pending';
                        $created = date( 'j M Y, g:ia', strtotime( $order['created_at'] ?? '' ) );
                        $total   = $order['total_npr'] ? 'NPR ' . number_format( $order['total_npr'] ) : '—';
                    ?>
                    <tr>
                        <td><?php echo esc_html( $created ); ?></td>
                        <td><?php echo esc_html( $order['name'] ?? '—' ); ?></td>
                        <td><strong><?php echo esc_html( $order['phone'] ?? '—' ); ?></strong></td>
                        <td><?php echo esc_html( $order['item_name'] ?? '—' ); ?></td>
                        <td><?php echo esc_html( $order['quantity'] ?? 1 ); ?></td>
                        <td><?php echo esc_html( $total ); ?></td>
                        <td><?php echo $order['affiliate_redirect'] ? '<span class="ng-admin-badge ng-admin-badge--affiliate">Affiliate</span>' : esc_html( $order['order_type'] ?? '' ); ?></td>
                        <td><span class="ng-admin-status ng-admin-status--<?php echo esc_attr($status); ?>"><?php echo esc_html($status); ?></span></td>
                        <td>
                            <form method="post" style="display:inline">
                                <?php wp_nonce_field( 'ng_order_status', 'ng_order_status_nonce' ); ?>
                                <input type="hidden" name="order_id" value="<?php echo esc_attr( $order['id'] ?? '' ); ?>">
                                <select name="status">
                                    <?php foreach ( [ 'pending','confirmed','cancelled','fulfilled' ] as $s ) : ?>
                                    <option value="<?php echo esc_attr($s); ?>" <?php selected($status,$s); ?>><?php echo esc_html(ucfirst($s)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="button button-small">Save</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    // ── Vehicles ──────────────────────────────────────────────────────────────

    public function render_comparisons(): void {
        if ( ! current_user_can( 'ng_edit_vehicles' ) ) {
            wp_die( 'Access denied.' );
        }

        $notice = '';
        if ( isset( $_POST['ng_comparison_nonce'], $_POST['comparison_id'], $_POST['published'] )
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ng_comparison_nonce'] ) ), 'ng_comparison_publish' )
        ) {
            $comparison_id = sanitize_text_field( wp_unslash( $_POST['comparison_id'] ) );
            $published     = (bool) absint( wp_unslash( $_POST['published'] ) );
            $existing      = $this->sb_get( 'comparisons', [
                'select' => '*',
                'id'     => 'eq.' . $comparison_id,
                'limit'  => '1',
            ] );
            $is_ready = ! empty( $existing[0] ) && count( $this->comparison_variant_slugs( $existing[0] ) ) >= 2;
            $updated  = $published && ! $is_ready
                ? false
                : $this->sb_patch( 'comparisons', [ 'id' => 'eq.' . $comparison_id ], [ 'published' => $published ] );

            if ( $updated ) {
                $this->clear_comparison_caches();
                $notice = $published ? 'Comparison published.' : 'Comparison unpublished.';
            } elseif ( $published && ! $is_ready ) {
                $notice = 'Could not publish comparison. Add two variant slugs first.';
            } else {
                $notice = 'Could not update comparison. Check the Supabase service key and table permissions.';
            }
        }

        $rows = $this->sb_get( 'comparisons', [
            'select' => '*',
            'order'  => 'created_at.desc',
            'limit'  => '200',
        ] );
        ?>
        <div class="wrap ng-admin">
            <h1>Comparisons</h1>
            <p>Publish only comparisons with matched sourced rows. Public URLs use <code>/compare/[slug]/</code>.</p>

            <?php if ( $notice ) : ?>
                <div class="notice <?php echo false !== strpos( $notice, 'Could not' ) ? 'notice-error' : 'notice-success'; ?> is-dismissible">
                    <p><?php echo esc_html( $notice ); ?></p>
                </div>
            <?php endif; ?>

            <table class="wp-list-table widefat striped ng-admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th>Public URL</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $rows ) ) : ?>
                        <tr><td colspan="5" style="text-align:center;padding:20px">No comparisons found. Check service key.</td></tr>
                    <?php else : ?>
                        <?php foreach ( $rows as $row ) :
                            $is_published = filter_var( $row['published'] ?? false, FILTER_VALIDATE_BOOLEAN );
                            $variant_slugs = $this->comparison_variant_slugs( $row );
                            $is_ready      = count( $variant_slugs ) >= 2;
                            $public_url   = home_url( '/compare/' . sanitize_title( $row['slug'] ?? '' ) . '/' );
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html( $row['title'] ?? 'Untitled comparison' ); ?></strong>
                                    <?php if ( ! empty( $row['meta_description'] ) ) : ?>
                                        <p class="description"><?php echo esc_html( wp_trim_words( $row['meta_description'], 18 ) ); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td><code><?php echo esc_html( $row['slug'] ?? '' ); ?></code></td>
                                <td>
                                    <span class="ng-admin-badge ng-admin-badge--<?php echo $is_published ? 'published' : 'draft'; ?>">
                                        <?php echo $is_published ? 'published' : 'draft'; ?>
                                    </span>
                                    <?php if ( ! $is_ready ) : ?>
                                        <p class="description">Needs two variant slugs before publishing.</p>
                                    <?php endif; ?>
                                </td>
                                <td><a href="<?php echo esc_url( $public_url ); ?>" target="_blank" rel="noopener">View</a></td>
                                <td>
                                    <form method="post">
                                        <?php wp_nonce_field( 'ng_comparison_publish', 'ng_comparison_nonce' ); ?>
                                        <input type="hidden" name="comparison_id" value="<?php echo esc_attr( $row['id'] ?? '' ); ?>">
                                        <input type="hidden" name="published" value="<?php echo $is_published ? '0' : '1'; ?>">
                                        <button type="submit" class="button button-small <?php echo $is_published ? '' : 'button-primary'; ?>" <?php disabled( ! $is_published && ! $is_ready ); ?>>
                                            <?php echo $is_published ? 'Unpublish' : 'Publish'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_vehicles(): void {
        if ( ! current_user_can( 'ng_edit_vehicles' ) ) { wp_die( 'Access denied.' ); }

        $rows = $this->sb_get( 'variants', [
            'select'             => 'id,name,slug,starting_price_npr,is_featured,is_available_nepal,year_from,models(name,slug,body_type,brands(name,slug))',
            'is_available_nepal' => 'eq.true',
            'order'              => 'models(brands(name)).asc,models(name).asc,starting_price_npr.asc',
            'limit'              => '500',
        ] );
        ?>
        <div class="wrap ng-admin">
            <h1>Vehicle Manager</h1>
            <p><a href="#ng-add-variant-form" class="button button-primary">+ Add Variant</a></p>

            <table class="wp-list-table widefat striped ng-admin-table">
                <thead><tr>
                    <th>Brand</th><th>Model</th><th>Variant</th><th>Body Type</th><th>Price (NPR)</th><th>Year</th><th>Featured</th>
                </tr></thead>
                <tbody>
                <?php if ( empty( $rows ) ) : ?>
                    <tr><td colspan="7" style="text-align:center;padding:20px">No variants found. Check service key.</td></tr>
                <?php else : ?>
                    <?php foreach ( $rows as $v ) : ?>
                    <tr>
                        <td><?php echo esc_html( $v['models']['brands']['name'] ?? '—' ); ?></td>
                        <td><?php echo esc_html( $v['models']['name'] ?? '—' ); ?></td>
                        <td><?php echo esc_html( $v['name'] ?? '—' ); ?></td>
                        <td><?php echo esc_html( $v['models']['body_type'] ?? '—' ); ?></td>
                        <td><?php echo $v['starting_price_npr'] ? 'NPR ' . esc_html( number_format( $v['starting_price_npr'] ) ) : '—'; ?></td>
                        <td><?php echo esc_html( $v['year_from'] ?? '—' ); ?></td>
                        <td><?php echo $v['is_featured'] ? '⭐' : ''; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    // ── Accessories ───────────────────────────────────────────────────────────

    public function render_accessories(): void {
        if ( ! current_user_can( 'ng_manage_accessories' ) ) { wp_die( 'Access denied.' ); }

        $rows = $this->sb_get( 'accessories', [
            'select' => 'id,name,slug,category,price_npr,is_available,affiliate_url',
            'order'  => 'created_at.desc',
            'limit'  => '200',
        ] );
        ?>
        <div class="wrap ng-admin">
            <h1>Accessories Manager</h1>
            <p><span class="ng-admin-count"><?php echo count($rows); ?> accessories</span></p>

            <table class="wp-list-table widefat striped ng-admin-table">
                <thead><tr>
                    <th>Name</th><th>Category</th><th>Price (NPR)</th><th>Affiliate?</th><th>Available</th>
                </tr></thead>
                <tbody>
                <?php if ( empty( $rows ) ) : ?>
                    <tr><td colspan="5" style="text-align:center;padding:20px">No accessories yet.</td></tr>
                <?php else : ?>
                    <?php foreach ( $rows as $a ) : ?>
                    <tr>
                        <td><strong><?php echo esc_html( $a['name'] ?? '—' ); ?></strong></td>
                        <td><?php echo esc_html( str_replace('_',' ', $a['category'] ?? '') ); ?></td>
                        <td><?php echo $a['price_npr'] ? 'NPR ' . esc_html( number_format( $a['price_npr'] ) ) : '—'; ?></td>
                        <td><?php echo ! empty( $a['affiliate_url'] ) ? '✓' : ''; ?></td>
                        <td><?php echo $a['is_available'] ? '<span style="color:#16A34A">●</span>' : '<span style="color:#DC2626">●</span>'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    // ── Settings page ─────────────────────────────────────────────────────────

    public function render_settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;
        ?>
        <div class="wrap ng-admin">
            <h1>NepaliGarage Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( self::OPTION_GROUP );
                do_settings_sections( self::PAGE_SLUG );
                submit_button( 'Save Settings' );
                ?>
            </form>
        </div>
        <?php
    }

    public function render_pricing_section_intro(): void {
        echo '<p>These values drive the Nepal on-road price estimator. Keep them current.</p>';
    }

    // ── Render helpers ────────────────────────────────────────────────────────

    private function render_input( string $option_name, string $input_type ): void {
        $value = get_option( $option_name, '' );

        if ( in_array( $option_name, [ 'ng_supabase_key', 'ng_supabase_service_key' ], true ) && ! empty( $value ) ) {
            echo '<input type="password" id="' . esc_attr( $option_name ) . '" name="' . esc_attr( $option_name ) . '"'
                . ' value="' . esc_attr( $value ) . '" class="regular-text" autocomplete="new-password">';
            $hint = $option_name === 'ng_supabase_service_key'
                ? 'Service key — admin CRUD only. NEVER expose to browser.'
                : 'Anon key — safe for browser auth only.';
            echo '<p class="description">' . esc_html( $hint ) . '</p>';
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

    private function sanitize_callback_for( string $input_type ): callable {
        if ( 'url' === $input_type ) {
            return 'esc_url_raw';
        }
        if ( 'number' === $input_type ) {
            return function ( $v ) { return (string) floatval( $v ); };
        }
        if ( 'password' === $input_type ) {
            return 'sanitize_text_field';
        }
        return 'sanitize_text_field';
    }
}
