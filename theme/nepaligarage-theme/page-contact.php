<?php
/**
 * Contact page.
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
                <span class="ng-section__eyebrow">Contact</span>
                <h1>Contact NepaliGarage</h1>
                <p>Send corrections, official source documents, partnership enquiries, or vehicle data updates.</p>
            </div>

            <div class="ng-content">
                <h2>Data corrections</h2>
                <p>For price, spec, or source corrections, include the model, variant, source URL, and any official brochure or price sheet.</p>

                <h2>Dealer and partner enquiries</h2>
                <p>NepaliGarage may work with dealers and partners, but paid relationships must not influence published specifications, confidence labels, or comparison verdicts.</p>
            </div>
        </div>
    </section>
</main>

<?php
get_footer();

