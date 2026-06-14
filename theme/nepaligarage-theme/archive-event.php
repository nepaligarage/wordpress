<?php
/**
 * Event archive.
 *
 * @package NepaliGarage
 */

defined( 'ABSPATH' ) || exit;

get_header();

$today = current_time( 'Y-m-d' );

$upcoming = new WP_Query( [
    'post_type'      => 'event',
    'post_status'    => 'publish',
    'posts_per_page' => 24,
    'meta_key'       => 'event_date',
    'orderby'        => 'meta_value',
    'order'          => 'ASC',
    'meta_query'     => [
        [
            'key'     => 'event_date',
            'value'   => $today,
            'compare' => '>=',
            'type'    => 'DATE',
        ],
    ],
] );

$past = new WP_Query( [
    'post_type'      => 'event',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
    'meta_key'       => 'event_date',
    'orderby'        => 'meta_value',
    'order'          => 'DESC',
    'meta_query'     => [
        [
            'key'     => 'event_date',
            'value'   => $today,
            'compare' => '<',
            'type'    => 'DATE',
        ],
    ],
] );
?>

<main id="primary" class="site-main">
    <section class="ng-page-hero ng-events-hero">
        <div class="ng-container">
            <p class="ng-page-hero__eyebrow">Events</p>
            <h1 class="ng-page-hero__title">Launches, drives, showcases, and community moments</h1>
            <p class="ng-page-hero__sub">Track upcoming NepaliGarage events, launches, test drives, and partner activities in one place.</p>
        </div>
    </section>

    <section class="ng-section">
        <div class="ng-container">
            <div class="ng-section__header">
                <span class="ng-section__eyebrow">Upcoming</span>
                <h2>What’s next</h2>
                <p>Upcoming events your team can promote, update, and publish through WordPress.</p>
            </div>

            <?php if ( $upcoming->have_posts() ) : ?>
                <div class="ng-events-grid">
                    <?php while ( $upcoming->have_posts() ) : $upcoming->the_post(); ?>
                        <?php
                        $event_date = ngt_event_meta( get_the_ID(), 'event_date' );
                        $event_city = ngt_event_meta( get_the_ID(), 'event_city' );
                        $event_venue = ngt_event_meta( get_the_ID(), 'event_venue' );
                        ?>
                        <article class="ng-event-card">
                            <a class="ng-event-card__link" href="<?php the_permalink(); ?>">
                                <div class="ng-event-card__media">
                                    <?php if ( has_post_thumbnail() ) : ?>
                                        <?php the_post_thumbnail( 'large', [ 'class' => 'ng-event-card__image' ] ); ?>
                                    <?php else : ?>
                                        <div class="ng-event-card__placeholder">Event</div>
                                    <?php endif; ?>
                                </div>
                                <div class="ng-event-card__body">
                                    <span class="ng-event-card__badge">Upcoming</span>
                                    <h3 class="ng-event-card__title"><?php the_title(); ?></h3>
                                    <p class="ng-event-card__meta"><?php echo esc_html( $event_date ? date_i18n( 'j M Y', strtotime( $event_date ) ) : get_the_date() ); ?><?php echo $event_city ? ' · ' . esc_html( $event_city ) : ''; ?></p>
                                    <?php if ( $event_venue ) : ?>
                                        <p class="ng-event-card__venue"><?php echo esc_html( $event_venue ); ?></p>
                                    <?php endif; ?>
                                    <p class="ng-event-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></p>
                                </div>
                            </a>
                        </article>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            <?php else : ?>
                <p class="ng-empty">No upcoming events are published yet.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="ng-section ng-section--muted">
        <div class="ng-container">
            <div class="ng-section__header">
                <span class="ng-section__eyebrow">Archive</span>
                <h2>Past events</h2>
                <p>Keep a public record of launches, activations, and past gatherings.</p>
            </div>

            <?php if ( $past->have_posts() ) : ?>
                <div class="ng-events-grid ng-events-grid--past">
                    <?php while ( $past->have_posts() ) : $past->the_post(); ?>
                        <?php $event_date = ngt_event_meta( get_the_ID(), 'event_date' ); ?>
                        <article class="ng-event-card ng-event-card--past">
                            <a class="ng-event-card__link" href="<?php the_permalink(); ?>">
                                <div class="ng-event-card__body">
                                    <span class="ng-event-card__badge ng-event-card__badge--past">Past event</span>
                                    <h3 class="ng-event-card__title"><?php the_title(); ?></h3>
                                    <p class="ng-event-card__meta"><?php echo esc_html( $event_date ? date_i18n( 'j M Y', strtotime( $event_date ) ) : get_the_date() ); ?></p>
                                    <p class="ng-event-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p>
                                </div>
                            </a>
                        </article>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            <?php else : ?>
                <p class="ng-empty">No past events are published yet.</p>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php get_footer(); ?>
