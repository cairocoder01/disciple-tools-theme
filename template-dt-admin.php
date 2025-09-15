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

require_once get_template_directory() . '/vendor/autoload.php';
use Kucrut\Vite;

// Initialize Vite for WP
add_action('init', function() {
    Vite\init( get_template_directory_uri() . '/dt-assets/build/vue' );
});

// Enqueue the Vue.js application script only on dt-admin pages
function dt_admin_enqueue_scripts() {
    // Only enqueue scripts if we're actually on a dt-admin page
    $url_path = dt_get_url_path();
    if ( strpos( $url_path, 'dt-admin' ) !== 0 ) {
        return;
    }

    Vite\enqueue_asset(
        get_template_directory() . '/dt-assets/dist',
        'src/main.js',
        [ 'handle' => 'dt-admin-vue-app' ]
    );

    // Pass current URL path to the Vue app for routing
    wp_localize_script( 'dt-admin-vue-app', 'dtAdminData', array(
        'current_path' => $url_path,
        'base_url' => home_url( '/dt-admin' ),
        'rest_url' => rest_url(),
        'nonce' => wp_create_nonce( 'wp_rest' ),
        'current_user_id' => get_current_user_id(),
    ));
}

add_action( 'wp_enqueue_scripts', 'dt_admin_enqueue_scripts' );

?>

<?php get_header(); ?>

<div id="content" class="template-dt-admin">
    <!-- Vue.js Application Container -->
    <div id="dt-admin-app">
        <!-- Loading state while Vue app initializes -->
        <div class="dt-admin-loading" style="text-align: center; padding: 50px;">
            <h1><?php esc_html_e( 'Hello World', 'disciple_tools' ); ?></h1>
            <p><?php esc_html_e( 'Loading DT Admin...', 'disciple_tools' ); ?></p>
        </div>
    </div>
</div>

<?php get_footer(); ?>
