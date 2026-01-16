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


            <h1>Hello Dashboard!</h1>


    </div> <!-- end #content -->

<?php get_footer(); ?>
