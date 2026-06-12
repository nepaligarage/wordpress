<?php
/**
 * Privacy policy.
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
                <span class="ng-section__eyebrow">Privacy</span>
                <h1>Privacy Policy</h1>
                <p>How NepaliGarage handles account, enquiry, and site usage data.</p>
            </div>

            <div class="ng-content">
                <h2>Information we collect</h2>
                <p>We may collect account details, enquiry form submissions, vehicle dashboard records, and basic analytics or cookie consent preferences.</p>

                <h2>How we use information</h2>
                <p>We use submitted data to provide vehicle research tools, respond to enquiries, improve the site, and connect quote or test-drive requests with relevant partners when requested.</p>

                <h2>Partner disclosure</h2>
                <p>If you submit a lead or enquiry, relevant details may be shared with a dealer or service partner so they can respond. NepaliGarage may earn referral revenue from some partner introductions.</p>

                <h2>Data corrections and removal</h2>
                <p>Contact NepaliGarage to request corrections or removal of account and enquiry data where legally and operationally possible.</p>
            </div>
        </div>
    </section>
</main>

<?php
get_footer();

