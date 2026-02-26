<?php

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Disciple_Tools_Dashboard_Endpoints {

    public static function register_routes() {
        // Example route registration. This will be expanded in later phases.
        /*
        register_rest_route(
            'dt/v1',
            '/dashboard/sample',
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_sample_data' ],
                'permission_callback' => function () {
                    return current_user_can( 'access_disciple_tools' );
                },
            ]
        );
        */
    }

    /*
    public static function get_sample_data( $request ) {
        return new WP_REST_Response( [ 'success' => true, 'data' => 'Hello World!' ] );
    }
    */
}
