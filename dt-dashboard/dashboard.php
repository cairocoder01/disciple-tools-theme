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
        require_once 'endpoints.php';
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

        dt_theme_enqueue_script( 'dt-dashboard-js', 'dt-dashboard/dashboard.js', [] );

        wp_localize_script(
            'dt-dashboard-js',
            'dtDashboard',
            [
                'rest_url_base' => esc_url_raw( rest_url() ),
                'nonce'    => wp_create_nonce( 'wp_rest' ),
                'current_user_id' => get_current_user_id(),
                'translations' => [],
            ]
        );
    }

    /**
     * Generate a deep link to a list page with specific filters.
     *
     * @param string $post_type   The post type (e.g., 'contacts', 'groups').
     * @param array  $query       The query parameters (e.g., ['assigned_to' => ['me']]).
     * @param array  $labels      The labels to display for the filter.
     * @param string $filter_id   Optional predefined filter ID.
     * @param string $filter_tab  Optional predefined filter tab key.
     * @param string $filter_name Optional predefined filter name.
     *
     * @return string The formatted URL.
     */
    public static function get_list_url( $post_type, $query = [], $labels = [], $filter_id = '', $filter_tab = '', $filter_name = '' ) {
        $url = site_url( '/' . $post_type . '/' );
        $params = [];
        if ( !empty( $query ) ) {
            $params['query'] = base64_encode( wp_json_encode( $query ) );
        }
        if ( !empty( $labels ) ) {
            $params['labels'] = base64_encode( wp_json_encode( $labels ) );
        }
        if ( !empty( $filter_id ) ) {
            $params['filter_id'] = $filter_id;
        }
        if ( !empty( $filter_tab ) ) {
            $params['filter_tab'] = $filter_tab;
        }
        if ( !empty( $filter_name ) ) {
            $params['filter_name'] = $filter_name;
        }

        if ( !empty( $params ) ) {
            $query_parts = [];
            foreach ( $params as $key => $value ) {
                if ( in_array( $key, [ 'query', 'labels' ], true ) ) {
                    // Base64 params should use rawurlencode to match D.T canonical behavior
                    $query_parts[] = $key . '=' . rawurlencode( $value );
                } else {
                    // Standard params (like filter_name) should use urlencode (spaces to +)
                    $query_parts[] = $key . '=' . urlencode( $value );
                }
            }
            $url .= '?' . implode( '&', $query_parts );
        }

        return $url;
    }
}

Disciple_Tools_Dashboard::instance();
