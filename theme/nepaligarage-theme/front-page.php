<?php
get_header();

// Fetch data from Supabase for dynamic homepage sections
$brands_cars  = ngt_supabase_get( 'brands', [ 'select' => 'id,name,slug,logo_url,type', 'type' => 'eq.car',  'order' => 'name.asc' ] );
$brands_bikes = ngt_supabase_get( 'brands', [ 'select' => 'id,name,slug,logo_url,type', 'type' => 'eq.bike', 'order' => 'name.asc' ] );
$brands       = array_merge( $brands_cars, $brands_bikes ); // keep $brands for fallback
$variants = ngt_supabase_get( 'variants', [
    'select'              => 'id,name,slug,model(name,body_type,brand(name,slug))',
    'is_available_nepal'  => 'eq.true',
    'limit'               => '6',
] );
$comparisons = ngt_supabase_get( 'comparisons', [
    'select'    => 'id,slug,title,created_at',
    'published' => 'eq.true',
    'order'     => 'created_at.desc',
    'limit'     => '3',
] );
?>

<!-- ── HERO ──────────────────────────────────────────────────────────────────── -->
<section class="ng-hero">
    <div class="ng-container">
        <div class="ng-hero__content">
            <h1 class="ng-hero__title">
                Nepal's Smartest<br>Vehicle Platform
            </h1>
            <p class="ng-hero__subtitle">Compare specs, estimate on-road prices, track ownership costs — all in one place. Built for Nepal's roads, Nepal's market.</p>
            <div class="ng-hero__ctas">
                <a href="<?php echo esc_url( home_url( '/compare/' ) ); ?>" class="ng-btn ng-btn--red ng-btn--lg">
                    Compare Cars
                </a>
                <a href="<?php echo esc_url( home_url( '/nepal-car-price-estimator/' ) ); ?>" class="ng-btn ng-btn--outline-white ng-btn--lg">
                    Price a Car in Nepal
                </a>
            </div>
            <div class="ng-hero__trust">
                <span>✓ Official manufacturer data</span>
                <span>✓ Nepal customs &amp; tax included</span>
                <span>✓ 122 spec fields per vehicle</span>
            </div>
        </div>
    </div>
</section>

<!-- ── QUICK TILES ────────────────────────────────────────────────────────────── -->
<section class="ng-section ng-section--tiles">
    <div class="ng-container">
        <div class="ng-tiles">

            <a href="<?php echo esc_url( home_url( '/compare/' ) ); ?>" class="ng-tile">
                <div class="ng-tile__icon">
                    <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="4" y="14" width="18" height="22" rx="3" stroke="currentColor" stroke-width="2.5"/>
                        <rect x="26" y="14" width="18" height="22" rx="3" stroke="currentColor" stroke-width="2.5"/>
                        <path d="M22 24h4" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                    </svg>
                </div>
                <span class="ng-tile__label">Compare Cars</span>
            </a>

            <a href="<?php echo esc_url( home_url( '/electric-vehicles/' ) ); ?>" class="ng-tile">
                <div class="ng-tile__icon ng-tile__icon--ev">
                    <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M26 8L14 26h12l-2 14L38 22H26L28 8z" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/>
                    </svg>
                </div>
                <span class="ng-tile__label">Electric Vehicles</span>
            </a>

            <a href="<?php echo esc_url( home_url( '/nepal-car-price-estimator/' ) ); ?>" class="ng-tile">
                <div class="ng-tile__icon">
                    <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="8" y="8" width="32" height="32" rx="4" stroke="currentColor" stroke-width="2.5"/>
                        <path d="M16 24h16M24 16v16" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                        <path d="M19 19l10 10M29 19L19 29" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity=".4"/>
                    </svg>
                </div>
                <span class="ng-tile__label">Price Estimator</span>
            </a>

            <a href="<?php echo esc_url( home_url( '/used-cars/' ) ); ?>" class="ng-tile">
                <div class="ng-tile__icon">
                    <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8 32V20l6-8h20l6 8v12" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/>
                        <circle cx="16" cy="33" r="4" stroke="currentColor" stroke-width="2.5"/>
                        <circle cx="32" cy="33" r="4" stroke="currentColor" stroke-width="2.5"/>
                        <path d="M20 33h8" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                        <path d="M14 20h20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                <span class="ng-tile__label">Used Car Guide</span>
            </a>

            <a href="<?php echo esc_url( home_url( '/garages/' ) ); ?>" class="ng-tile">
                <div class="ng-tile__icon">
                    <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8 40V20L24 10l16 10v20H8z" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/>
                        <rect x="18" y="28" width="12" height="12" rx="1" stroke="currentColor" stroke-width="2.5"/>
                        <path d="M8 20h32" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity=".4"/>
                    </svg>
                </div>
                <span class="ng-tile__label">Find a Garage</span>
            </a>

            <a href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>" class="ng-tile ng-tile--cta">
                <div class="ng-tile__icon">
                    <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="24" cy="16" r="8" stroke="currentColor" stroke-width="2.5"/>
                        <path d="M8 40c0-8.837 7.163-16 16-16s16 7.163 16 16" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                    </svg>
                </div>
                <span class="ng-tile__label">My Garage</span>
            </a>

        </div>
    </div>
</section>

<!-- ── FEATURED VEHICLES ──────────────────────────────────────────────────────── -->
<?php if ( ! empty( $variants ) ) : ?>
<section class="ng-section">
    <div class="ng-container">
        <div class="ng-section__header">
            <h2>Vehicles Available in Nepal</h2>
            <a href="<?php echo esc_url( home_url( '/compare/' ) ); ?>" class="ng-link--more">See all →</a>
        </div>
        <div class="ng-cards ng-cards--scroll">
            <?php foreach ( $variants as $v ) :
                $model_name = $v['model']['name'] ?? '';
                $brand_name = $v['model']['brand']['name'] ?? '';
                $body_type  = $v['model']['body_type'] ?? '';
                $slug       = $v['slug'] ?? '';
            ?>
            <article class="ng-vehicle-card">
                <div class="ng-vehicle-card__img">
                    <div class="ng-vehicle-card__placeholder">
                        <svg viewBox="0 0 80 48" fill="none"><path d="M12 34V24l8-12h40l8 12v10" stroke="#CBD5E1" stroke-width="2" stroke-linejoin="round"/><circle cx="22" cy="35" r="5" stroke="#CBD5E1" stroke-width="2"/><circle cx="58" cy="35" r="5" stroke="#CBD5E1" stroke-width="2"/></svg>
                    </div>
                    <?php if ( $body_type ) : ?>
                        <span class="ng-vehicle-card__badge"><?php echo esc_html( ucfirst( $body_type ) ); ?></span>
                    <?php endif; ?>
                </div>
                <div class="ng-vehicle-card__body">
                    <p class="ng-vehicle-card__brand"><?php echo esc_html( $brand_name ); ?></p>
                    <h3 class="ng-vehicle-card__name"><?php echo esc_html( $model_name . ' ' . $v['name'] ); ?></h3>
                    <div class="ng-vehicle-card__actions">
                        <a href="<?php echo esc_url( home_url( '/compare/?v=' . urlencode( $slug ) ) ); ?>" class="ng-btn ng-btn--sm ng-btn--primary">Compare</a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ── BROWSE BY BRAND ────────────────────────────────────────────────────────── -->
<?php if ( ! empty( $brands_cars ) || ! empty( $brands_bikes ) ) : ?>
<section class="ng-section ng-section--gray">
    <div class="ng-container">
        <div class="ng-section__header">
            <h2>Browse by Brand</h2>
        </div>

        <?php if ( ! empty( $brands_cars ) ) : ?>
        <p class="ng-brand-type-label">Cars &amp; SUVs</p>
        <div class="ng-brand-marquee" aria-label="Browse car brands">
            <div class="ng-brand-track">
                <?php foreach ( $brands_cars as $brand ) : ?>
                <a href="<?php echo esc_url( home_url( '/cars/' . ( $brand['slug'] ?? sanitize_title( $brand['name'] ) ) . '/' ) ); ?>" class="ng-brand-tile">
                    <?php if ( ! empty( $brand['logo_url'] ) ) : ?>
                        <img src="<?php echo esc_url( $brand['logo_url'] ); ?>" alt="<?php echo esc_attr( $brand['name'] ); ?>" loading="lazy">
                    <?php else : ?>
                        <span class="ng-brand-tile__name"><?php echo esc_html( $brand['name'] ); ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
                <?php foreach ( $brands_cars as $brand ) : // duplicate for seamless loop ?>
                <a href="<?php echo esc_url( home_url( '/cars/' . ( $brand['slug'] ?? sanitize_title( $brand['name'] ) ) . '/' ) ); ?>" class="ng-brand-tile" aria-hidden="true" tabindex="-1">
                    <?php if ( ! empty( $brand['logo_url'] ) ) : ?>
                        <img src="<?php echo esc_url( $brand['logo_url'] ); ?>" alt="" loading="lazy">
                    <?php else : ?>
                        <span class="ng-brand-tile__name"><?php echo esc_html( $brand['name'] ); ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( ! empty( $brands_bikes ) ) : ?>
        <p class="ng-brand-type-label" style="margin-top:24px">Bikes &amp; Scooters</p>
        <div class="ng-brand-marquee ng-brand-marquee--reverse" aria-label="Browse bike brands">
            <div class="ng-brand-track">
                <?php foreach ( $brands_bikes as $brand ) : ?>
                <a href="<?php echo esc_url( home_url( '/bikes/' . ( $brand['slug'] ?? sanitize_title( $brand['name'] ) ) . '/' ) ); ?>" class="ng-brand-tile">
                    <?php if ( ! empty( $brand['logo_url'] ) ) : ?>
                        <img src="<?php echo esc_url( $brand['logo_url'] ); ?>" alt="<?php echo esc_attr( $brand['name'] ); ?>" loading="lazy">
                    <?php else : ?>
                        <span class="ng-brand-tile__name"><?php echo esc_html( $brand['name'] ); ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
                <?php foreach ( $brands_bikes as $brand ) : ?>
                <a href="<?php echo esc_url( home_url( '/bikes/' . ( $brand['slug'] ?? sanitize_title( $brand['name'] ) ) . '/' ) ); ?>" class="ng-brand-tile" aria-hidden="true" tabindex="-1">
                    <?php if ( ! empty( $brand['logo_url'] ) ) : ?>
                        <img src="<?php echo esc_url( $brand['logo_url'] ); ?>" alt="" loading="lazy">
                    <?php else : ?>
                        <span class="ng-brand-tile__name"><?php echo esc_html( $brand['name'] ); ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="ng-brand-more">
            <a href="<?php echo esc_url( home_url( '/new-cars/' ) ); ?>" class="ng-btn ng-btn--outline">All Cars →</a>
            <a href="<?php echo esc_url( home_url( '/bikes/' ) ); ?>" class="ng-btn ng-btn--outline">All Bikes →</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ── LATEST COMPARISONS ─────────────────────────────────────────────────────── -->
<?php if ( ! empty( $comparisons ) ) : ?>
<section class="ng-section">
    <div class="ng-container">
        <div class="ng-section__header">
            <h2>Latest Comparisons</h2>
            <a href="<?php echo esc_url( home_url( '/compare/' ) ); ?>" class="ng-link--more">All comparisons →</a>
        </div>
        <div class="ng-cards ng-cards--3col">
            <?php foreach ( $comparisons as $comp ) :
                $date = ! empty( $comp['created_at'] ) ? date( 'M Y', strtotime( $comp['created_at'] ) ) : '';
            ?>
            <a href="<?php echo esc_url( home_url( '/compare/' . sanitize_title( $comp['slug'] ) . '/' ) ); ?>" class="ng-compare-card">
                <div class="ng-compare-card__tag">Comparison</div>
                <h3 class="ng-compare-card__title"><?php echo esc_html( $comp['title'] ); ?></h3>
                <?php if ( $date ) : ?>
                    <p class="ng-compare-card__date"><?php echo esc_html( $date ); ?></p>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ── BROWSE BY BODY TYPE ────────────────────────────────────────────────────── -->
<section class="ng-section ng-section--gray">
    <div class="ng-container">
        <div class="ng-section__header">
            <h2>Browse by Type</h2>
        </div>
        <div class="ng-type-grid">
            <?php
            $types = [
                [ 'label' => 'SUV',      'slug' => 'suv',       'icon' => 'M6 30V20l8-10h32l8 10v10H6z' ],
                [ 'label' => 'Sedan',    'slug' => 'sedan',     'icon' => 'M8 30V22l6-8h32l6 8v8H8z M14 22h32' ],
                [ 'label' => 'Hatchback','slug' => 'hatchback', 'icon' => 'M10 30V22l8-8h24l8 8v8H10z' ],
                [ 'label' => 'Electric', 'slug' => 'ev',        'icon' => 'M26 6L14 26h12l-4 16L42 22H28L30 6z' ],
                [ 'label' => 'Pickup',   'slug' => 'pickup',    'icon' => 'M4 32V22l8-10h18v20H4z M30 32V28h14v4H30z' ],
                [ 'label' => 'Minivan',  'slug' => 'minivan',   'icon' => 'M4 32V18l6-8h34l4 8v14H4z M4 22h44' ],
            ];
            foreach ( $types as $type ) : ?>
            <a href="<?php echo esc_url( home_url( '/new-cars/?type=' . $type['slug'] ) ); ?>" class="ng-type-tile">
                <svg viewBox="0 0 54 44" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="<?php echo esc_attr( $type['icon'] ); ?>" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/>
                    <circle cx="16" cy="33" r="4" stroke="currentColor" stroke-width="2.5"/>
                    <circle cx="38" cy="33" r="4" stroke="currentColor" stroke-width="2.5"/>
                </svg>
                <span><?php echo esc_html( $type['label'] ); ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── TRUST STRIP ────────────────────────────────────────────────────────────── -->
<section class="ng-trust-strip">
    <div class="ng-container">
        <div class="ng-trust-strip__grid">
            <div class="ng-trust-strip__item">
                <span class="ng-trust-strip__number">122</span>
                <span class="ng-trust-strip__label">Spec fields tracked per vehicle</span>
            </div>
            <div class="ng-trust-strip__item">
                <span class="ng-trust-strip__number">✓</span>
                <span class="ng-trust-strip__label">Official manufacturer &amp; importer sources only</span>
            </div>
            <div class="ng-trust-strip__item">
                <span class="ng-trust-strip__number">NPR</span>
                <span class="ng-trust-strip__label">On-road prices with Nepal customs &amp; tax included</span>
            </div>
        </div>
    </div>
</section>

<!-- ── LATEST NEWS ───────────────────────────────────────────────────────────── -->
<?php
$recent_posts = get_posts( [ 'numberposts' => 3, 'post_status' => 'publish' ] );
if ( $recent_posts ) : ?>
<section class="ng-section">
    <div class="ng-container">
        <div class="ng-section__header">
            <h2>Latest News &amp; Reviews</h2>
            <a href="<?php echo esc_url( home_url( '/news/' ) ); ?>" class="ng-link--more">All articles →</a>
        </div>
        <div class="ng-cards ng-cards--3col">
            <?php foreach ( $recent_posts as $post ) : setup_postdata( $post ); ?>
            <article class="ng-post-card">
                <?php if ( has_post_thumbnail( $post ) ) : ?>
                <a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="ng-post-card__thumb">
                    <?php echo get_the_post_thumbnail( $post, 'medium_large' ); ?>
                </a>
                <?php endif; ?>
                <div class="ng-post-card__body">
                    <p class="ng-post-card__meta"><?php echo esc_html( get_the_date( 'M j, Y', $post ) ); ?></p>
                    <h3 class="ng-post-card__title">
                        <a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a>
                    </h3>
                    <p class="ng-post-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $post ), 16 ) ); ?></p>
                </div>
            </article>
            <?php endforeach; wp_reset_postdata(); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ── FAQ ───────────────────────────────────────────────────────────────────── -->
<section class="ng-section ng-section--gray" itemscope itemtype="https://schema.org/FAQPage">
    <div class="ng-container ng-container--narrow">
        <div class="ng-section__header ng-section__header--center">
            <h2>Frequently Asked Questions</h2>
        </div>
        <div class="ng-faq">

            <?php
            $faqs = [
                [
                    'q' => 'How are on-road prices calculated for Nepal?',
                    'a' => 'We use the official customs duty rates set by the Nepal government — 40% for electric vehicles and 60% for petrol/diesel vehicles — applied to the manufacturer\'s base USD price, plus 13% VAT, road tax, and handling charges. The full formula is shown on every price estimate.',
                ],
                [
                    'q' => 'Are the spec values official or estimates?',
                    'a' => 'Every spec value is labeled with a confidence badge: green (official manufacturer or importer data), amber (calculated estimate with the formula shown), or red (community-reported, unverified). We never publish AI-generated spec data without human verification.',
                ],
                [
                    'q' => 'What is My Garage and is it free?',
                    'a' => 'My Garage is your personal vehicle dashboard — log maintenance, track fuel, store documents like insurance and bluebook, and set service reminders. The basic version is completely free. A paid plan (Owner Plus, NPR 499/month) unlocks unlimited vehicles, AI insights, and resale reports.',
                ],
                [
                    'q' => 'Can I compare more than two vehicles?',
                    'a' => 'Currently NepaliGarage supports side-by-side comparisons of two vehicles with 122 spec fields. Multi-vehicle comparisons (3+) are on the roadmap for Phase 2.',
                ],
                [
                    'q' => 'How do I add my vehicle if it\'s not in the database?',
                    'a' => 'In My Garage, you can add any vehicle manually — just enter the make, model, and details yourself. If you\'d like your vehicle added to our public database, contact us with the official brochure or source, and we\'ll add it with proper attribution.',
                ],
            ];
            foreach ( $faqs as $faq ) : ?>
            <div class="ng-faq__item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                <button class="ng-faq__question" aria-expanded="false" itemprop="name">
                    <?php echo esc_html( $faq['q'] ); ?>
                    <svg class="ng-faq__icon" viewBox="0 0 20 20" fill="none"><path d="M5 8l5 5 5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </button>
                <div class="ng-faq__answer" hidden itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                    <p itemprop="text"><?php echo esc_html( $faq['a'] ); ?></p>
                </div>
            </div>
            <?php endforeach; ?>

        </div>
    </div>
</section>

<?php get_footer(); ?>
