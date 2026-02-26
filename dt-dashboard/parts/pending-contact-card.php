<?php
/**
 * Template part for a single pending contact card in the dashboard.
 *
 * @package Disciple_Tools
 * @subpackage Disciple_Tools_Theme/dt-dashboard
 * @since 2.0.0
 *
 * @var stdClass $contact {
 *     @type int    $ID
 *     @type string $post_title
 *     @type string $post_type
 * }
 */

if ( ! empty( $contact ) ) :
    $permalink = get_permalink( $contact->ID );
    $contact_detail = DT_Posts::get_post( $contact->post_type, $contact->ID );

    $location_values = [];
    if ( isset( $contact_detail['location_grid_meta'] ) ) {
        $location_values = array_map( function( $location ) {
            return $location['label'];
        }, $contact_detail['location_grid_meta'] );
    } else if ( isset( $contact_detail['location_grid'] ) ) {
        $location_values = array_map( function( $location ) {
            return $location['matched_search'] ?? $location['label'];
        }, $contact_detail['location_grid'] );
    }
    $location = implode( ', ', $location_values );

    ?>
    <div class="contact-card" id="contact-card-<?php echo esc_attr( $contact->ID ); ?>">
        <div class="contact-name"><?php echo esc_html( $contact->post_title ); ?></div>

        <?php if ( !empty( $location_values ) ): ?>
        <div class="contact-meta">
            <?php foreach ( $location_values as $location_value ): ?>
            <span><?php echo esc_html( $location_value ); ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ( isset( $contact_detail['age'] ) ) : ?>
        <div class="contact-meta">
            <?php echo esc_html( $contact_detail['age']['label'] ); ?></span>
        </div>
        <?php endif; ?>

        <?php if ( isset( $contact_detail['gender'] ) ) : ?>
        <div class="contact-meta">
            <?php echo esc_html( $contact_detail['gender']['label'] ); ?></span>
        </div>
        <?php endif; ?>

        <div class="contact-actions">
            <button class="button btn-accept" data-contact-id="<?php echo esc_attr( $contact->ID ); ?>" data-action="accept">Accept</button>
            <button class="button btn-decline" data-contact-id="<?php echo esc_attr( $contact->ID ); ?>" data-action="decline">Decline</button>
            <a href="<?php echo esc_url( $permalink ); ?>" class="button btn-details">See Details</a>
        </div>
    </div>
    <?php
endif;
