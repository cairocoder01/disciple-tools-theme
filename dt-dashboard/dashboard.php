<?php

/**
 * Disciple_Tools_Dashboard
 *
 * @class      Disciple_Tools_Dashboard
 * @version    1.0.0
 * @since      1.78.0
 * @package    Disciple.Tools
 * @author     Disciple.Tools
 */

if ( !defined( 'ABSPATH' ) ){
    exit; // Exit if accessed directly
}

class Disciple_Tools_Dashboard {

    private static string $slug = 'dashboard2';

    private static $_instance = null;

    public static function instance(){
        if ( is_null( self::$_instance ) ){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct(){
        $url_path = dt_get_url_path();
        if ( str_starts_with( $url_path, $this::$slug ) ) {
            add_filter( 'dt_templates_for_urls', [ $this, 'base_add_url' ] ); // add custom template
            add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 11 ); // 11 priority after deprecated plugin
        }

        /**
         * Add Navigation Menu
         */
        if ( current_user_can( 'access_disciple_tools' ) ) {
            add_filter( 'desktop_navbar_menu_options', function ( $tabs ){
                $tabs[$this::$slug] = [
                    'link' => site_url( '/' . $this::$slug . '/' ),
                    'label' => __( 'Dashboard', 'disciple_tools' )
                ];
                return $tabs;
            }, 10, 1 ); // priority 10 so it gets put first
        }
    }

    public function base_add_url( $template_for_url ) {
        $template_for_url[$this::$slug] = 'dt-dashboard/template.php';
        return $template_for_url;
    }

    public function scripts() {
        wp_dequeue_style( 'dashboard-css' ); // remove old plugin css
    }
}

Disciple_Tools_Dashboard::instance();
