<?php
get_header();

// ── Data ──────────────────────────────────────────────────────────────────────

$brands_cars  = ngt_supabase_get( 'brands', [ 'select' => 'id,name,slug,logo_url,type', 'type' => 'eq.car',  'order' => 'name.asc' ] );
$brands_bikes = ngt_supabase_get( 'brands', [ 'select' => 'id,name,slug,logo_url,type', 'type' => 'eq.bike', 'order' => 'name.asc' ] );

// Fixed join syntax + prices (was broken: used model(...) instead of models!inner(...))
$variants = ngt_supabase_get( 'variants', [
    'select'             => 'id,name,slug,starting_price_npr,year_from,model_id,models!inner(id,name,slug,body_type,is_ev,brands!inner(name,slug,logo_url))',
    'is_available_nepal' => 'eq.true',
    'order'              => 'year_from.desc,name.asc',
    'limit'              => '8',
] );
$home_price_rows = ngt_prices_for_variants( array_column( $variants, 'id' ) );

$comparisons = ngt_supabase_get( 'comparisons', [
    'select'    => 'id,slug,title,created_at',
    'published' => 'eq.true',
    'order'     => 'created_at.desc',
    'limit'     => '3',
] );
?>

<!-- ── HERO ──────────────────────────────────────────────────────────────────── -->
<section class="ng-hero">
    <div class="ng-hero__bg" data-parallax="0.18" aria-hidden="true"></div>
    <div class="ng-hero__shape" data-parallax="0.35" aria-hidden="true"></div>
    <div class="ng-container">
        <div class="ng-hero__content">
            <p class="ng-hero__eyebrow">Nepal-first vehicle intelligence platform</p>
            <h1 class="ng-hero__title">Research Better.<br>Buy Smarter.</h1>
            <p class="ng-hero__subtitle">Compare cars, audit specs, check Nepal on-road pricing, and understand ownership tradeoffs before you shortlist anything.</p>

            <!-- Tabbed search widget -->
            <div class="ng-herosearch" role="search">
                <div class="ng-herosearch__tabs" role="tablist" aria-label="Search NepaliGarage">
                    <button type="button" class="ng-herosearch__tab is-active" role="tab" id="ng-hstab-find"
                            aria-selected="true" aria-controls="ng-hspanel-find" data-hstab="find">Find a car</button>
                    <button type="button" class="ng-herosearch__tab" role="tab" id="ng-hstab-estimate" tabindex="-1"
                            aria-selected="false" aria-controls="ng-hspanel-estimate" data-hstab="estimate">Estimate price</button>
                    <button type="button" class="ng-herosearch__tab" role="tab" id="ng-hstab-compare" tabindex="-1"
                            aria-selected="false" aria-controls="ng-hspanel-compare" data-hstab="compare">Compare</button>
                </div>

                <!-- Find a car -->
                <div class="ng-herosearch__panel is-active" role="tabpanel" id="ng-hspanel-find"
                     aria-labelledby="ng-hstab-find" data-hspanel="find">
                    <div class="ng-herosearch__row">
                        <label class="ng-screen-reader-text" for="ng-hs-find-make">Make</label>
                        <select class="ng-herosearch__select" id="ng-hs-find-make"><option value="">Make</option></select>
                        <label class="ng-screen-reader-text" for="ng-hs-find-model">Model</label>
                        <select class="ng-herosearch__select" id="ng-hs-find-model" disabled><option value="">Model</option></select>
                        <button type="button" class="ng-btn ng-btn--red" id="ng-hs-find-go">Search</button>
                    </div>
                    <p class="ng-herosearch__hint">Pick a make and model to jump straight to its Nepal page — or just a make to browse the full lineup.</p>
                </div>

                <!-- Estimate price -->
                <div class="ng-herosearch__panel" role="tabpanel" id="ng-hspanel-estimate"
                     aria-labelledby="ng-hstab-estimate" data-hspanel="estimate" hidden>
                    <p class="ng-herosearch__lead">Estimate the Nepal on-road price of an imported vehicle — customs duty, VAT and road tax included.</p>
                    <a href="<?php echo esc_url( home_url( '/nepal-car-price-estimator/' ) ); ?>" class="ng-btn ng-btn--red">Open price estimator →</a>
                </div>

                <!-- Compare -->
                <div class="ng-herosearch__panel" role="tabpanel" id="ng-hspanel-compare"
                     aria-labelledby="ng-hstab-compare" data-hspanel="compare" hidden>
                    <div class="ng-herosearch__compare">
                        <div class="ng-herosearch__row">
                            <label class="ng-screen-reader-text" for="ng-hs-cmp-a-make">First car make</label>
                            <select class="ng-herosearch__select" id="ng-hs-cmp-a-make"><option value="">Make</option></select>
                            <label class="ng-screen-reader-text" for="ng-hs-cmp-a-model">First car model</label>
                            <select class="ng-herosearch__select" id="ng-hs-cmp-a-model" disabled><option value="">Model</option></select>
                        </div>
                        <span class="ng-herosearch__vs" aria-hidden="true">vs</span>
                        <div class="ng-herosearch__row">
                            <label class="ng-screen-reader-text" for="ng-hs-cmp-b-make">Second car make</label>
                            <select class="ng-herosearch__select" id="ng-hs-cmp-b-make"><option value="">Make</option></select>
                            <label class="ng-screen-reader-text" for="ng-hs-cmp-b-model">Second car model</label>
                            <select class="ng-herosearch__select" id="ng-hs-cmp-b-model" disabled><option value="">Model</option></select>
                        </div>
                        <button type="button" class="ng-btn ng-btn--red" id="ng-hs-cmp-go">Compare →</button>
                    </div>
                </div>
            </div>

            <div class="ng-hero__quickchips">
                <a href="<?php echo esc_url( home_url( '/new-cars/#type=car' ) ); ?>" class="ng-hero__search-btn">Cars &amp; SUVs</a>
                <a href="<?php echo esc_url( home_url( '/bikes/' ) ); ?>" class="ng-hero__search-btn">Bikes</a>
                <a href="<?php echo esc_url( home_url( '/new-cars/#fuel=ev' ) ); ?>" class="ng-hero__search-btn">Electric</a>
                <a href="<?php echo esc_url( home_url( '/compare/' ) ); ?>" class="ng-hero__search-btn">Compare</a>
            </div>

            <div class="ng-hero__trust">
                <span>88+ models tracked</span>
                <span>NPR on-road prices</span>
                <span>Nepal customs &amp; tax included</span>
            </div>
        </div>
    </div>
</section>

<!-- ── BROWSE BY BUDGET ──────────────────────────────────────────────────────── -->
<section class="ng-section ng-section--gray">
    <div class="ng-container">
        <div class="ng-section__header">
            <div class="ng-section__header-left">
                <p class="ng-section__label">Find your fit</p>
                <h2 data-animate>Browse by budget</h2>
                <p class="ng-section__intro">On-road price bands in NPR — built with Nepal customs, VAT &amp; road tax.</p>
            </div>
            <a href="<?php echo esc_url( home_url( '/new-cars/' ) ); ?>" class="ng-link--more">All cars →</a>
        </div>
        <div class="ng-budget-grid">
            <?php
            $budget_bands = [
                [ 'hash' => 'under-5l',  'top' => 'Under',          'amount' => 'Rs 5 Lakh' ],
                [ 'hash' => '5l-15l',    'top' => 'Rs 5 – 15',      'amount' => 'Lakh' ],
                [ 'hash' => '15l-50l',   'top' => 'Rs 15 – 50',     'amount' => 'Lakh' ],
                [ 'hash' => '50l-1cr',   'top' => 'Rs 50 L – 1',    'amount' => 'Crore' ],
                [ 'hash' => 'above-1cr', 'top' => 'Above',          'amount' => 'Rs 1 Crore' ],
            ];
            foreach ( $budget_bands as $band ) : ?>
            <a class="ng-budget-card" href="<?php echo esc_url( home_url( '/new-cars/#price=' . $band['hash'] ) ); ?>">
                <span class="ng-budget-card__top"><?php echo esc_html( $band['top'] ); ?></span>
                <strong class="ng-budget-card__amount"><?php echo esc_html( $band['amount'] ); ?></strong>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── FEATURED VEHICLES ──────────────────────────────────────────────────────── -->
<?php if ( ! empty( $variants ) ) : ?>
<section class="ng-section">
    <div class="ng-container">
        <div class="ng-section__header">
            <h2 data-animate>Latest Models in Nepal</h2>
            <a href="<?php echo esc_url( home_url( '/new-cars/' ) ); ?>" class="ng-link--more">See all →</a>
        </div>
        <div class="ng-cards ng-cards--scroll">
            <?php foreach ( $variants as $v ) :
                $model      = $v['models'] ?? [];
                $brand      = $model['brands'] ?? [];
                $brand_slug = $brand['slug'] ?? '';
                $model_slug = $model['slug'] ?? '';
                $logo_url   = $brand['logo_url'] ?? '';
                $brand_name = $brand['name'] ?? '';
                $model_name = $model['name'] ?? '';
                $body_type  = $model['body_type'] ?? '';
                $is_ev      = ! empty( $model['is_ev'] );
                $href       = ( $brand_slug && $model_slug )
                              ? home_url( '/cars/' . $brand_slug . '/' . $model_slug . '/' )
                              : home_url( '/new-cars/' );
                $price      = ngt_variant_price_display( $v, $home_price_rows );
            ?>
            <a class="ng-vehicle-card" href="<?php echo esc_url( $href ); ?>">
                <div class="ng-vehicle-card__img ng-vehicle-card__logo-bg">
                    <?php if ( $logo_url ) : ?>
                        <img src="<?php echo esc_url( $logo_url ); ?>"
                             alt="<?php echo esc_attr( $brand_name ); ?>"
                             class="ng-brand-logo-filter"
                             style="max-height:60px;max-width:120px;object-fit:contain;" loading="lazy">
                    <?php else : ?>
                        <span class="ng-vehicle-card__logo-fallback"><?php echo esc_html( $brand_name ); ?></span>
                    <?php endif; ?>
                </div>
                <div class="ng-vehicle-card__body">
                    <div class="ng-vehicle-card__badges">
                        <?php if ( $body_type ) : ?>
                            <span class="ng-vehicle-card__badge"><?php echo esc_html( ucfirst( $body_type ) ); ?></span>
                        <?php endif; ?>
                        <?php if ( $is_ev ) : ?>
                            <span class="ng-vehicle-card__badge ng-badge--ev">EV</span>
                        <?php endif; ?>
                    </div>
                    <p class="ng-vehicle-card__brand"><?php echo esc_html( $brand_name ); ?></p>
                    <h3 class="ng-vehicle-card__name"><?php echo esc_html( $model_name ); ?></h3>
                    <p class="ng-vehicle-card__price"><?php echo esc_html( $price['label'] ); ?></p>
                    <div class="ng-card-price-meta"><?php echo wp_kses_post( ngt_price_badge_html( $price ) ); ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ── LATEST COMPARISONS (signature feature — elevated) ──────────────────────── -->
<?php if ( ! empty( $comparisons ) ) : ?>
<section class="ng-section ng-section--gray">
    <div class="ng-container">
        <p class="ng-section__label">Comparison Engine</p>
        <div class="ng-section__header ng-section__header--stack">
            <div class="ng-section__header-left">
                <h2 data-animate>Latest Comparisons</h2>
                <p class="ng-section__intro">Side-by-side specs, on-road pricing, and Nepal-specific context — so you can decide with confidence.</p>
            </div>
            <a href="<?php echo esc_url( home_url( '/compare/' ) ); ?>" class="ng-link--more">All comparisons →</a>
        </div>
        <div class="ng-cards ng-cards--3col">
            <?php foreach ( $comparisons as $comp ) :
                $date = ! empty( $comp['created_at'] ) ? date( 'M Y', strtotime( $comp['created_at'] ) ) : '';
            ?>
            <a href="<?php echo esc_url( home_url( '/compare/' . sanitize_title( $comp['slug'] ) . '/' ) ); ?>" class="ng-compare-card">
                <div class="ng-compare-card__tag">Head-to-Head</div>
                <h3 class="ng-compare-card__title"><?php echo esc_html( $comp['title'] ); ?></h3>
                <?php if ( $date ) : ?>
                    <p class="ng-compare-card__date"><?php echo esc_html( $date ); ?></p>
                <?php endif; ?>
                <span class="ng-compare-card__cta">Read comparison →</span>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="ng-compare-section-footer">
            <a href="<?php echo esc_url( home_url( '/compare/' ) ); ?>" class="ng-btn ng-btn--outline">Build your own comparison →</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ── TRUST STRIP ────────────────────────────────────────────────────────────── -->
<section class="ng-trust-strip">
    <div class="ng-trust-strip__bg" data-parallax="0.15" aria-hidden="true"></div>
    <div class="ng-container">
        <div class="ng-trust-strip__grid">
            <div class="ng-trust-strip__item" data-animate data-animate-delay="0">
                <span class="ng-trust-strip__number">88+</span>
                <span class="ng-trust-strip__label">Cars &amp; bikes tracked with Nepal availability status</span>
            </div>
            <div class="ng-trust-strip__item" data-animate data-animate-delay="150">
                <span class="ng-trust-strip__number">On-Road</span>
                <span class="ng-trust-strip__label">Prices built with Nepal customs, VAT &amp; road tax</span>
            </div>
            <div class="ng-trust-strip__item" data-animate data-animate-delay="300">
                <span class="ng-trust-strip__number">Free</span>
                <span class="ng-trust-strip__label">Side-by-side comparison — no account needed</span>
            </div>
        </div>
    </div>
</section>

<!-- ── BROWSE BY BRAND ────────────────────────────────────────────────────────── -->
<?php if ( ! empty( $brands_cars ) || ! empty( $brands_bikes ) ) : ?>
<section class="ng-section">
    <div class="ng-container">
        <div class="ng-section__header">
            <h2 data-animate>Browse by Brand</h2>
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
        <p class="ng-brand-type-label ng-brand-type-label--gap">Bikes &amp; Scooters</p>
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
                <?php foreach ( $brands_bikes as $brand ) : // duplicate for seamless loop ?>
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

<!-- ── OWNERS' HUB ───────────────────────────────────────────────────────────── -->
<section class="ng-owners">
    <div class="ng-container">
        <div class="ng-section__header ng-section__header--center">
            <p class="ng-section__label ng-owners__label">My Garage — free for owners</p>
            <h2 data-animate>Already own a vehicle?</h2>
            <p class="ng-section__intro ng-owners__intro">Track maintenance and renewals, keep your records in one place, and get a sense of what your vehicle is worth.</p>
        </div>
        <div class="ng-owners__grid">
            <div class="ng-owners__card">
                <h3 class="ng-owners__card-title">Track your car</h3>
                <p class="ng-owners__card-text">Log service history, fuel and documents, and get reminders for tax, insurance &amp; pollution renewals — free.</p>
                <a href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>" class="ng-btn ng-btn--red">Open My Garage →</a>
            </div>
            <div class="ng-owners__card">
                <h3 class="ng-owners__card-title">List your car</h3>
                <p class="ng-owners__card-text">Keep your vehicle details ready in your garage so you can share them when you're set to sell. <span class="ng-owners__soon">Public listings coming soon.</span></p>
                <a href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>" class="ng-btn ng-btn--outline-white">Add your vehicle →</a>
            </div>
            <div class="ng-owners__card">
                <h3 class="ng-owners__card-title">Estimate its value</h3>
                <p class="ng-owners__card-text">Use our Nepal price tool to gauge an approximate import/market value as a starting reference. <span class="ng-owners__soon">Used-value model coming soon.</span></p>
                <a href="<?php echo esc_url( home_url( '/nepal-car-price-estimator/' ) ); ?>" class="ng-btn ng-btn--outline-white">Estimate value →</a>
            </div>
        </div>
    </div>
</section>

<!-- ── LATEST NEWS ────────────────────────────────────────────────────────────── -->
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

<!-- ── FAQ ────────────────────────────────────────────────────────────────────── -->
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
                    'a' => 'The estimator uses configurable Nepal cost assumptions for duty, VAT, road tax, and handling. Treat estimates as planning guidance and verify final prices with authorized dealers or official notices before purchase.',
                ],
                [
                    'q' => 'Are the spec values official or estimates?',
                    'a' => 'Spec and price values should show confidence and source context where available. Coverage is still expanding, and uncertain values are labeled conservatively rather than treated as final facts.',
                ],
                [
                    'q' => 'What is My Garage and is it free?',
                    'a' => 'My Garage is your personal vehicle dashboard — log maintenance, track fuel, store documents like insurance and bluebook, and set service reminders. The basic version is completely free. A paid plan (Owner Plus, NPR 499/month) unlocks unlimited vehicles, AI insights, and resale reports.',
                ],
                [
                    'q' => 'Can I compare more than two vehicles?',
                    'a' => 'Currently NepaliGarage supports side-by-side comparisons of two vehicles. Spec coverage is expanding as reliable Nepal-relevant sources are added.',
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
