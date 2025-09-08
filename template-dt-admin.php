<?php
/*
Template Name: DT Admin
*/

// Security check - require login
dt_please_log_in();

// Check for access permissions (administrators or users with specific capability)
if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'access_disciple_tools' ) ) {
    wp_safe_redirect( '/registered' );
    exit();
}

// Enqueue the Vue.js application script
add_action( 'wp_enqueue_scripts', 'dt_admin_enqueue_scripts' );

function dt_admin_enqueue_scripts() {
    // Enqueue Vue.js from CDN
    wp_enqueue_script(
        'vuejs',
        'https://unpkg.com/vue@3/dist/vue.global.js',
        array(),
        '3.3.4',
        true
    );

    // Enqueue Vue Router from CDN
    wp_enqueue_script(
        'vue-router',
        'https://unpkg.com/vue-router@4/dist/vue-router.global.js',
        array( 'vuejs' ),
        '4.2.4',
        true
    );

    // Enqueue our Vue application
    wp_enqueue_script(
        'dt-admin-vue-app',
        get_template_directory_uri() . '/dt-assets/js/app.vue.js',
        array( 'vuejs', 'vue-router' ),
        filemtime( get_template_directory() . '/dt-assets/js/app.vue.js' ),
        true
    );

    // Pass current URL path to the Vue app for routing
    $url_path = dt_get_url_path();
    wp_localize_script( 'dt-admin-vue-app', 'dtAdminData', array(
        'current_path' => $url_path,
        'base_url' => home_url( '/dt-admin' ),
        'rest_url' => rest_url(),
        'nonce' => wp_create_nonce( 'wp_rest' ),
        'current_user_id' => get_current_user_id(),
    ));
}

?>

<?php get_header(); ?>

<div id="content" class="template-dt-admin">
    <div id="inner-content" class="grid-x grid-margin-x">
        <div class="large-12 medium-12 small-12 cell">

            <!-- Vue.js Application Container -->
            <div id="dt-admin-app">
                <!-- Loading state while Vue app initializes -->
                <div class="dt-admin-loading" style="text-align: center; padding: 50px;">
                    <h1><?php esc_html_e( 'Hello World', 'disciple_tools' ); ?></h1>
                    <p><?php esc_html_e( 'Loading DT Admin...', 'disciple_tools' ); ?></p>
                </div>
            </div>

        </div>
    </div>
</div>

<?php get_footer(); ?>
