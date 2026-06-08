<?php
/**
 * Content Automation admin page for NepaliGarage.
 * Manages tracked brands and logs for the daily Claude Code article pipeline.
 *
 * @package NepaliGarage
 */

defined( 'ABSPATH' ) || exit;

class NG_Content_Automator {

    private const BRANDS_OPTION  = 'ng_tracked_brands';
    private const RUNS_OPTION    = 'ng_last_automation_runs';
    private const CATEGORY_NAME  = 'Brand News';
    private const CATEGORY_SLUG  = 'brand-news';

    public function __construct() {
        add_action( 'admin_menu',  [ $this, 'add_submenu' ] );
        add_action( 'admin_init',  [ $this, 'handle_save' ] );
        add_action( 'init',        [ $this, 'ensure_category' ] );
    }

    // ── Admin sub-page ───────────────────────────────────────────────────────

    public function add_submenu(): void {
        add_submenu_page(
            'nepaligarage',
            'Content Automation',
            'Content Automation',
            'manage_options',
            'ng-content-automation',
            [ $this, 'render_page' ]
        );
    }

    public function render_page(): void {
        $brands = $this->get_brands();
        $runs   = get_option( self::RUNS_OPTION, [] );
        if ( ! is_array( $runs ) ) {
            $runs = [];
        }
        ?>
        <div class="wrap ng-admin">
            <h1>Content Automation</h1>
            <p>Claude Code searches for brand news daily and posts articles as drafts for your review.
               Enable brands below. Run the skill manually in Claude Code with <code>/ng-brand-news [BrandName]</code>.</p>

            <?php if ( isset( $_GET['saved'] ) ) : ?>
            <div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
            <?php endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field( 'ng_automation_save', '_ng_automation_nonce' ); ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width:40px">On</th>
                            <th>Brand</th>
                            <th>Search Keywords</th>
                            <th>Last Run</th>
                            <th>Drafts Published</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $brands as $i => $brand ) :
                            $run  = $runs[ $brand['slug'] ] ?? null;
                            $last = $run ? esc_html( $run['date'] . ' — ' . $run['result'] ) : '—';
                            $drafts = $this->count_drafts( $brand['name'] );
                        ?>
                        <tr>
                            <td>
                                <input type="hidden" name="brands[<?php echo $i; ?>][slug]"    value="<?php echo esc_attr( $brand['slug'] ); ?>">
                                <input type="hidden" name="brands[<?php echo $i; ?>][name]"    value="<?php echo esc_attr( $brand['name'] ); ?>">
                                <input type="hidden" name="brands[<?php echo $i; ?>][keywords]" value="<?php echo esc_attr( $brand['keywords'] ); ?>">
                                <input type="checkbox" name="brands[<?php echo $i; ?>][enabled]" value="1"
                                    <?php checked( ! empty( $brand['enabled'] ) ); ?>>
                            </td>
                            <td><strong><?php echo esc_html( $brand['name'] ); ?></strong></td>
                            <td>
                                <input type="text" name="brands[<?php echo $i; ?>][keywords]"
                                    value="<?php echo esc_attr( $brand['keywords'] ); ?>"
                                    style="width:100%">
                            </td>
                            <td><?php echo $last; ?></td>
                            <td><?php echo (int) $drafts; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary">Save Settings</button>
                </p>
            </form>

            <hr>
            <h2>How to run</h2>
            <ol>
                <li>Open Claude Code in your terminal</li>
                <li>Type <code>/ng-brand-news BYD</code> (or any brand name)</li>
                <li>Claude researches news, writes a 700-900 word article, and posts it as a draft</li>
                <li>Review drafts at <a href="<?php echo esc_url( admin_url( 'edit.php?post_status=draft' ) ); ?>">Posts → Drafts</a></li>
            </ol>
            <p>The schedule runs automatically once you set it up via CronCreate in Claude Code.</p>
        </div>
        <?php
    }

    // ── Save handler ─────────────────────────────────────────────────────────

    public function handle_save(): void {
        if ( ! isset( $_POST['_ng_automation_nonce'] ) ) {
            return;
        }
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_ng_automation_nonce'] ) ), 'ng_automation_save' ) ) {
            wp_die( 'Nonce verification failed.' );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Insufficient permissions.' );
        }

        $raw    = isset( $_POST['brands'] ) && is_array( $_POST['brands'] ) ? $_POST['brands'] : [];
        $brands = [];

        foreach ( $raw as $item ) {
            $slug = sanitize_key( $item['slug'] ?? '' );
            if ( empty( $slug ) ) {
                continue;
            }
            $brands[] = [
                'name'     => sanitize_text_field( $item['name'] ?? '' ),
                'slug'     => $slug,
                'keywords' => sanitize_text_field( $item['keywords'] ?? '' ),
                'enabled'  => ! empty( $item['enabled'] ),
            ];
        }

        update_option( self::BRANDS_OPTION, $brands );

        wp_redirect( add_query_arg( [ 'page' => 'ng-content-automation', 'saved' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function get_brands(): array {
        $stored = get_option( self::BRANDS_OPTION );

        if ( ! empty( $stored ) && is_array( $stored ) ) {
            return $stored;
        }

        // Seed defaults
        return [
            [ 'name' => 'BYD',        'slug' => 'byd',        'keywords' => 'BYD Nepal electric car',          'enabled' => true  ],
            [ 'name' => 'Toyota',     'slug' => 'toyota',     'keywords' => 'Toyota Nepal new launch',          'enabled' => false ],
            [ 'name' => 'Hyundai',    'slug' => 'hyundai',    'keywords' => 'Hyundai Nepal EV',                 'enabled' => false ],
            [ 'name' => 'Honda',      'slug' => 'honda',      'keywords' => 'Honda Nepal motorcycle car',       'enabled' => false ],
            [ 'name' => 'Yamaha',     'slug' => 'yamaha',     'keywords' => 'Yamaha Nepal motorcycle',          'enabled' => false ],
            [ 'name' => 'Land Rover', 'slug' => 'land-rover', 'keywords' => 'Land Rover Nepal Defender',        'enabled' => false ],
        ];
    }

    private function count_drafts( string $brand_name ): int {
        $query = new WP_Query( [
            'post_status'    => 'draft',
            'post_type'      => 'post',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            's'              => $brand_name,
        ] );
        return (int) $query->found_posts;
    }

    public function ensure_category(): void {
        if ( ! term_exists( self::CATEGORY_SLUG, 'category' ) ) {
            wp_insert_term( self::CATEGORY_NAME, 'category', [ 'slug' => self::CATEGORY_SLUG ] );
        }
    }
}
