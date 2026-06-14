<?php
/**
 * Single event template.
 *
 * @package NepaliGarage
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( have_posts() ) :
    while ( have_posts() ) : the_post();
        $event_date      = ngt_event_meta( get_the_ID(), 'event_date' );
        $event_end_date  = ngt_event_meta( get_the_ID(), 'event_end_date' );
        $event_venue     = ngt_event_meta( get_the_ID(), 'event_venue' );
        $event_city      = ngt_event_meta( get_the_ID(), 'event_city' );
        $event_organizer = ngt_event_meta( get_the_ID(), 'event_organizer' );
        $event_link      = ngt_event_meta( get_the_ID(), 'event_link' );
        $gallery_urls    = ngt_event_gallery_urls( get_the_ID() );
        ?>
        <main id="primary" class="site-main">
            <article <?php post_class( 'ng-event-single' ); ?>>
                <section class="ng-page-hero ng-event-single__hero">
                    <div class="ng-container">
                        <p class="ng-page-hero__eyebrow">Event</p>
                        <h1 class="ng-page-hero__title"><?php the_title(); ?></h1>
                        <div class="ng-event-single__meta">
                            <?php if ( $event_date ) : ?>
                                <span><?php echo esc_html( date_i18n( 'j M Y', strtotime( $event_date ) ) ); ?><?php echo $event_end_date ? ' – ' . esc_html( date_i18n( 'j M Y', strtotime( $event_end_date ) ) ) : ''; ?></span>
                            <?php endif; ?>
                            <?php if ( $event_city ) : ?>
                                <span><?php echo esc_html( $event_city ); ?></span>
                            <?php endif; ?>
                            <?php if ( $event_venue ) : ?>
                                <span><?php echo esc_html( $event_venue ); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <section class="ng-section">
                    <div class="ng-container ng-event-single__layout">
                        <div class="ng-event-single__main">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <figure class="ng-event-single__cover"><?php the_post_thumbnail( 'full' ); ?></figure>
                            <?php endif; ?>

                            <div class="ng-event-single__content">
                                <?php the_content(); ?>
                            </div>

                            <?php if ( ! empty( $gallery_urls ) ) : ?>
                                <section class="ng-event-gallery">
                                    <div class="ng-section__header">
                                        <span class="ng-section__eyebrow">Gallery</span>
                                        <h2>Event photos</h2>
                                    </div>
                                    <div class="ng-event-gallery__grid">
                                        <?php foreach ( $gallery_urls as $gallery_url ) : ?>
                                            <a class="ng-event-gallery__item" href="<?php echo esc_url( $gallery_url ); ?>" target="_blank" rel="noopener">
                                                <img src="<?php echo esc_url( $gallery_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>">
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </section>
                            <?php endif; ?>
                        </div>

                        <aside class="ng-event-single__sidebar">
                            <div class="ng-event-sidebar-card">
                                <h2>Event details</h2>
                                <dl class="ng-event-sidebar-card__list">
                                    <?php if ( $event_date ) : ?>
                                        <div><dt>Date</dt><dd><?php echo esc_html( date_i18n( 'j M Y', strtotime( $event_date ) ) ); ?><?php echo $event_end_date ? ' – ' . esc_html( date_i18n( 'j M Y', strtotime( $event_end_date ) ) ) : ''; ?></dd></div>
                                    <?php endif; ?>
                                    <?php if ( $event_venue ) : ?>
                                        <div><dt>Venue</dt><dd><?php echo esc_html( $event_venue ); ?></dd></div>
                                    <?php endif; ?>
                                    <?php if ( $event_city ) : ?>
                                        <div><dt>City</dt><dd><?php echo esc_html( $event_city ); ?></dd></div>
                                    <?php endif; ?>
                                    <?php if ( $event_organizer ) : ?>
                                        <div><dt>Organizer</dt><dd><?php echo esc_html( $event_organizer ); ?></dd></div>
                                    <?php endif; ?>
                                </dl>
                                <?php if ( $event_link ) : ?>
                                    <a class="ng-btn ng-btn--primary ng-event-sidebar-card__cta" href="<?php echo esc_url( $event_link ); ?>" target="_blank" rel="noopener nofollow">Open registration / event link</a>
                                <?php endif; ?>
                            </div>

                            <div class="ng-event-sidebar-card">
                                <h2>More from NepaliGarage</h2>
                                <p>Use the events archive to keep launch coverage, meetups, and showcases discoverable over time.</p>
                                <a class="ng-btn ng-btn--outline ng-event-sidebar-card__cta" href="<?php echo esc_url( get_post_type_archive_link( 'event' ) ); ?>">View all events</a>
                            </div>
                        </aside>
                    </div>
                </section>
            </article>
        </main>
        <?php
    endwhile;
endif;

get_footer();
