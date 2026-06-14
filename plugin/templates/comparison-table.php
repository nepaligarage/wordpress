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

$hero_key_groups = [
    [
        'field' => 'price_usd',
        'label' => __( 'Price (USD)', 'nepaligarage' ),
    ],
    [
        'field' => 'nepal_price_est',
        'label' => __( 'Nepal Price', 'nepaligarage' ),
    ],
    [
        'field' => 'battery_kwh',
        'fallback' => 'battery_capacity_kwh',
        'label' => __( 'Battery', 'nepaligarage' ),
    ],
    [
        'field' => 'range_km',
        'label' => __( 'Range', 'nepaligarage' ),
    ],
    [
        'field' => 'engine_cc',
        'label' => __( 'Engine', 'nepaligarage' ),
    ],
    [
        'field' => 'seating_capacity',
        'label' => __( 'Seats', 'nepaligarage' ),
    ],
];

$hero_for = static function ( array $spec_index, array $group ) use ( $renderer ): string {
    if ( ! empty( $group['field'] ) && isset( $spec_index[ $group['field'] ] ) ) {
        return $renderer->hero_value( $spec_index, $group['field'] );
    }

    if ( ! empty( $group['fallback'] ) && isset( $spec_index[ $group['fallback'] ] ) ) {
        return $renderer->hero_value( $spec_index, $group['fallback'] );
    }

    return esc_html__( 'N/A', 'nepaligarage' );
};

$verdict_a = $a['verdict'] ?? '';
$verdict_b = $b['verdict'] ?? '';
$summary   = $a['comparison_summary'] ?? '';

$nepal_keys = [ 'nepal_price_est', 'nepal_charger_availability', 'nepal_service_center' ];
$has_nepal  = false;
foreach ( $nepal_keys as $nk ) {
    if ( isset( $specs_a[ $nk ] ) || isset( $specs_b[ $nk ] ) ) {
        $has_nepal = true;
        break;
    }
}
?>

<div class="ng-comparison-shell" data-ng-comparison>
    <section class="ng-comparison-spotlight">
        <div class="ng-comparison-spotlight__versus">
            <article class="ng-comparison-vehicle-card ng-comparison-vehicle-card--a">
                <span class="ng-comparison-vehicle-card__label"><?php esc_html_e( 'Vehicle A', 'nepaligarage' ); ?></span>
                <h2 class="ng-comparison-vehicle-card__title"><?php echo $name_a; ?></h2>
                <div class="ng-comparison-hero-specs">
                    <?php foreach ( $hero_key_groups as $group ) : ?>
                    <div class="ng-comparison-hero-spec">
                        <span class="ng-comparison-hero-spec__label"><?php echo esc_html( $group['label'] ); ?></span>
                        <span class="ng-comparison-hero-spec__value"><?php echo $hero_for( $specs_a, $group ); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </article>

            <div class="ng-comparison-spotlight__divider" aria-hidden="true">VS</div>

            <article class="ng-comparison-vehicle-card ng-comparison-vehicle-card--b">
                <span class="ng-comparison-vehicle-card__label"><?php esc_html_e( 'Vehicle B', 'nepaligarage' ); ?></span>
                <h2 class="ng-comparison-vehicle-card__title"><?php echo $name_b; ?></h2>
                <div class="ng-comparison-hero-specs">
                    <?php foreach ( $hero_key_groups as $group ) : ?>
                    <div class="ng-comparison-hero-spec">
                        <span class="ng-comparison-hero-spec__label"><?php echo esc_html( $group['label'] ); ?></span>
                        <span class="ng-comparison-hero-spec__value"><?php echo $hero_for( $specs_b, $group ); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </div>

        <?php if ( $summary || $verdict_a || $verdict_b ) : ?>
        <div class="ng-comparison-summary-box">
            <div class="ng-comparison-summary-box__head">
                <span class="ng-comparison-summary-box__eyebrow"><?php esc_html_e( 'Quick read', 'nepaligarage' ); ?></span>
                <h3 class="ng-comparison-summary-box__title"><?php esc_html_e( 'TL;DR Verdict', 'nepaligarage' ); ?></h3>
            </div>

            <?php if ( $summary ) : ?>
                <p class="ng-comparison-summary-box__summary"><?php echo wp_kses_post( $summary ); ?></p>
            <?php endif; ?>

            <?php if ( $verdict_a || $verdict_b ) : ?>
            <div class="ng-comparison-summary-box__grid">
                <?php if ( $verdict_a ) : ?>
                <article class="ng-comparison-summary-box__verdict">
                    <strong><?php echo $name_a; ?></strong>
                    <p><?php echo wp_kses_post( $verdict_a ); ?></p>
                </article>
                <?php endif; ?>

                <?php if ( $verdict_b ) : ?>
                <article class="ng-comparison-summary-box__verdict">
                    <strong><?php echo $name_b; ?></strong>
                    <p><?php echo wp_kses_post( $verdict_b ); ?></p>
                </article>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <p class="ng-comparison-summary-box__disclaimer"><?php esc_html_e( 'Confidence badges indicate how trustworthy a value is: official = manufacturer confirmed, estimated = calculated, unverified = community reported.', 'nepaligarage' ); ?></p>
        </div>
        <?php endif; ?>
    </section>

    <section class="ng-comparison-workbench">
        <?php if ( ! empty( $categories ) ) : ?>
        <nav class="ng-comparison-tab-nav" aria-label="<?php esc_attr_e( 'Comparison sections', 'nepaligarage' ); ?>">
            <?php $first = true; foreach ( $categories as $cat_key => $cat ) : ?>
            <button
                class="ng-comparison-tab-btn<?php echo $first ? ' is-active' : ''; ?>"
                data-tab="<?php echo esc_attr( $cat_key ); ?>"
                type="button"
            >
                <?php echo esc_html( $cat['label'] ); ?>
            </button>
            <?php $first = false; endforeach; ?>
        </nav>
        <?php endif; ?>

        <?php $first = true; foreach ( $categories as $cat_key => $cat ) : ?>
        <section
            class="ng-comparison-panel<?php echo $first ? ' is-active' : ''; ?>"
            id="ng-tab-<?php echo esc_attr( $cat_key ); ?>"
            data-tab-panel="<?php echo esc_attr( $cat_key ); ?>"
        >
            <div class="ng-comparison-panel__head">
                <h3><?php echo esc_html( $cat['label'] ); ?></h3>
                <p><?php esc_html_e( 'Scan the row labels first, then compare where each vehicle clearly gains or gives up value.', 'nepaligarage' ); ?></p>
            </div>

            <div class="ng-comparison-table-wrap">
                <table class="ng-comparison-table">
                    <thead>
                        <tr>
                            <th class="ng-table-col-label" scope="col"><?php esc_html_e( 'Specification', 'nepaligarage' ); ?></th>
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
                            <td class="ng-spec-cell ng-spec-cell--a"><?php echo $renderer->render_cell( $specs_a, $field['key'] ); ?></td>
                            <td class="ng-spec-cell ng-spec-cell--b"><?php echo $renderer->render_cell( $specs_b, $field['key'] ); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php $first = false; endforeach; ?>
    </section>

    <?php if ( $has_nepal ) : ?>
    <section class="ng-comparison-nepal-context">
        <div class="ng-comparison-panel__head">
            <h3><span aria-hidden="true">🇳🇵</span> <?php esc_html_e( 'Nepal Context', 'nepaligarage' ); ?></h3>
            <p><?php esc_html_e( 'These rows highlight buying and ownership context that matters specifically in Nepal.', 'nepaligarage' ); ?></p>
        </div>

        <div class="ng-comparison-table-wrap">
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
                            <td class="ng-spec-cell ng-spec-cell--a"><?php echo $renderer->render_cell( $specs_a, $nk ); ?></td>
                            <td class="ng-spec-cell ng-spec-cell--b"><?php echo $renderer->render_cell( $specs_b, $nk ); ?></td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <section class="ng-comparison-buyer-fit">
        <div class="ng-comparison-panel__head">
            <h3><?php esc_html_e( 'How to decide between them', 'nepaligarage' ); ?></h3>
            <p><?php esc_html_e( 'Use pricing, buyer-fit, and the strongest category differences together. The better vehicle is the one that asks fewer compromises for your use case.', 'nepaligarage' ); ?></p>
        </div>

        <div class="ng-comparison-buyer-fit__grid">
            <article class="ng-comparison-buyer-fit__card">
                <strong><?php esc_html_e( 'Choose the cheaper one when', 'nepaligarage' ); ?></strong>
                <p><?php esc_html_e( 'Its compromises do not affect your daily use, and the price gap feels more important than the extra features or space on offer.', 'nepaligarage' ); ?></p>
            </article>
            <article class="ng-comparison-buyer-fit__card">
                <strong><?php esc_html_e( 'Choose the pricier one when', 'nepaligarage' ); ?></strong>
                <p><?php esc_html_e( 'The added practicality, powertrain, comfort, or ownership confidence will matter to you every week, not just on paper.', 'nepaligarage' ); ?></p>
            </article>
        </div>
    </section>
</div>
