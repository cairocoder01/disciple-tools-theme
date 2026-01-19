<?php
/*
Template Name: Dashboard
*/
dt_please_log_in();

if ( !current_user_can( 'access_disciple_tools' ) ) {
    wp_safe_redirect( '/registered' );
    exit();
}
?>

<?php get_header(); ?>

    <div class="template-dashboard">

        <section id="pending-contacts">
            <div class="title-icon">
                <img src="<?php echo esc_url( get_template_directory_uri() ) . '/dt-assets/images/assigned-to.svg' ?>">

            </div>
            <h2 class="title-label"><?php esc_html_e( 'Pending Contacts', 'disciple_tools' ) ?></h2>
            <div class="contacts-list"></div>
        </section>


    </div> <!-- end #content -->

<?php get_footer(); ?>
