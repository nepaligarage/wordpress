<?php
/**
 * Price estimator landing page.
 *
 * @package NepaliGarage
 */

defined( 'ABSPATH' ) || exit;

global $wp_query;
$wp_query->is_404 = false;
status_header( 200 );

get_header();
?>

<main id="primary" class="site-main">
    <section class="ng-section">
        <div class="ng-container ng-container--narrow">
            <div class="ng-section__header">
                <span class="ng-section__eyebrow">Nepal vehicle costs</span>
                <h1>Nepal Car Price Estimator</h1>
                <p>Estimate landed and on-road costs using Nepal duty, VAT, road tax, and handling assumptions.</p>
            </div>
            <?php echo do_shortcode( '[ng_price_estimator]' ); ?>
        </div>
    </section>
</main>

<?php
get_footer();

