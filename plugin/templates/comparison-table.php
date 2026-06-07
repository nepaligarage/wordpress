<?php
/**
 * Comparison table template.
 *
 * Variables injected by NG_Comparison_Renderer::render():
 *   $a           – variant A raw data
 *   $b           – variant B raw data
 *   $specs_a     – indexed specs for variant A  (field_key => spec row)
 *   $specs_b     – indexed specs for variant B  (field_key => spec row)
 *   $categories  – [ category_key => [ label, fields[] ] ]
 *   $renderer    – NG_Comparison_Renderer instance
 *
 * @package NepaliGarage
 */

defined( 'ABSPATH' ) || exit;

$name_a = $renderer->variant_name( $a );
$name_b = $renderer->variant_name( $b );

// Hero spec keys — shown in the header banner.
$hero_keys = [ 'price_usd', 'battery_kwh', 'range_km' ];
$hero_labels = [
    'price_usd'   => __( 'Price (USD)', 'nepaligarage' ),
    'battery_kwh' => __( 'Battery', 'nepaligarage' ),
    'range_km'    => __( 'Range', 'nepaligarage' ),
];

// Nepal context field keys — shown in the highlighted section.
$nepal_keys = [ 'nepal_price_est', 'nepal_charger_availability', 'nepal_service_center' ];
?>

<div class="ng-comparison-wrap" data-ng-comparison>

    <!-- ================================================================ -->
    <!-- HEADER                                                            -->
    <!-- ================================================================ -->
    <div class="ng-comparison-header">
        <div class="ng-comparison-header__col ng-comparison-header__col--label"></div>

        <div class="ng-comparison-header__col ng-comparison-header__col--a">
            <h2 class="ng-car-name"><?php echo $name_a; ?></h2>
            <div class="ng-hero-specs">
                <?php foreach ( $hero_keys as $hk ) : ?>
                    <div class="ng-hero-spec">
                        <span class="ng-hero-spec__label"><?php echo esc_html( $hero_labels[ $hk ] ?? $hk ); ?></span>
                        <span class="ng-hero-spec__value"><?php echo $renderer->hero_value( $specs_a, $hk ); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="ng-comparison-header__col ng-comparison-header__col--b">
            <h2 class="ng-car-name"><?php echo $name_b; ?></h2>
            <div class="ng-hero-specs">
                <?php foreach ( $hero_keys as $hk ) : ?>
                    <div class="ng-hero-spec">
                        <span class="ng-hero-spec__label"><?php echo esc_html( $hero_labels[ $hk ] ?? $hk ); ?></span>
                        <span class="ng-hero-spec__value"><?php echo $renderer->hero_value( $specs_b, $hk ); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div><!-- /.ng-comparison-header -->

    <!-- ================================================================ -->
    <!-- TAB NAV                                                           -->
    <!-- ================================================================ -->
    <?php if ( ! empty( $categories ) ) : ?>
    <nav class="ng-tab-nav" aria-label="<?php esc_attr_e( 'Spec categories', 'nepaligarage' ); ?>">
        <?php $first = true; foreach ( $categories as $cat_key => $cat ) : ?>
            <button
                class="ng-tab-btn<?php echo $first ? ' ng-tab-btn--active' : ''; ?>"
                data-tab="<?php echo esc_attr( $cat_key ); ?>"
                type="button"
            >
                <?php echo esc_html( $cat['label'] ); ?>
            </button>
        <?php $first = false; endforeach; ?>
    </nav>
    <?php endif; ?>

    <!-- ================================================================ -->
    <!-- SPEC TABLES — one per category                                   -->
    <!-- ================================================================ -->
    <?php $first = true; foreach ( $categories as $cat_key => $cat ) : ?>
        <div
            class="ng-tab-panel<?php echo $first ? ' ng-tab-panel--active' : ''; ?>"
            id="ng-tab-<?php echo esc_attr( $cat_key ); ?>"
            data-tab-panel="<?php echo esc_attr( $cat_key ); ?>"
        >
            <table class="ng-comparison-table">
                <thead>
                    <tr>
                        <th class="ng-table-col-label" scope="col">
                            <?php esc_html_e( 'Specification', 'nepaligarage' ); ?>
                        </th>
                        <th class="ng-table-col-a" scope="col"><?php echo $name_a; ?></th>
                        <th class="ng-table-col-b" scope="col"><?php echo $name_b; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $cat['fields'] as $field ) : ?>
                        <tr class="ng-spec-row">
                            <td class="ng-spec-label">
                                <?php echo esc_html( $field['label'] ); ?>
                                <?php if ( ! empty( $field['unit'] ) ) : ?>
                                    <span class="ng-unit">(<?php echo esc_html( $field['unit'] ); ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td class="ng-spec-cell ng-spec-cell--a">
                                <?php echo $renderer->render_cell( $specs_a, $field['key'] ); ?>
                            </td>
                            <td class="ng-spec-cell ng-spec-cell--b">
                                <?php echo $renderer->render_cell( $specs_b, $field['key'] ); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php $first = false; endforeach; ?>

    <!-- ================================================================ -->
    <!-- NEPAL CONTEXT SECTION                                            -->
    <!-- ================================================================ -->
    <?php
    $has_nepal = false;
    foreach ( $nepal_keys as $nk ) {
        if ( isset( $specs_a[ $nk ] ) || isset( $specs_b[ $nk ] ) ) {
            $has_nepal = true;
            break;
        }
    }
    ?>
    <?php if ( $has_nepal ) : ?>
    <div class="ng-nepal-context">
        <h3 class="ng-nepal-context__title">
            <span class="ng-nepal-flag" aria-hidden="true">&#x1F1F3;&#x1F1F5;</span>
            <?php esc_html_e( 'Nepal Context', 'nepaligarage' ); ?>
        </h3>
        <table class="ng-comparison-table ng-comparison-table--nepal">
            <thead>
                <tr>
                    <th class="ng-table-col-label" scope="col"><?php esc_html_e( 'Factor', 'nepaligarage' ); ?></th>
                    <th class="ng-table-col-a" scope="col"><?php echo $name_a; ?></th>
                    <th class="ng-table-col-b" scope="col"><?php echo $name_b; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $nepal_keys as $nk ) : ?>
                    <?php if ( isset( $specs_a[ $nk ] ) || isset( $specs_b[ $nk ] ) ) : ?>
                    <tr class="ng-spec-row">
                        <td class="ng-spec-label">
                            <?php
                            $field_label = $specs_a[ $nk ]['spec_field']['label']
                                        ?? $specs_b[ $nk ]['spec_field']['label']
                                        ?? $nk;
                            echo esc_html( $field_label );
                            ?>
                        </td>
                        <td class="ng-spec-cell ng-spec-cell--a">
                            <?php echo $renderer->render_cell( $specs_a, $nk ); ?>
                        </td>
                        <td class="ng-spec-cell ng-spec-cell--b">
                            <?php echo $renderer->render_cell( $specs_b, $nk ); ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- ================================================================ -->
    <!-- TL;DR VERDICT BOX                                                -->
    <!-- ================================================================ -->
    <?php
    $verdict_a = $a['verdict'] ?? '';
    $verdict_b = $b['verdict'] ?? '';
    $summary   = $a['comparison_summary'] ?? '';
    ?>
    <?php if ( $verdict_a || $verdict_b || $summary ) : ?>
    <div class="ng-tldr-box">
        <h3 class="ng-tldr-box__title"><?php esc_html_e( 'TL;DR Verdict', 'nepaligarage' ); ?></h3>
        <?php if ( $summary ) : ?>
            <p class="ng-tldr-box__summary"><?php echo wp_kses_post( $summary ); ?></p>
        <?php endif; ?>
        <?php if ( $verdict_a || $verdict_b ) : ?>
        <div class="ng-tldr-box__verdicts">
            <?php if ( $verdict_a ) : ?>
            <div class="ng-tldr-verdict ng-tldr-verdict--a">
                <strong><?php echo $name_a; ?></strong>
                <p><?php echo wp_kses_post( $verdict_a ); ?></p>
            </div>
            <?php endif; ?>
            <?php if ( $verdict_b ) : ?>
            <div class="ng-tldr-verdict ng-tldr-verdict--b">
                <strong><?php echo $name_b; ?></strong>
                <p><?php echo wp_kses_post( $verdict_b ); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <p class="ng-tldr-box__disclaimer">
            <?php esc_html_e( 'Data accuracy: official = manufacturer confirmed, estimated = calculated, unverified = community reported.', 'nepaligarage' ); ?>
        </p>
    </div>
    <?php endif; ?>

</div><!-- /.ng-comparison-wrap -->
