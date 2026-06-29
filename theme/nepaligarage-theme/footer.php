</div><!-- #page -->

<footer class="ng-footer">
    <div class="ng-container">

        <div class="ng-footer__grid">

            <div class="ng-footer__col ng-footer__col--brand">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="ng-logo ng-logo--light">
                    <span class="ng-logo__mark">NG</span>
                    <span class="ng-logo__text">Nepali<strong>Garage</strong></span>
                </a>
                <p class="ng-footer__tagline">Nepal's smartest vehicle ownership platform. Compare, track, and decide with confidence.</p>
                <p class="ng-footer__tagline ng-footer__tagline--np">नेपालको सबैभन्दा स्मार्ट सवारी साधन प्लेटफर्म।</p>
            </div>

            <div class="ng-footer__col">
                <h4>Research</h4>
                <ul>
                    <li><a href="<?php echo esc_url( home_url( '/compare/' ) ); ?>">Compare Cars</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/new-cars/' ) ); ?>">New Cars Nepal</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/electric-vehicles/' ) ); ?>">Electric Vehicles</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/nepal-car-price-estimator/' ) ); ?>">Price Estimator</a></li>
                </ul>
            </div>

            <div class="ng-footer__col">
                <h4>Own &amp; Manage</h4>
                <ul>
                    <li><a href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>">My Garage</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/dashboard/#documents' ) ); ?>">Vehicle Documents</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/news/' ) ); ?>">News &amp; Reviews</a></li>
                </ul>
            </div>

            <div class="ng-footer__col">
                <h4>Company</h4>
                <ul>
                    <li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About Us</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contact</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">Privacy Policy</a></li>
                    <li><a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>">Terms of Use</a></li>
                </ul>
                <div class="ng-footer__contact">
                    <p><svg width="14" height="11" viewBox="0 0 14 11" fill="none" aria-hidden="true" style="display:inline-block;vertical-align:middle;margin-right:5px;opacity:.6"><rect x="0" y="0" width="14" height="11" rx="2" stroke="currentColor" stroke-width="1.2" fill="none"/><path d="M0 2l7 5 7-5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg><a href="mailto:hello@nepaligarage.com">hello@nepaligarage.com</a></p>
                </div>
            </div>

        </div>

        <div class="ng-footer__bottom">
            <p>&copy; <?php echo esc_html( date( 'Y' ) ); ?> NepaliGarage. Vehicle data is verified as sources become available.</p>
            <p class="ng-footer__disclaimer">Prices shown are estimates. Verify with authorized dealers before purchase.</p>
        </div>

    </div>
</footer>

<?php // ── Cookie consent bar (shown once, dismissed permanently via localStorage) ?>
<div id="ng-cookie-bar" class="ng-cookie-bar" role="region" aria-label="Cookie notice" hidden>
    <p class="ng-cookie-bar__text">
        We use cookies to improve your experience and analyse traffic.
        By clicking <strong>Accept</strong> you agree to our use of cookies.
        <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">Privacy Policy</a>
    </p>
    <div class="ng-cookie-bar__actions">
        <button id="ng-cookie-accept" class="ng-btn ng-btn--primary ng-btn--sm">Accept</button>
        <button id="ng-cookie-decline" class="ng-btn ng-btn--outline-white ng-btn--sm">Decline</button>
    </div>
</div>

<?php wp_footer(); ?>
</body>
</html>
