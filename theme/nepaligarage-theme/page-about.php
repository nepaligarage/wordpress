<?php
/**
 * About page with trust and disclosure copy.
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
                <span class="ng-section__eyebrow">About NepaliGarage</span>
                <h1>Nepal-first vehicle research, built on sourced data.</h1>
                <p>NepaliGarage helps buyers compare vehicles, estimate Nepal ownership costs, and keep vehicle decisions grounded in traceable information.</p>
            </div>

            <div class="ng-content">
                <h2>What we publish</h2>
                <p>We prioritize Nepal-relevant cars and electric vehicles, then expand specs and comparisons as reliable sources become available. Data can be official, estimated, or unverified, and high-impact numbers should show that status clearly.</p>

                <h2>Dealer and lead disclosure</h2>
                <p>NepaliGarage may earn referral revenue when a visitor submits an enquiry that is passed to a dealer or partner. Paid relationships must not change published specs, comparison verdicts, or source labels.</p>

                <h2>Corrections</h2>
                <p>If you represent a manufacturer, importer, dealer, or owner group and notice incorrect data, send the official brochure, price sheet, or source URL so the record can be corrected with attribution.</p>
            </div>
        </div>
    </section>
</main>

<?php
get_footer();

