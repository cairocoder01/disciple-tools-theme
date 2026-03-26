<?php

if ( !defined( 'ABSPATH' ) ){
    exit; // Exit if accessed directly
}

class Disciple_Tools_Dashboard_Endpoints {

    private static $_instance = null;

    public static function instance(){
        if ( is_null( self::$_instance ) ){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct(){
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route( 'dt/v1', '/dashboard/stats', [
            'methods'  => 'GET',
            'callback' => [ $this, 'get_stats' ],
            'permission_callback' => [ $this, 'permissions_check' ],
        ] );
    }

    public function permissions_check() {
        return current_user_can( 'access_disciple_tools' );
    }

    public function get_stats() {
        // 1. Active Contacts
        $active_contacts = DT_Posts::list_posts( 'contacts', [
            'assigned_to' => [ 'me' ],
            'subassigned' => [ 'me' ],
            'combine' => [ 'subassigned' ],
            'type' => [ 'access' ],
            'overall_status' => [ 'active' ],
            'limit' => 0,
        ] );

        // 2. Update Needed
        $update_needed = DT_Posts::list_posts( 'contacts', [
            'assigned_to' => [ 'me' ],
            'subassigned' => [ 'me' ],
            'combine' => [ 'subassigned' ],
            'overall_status' => [ 'active' ],
            'requires_update' => [ true ],
            'type' => [ 'access' ],
            'limit' => 0,
        ] );

        // 3. Contact Attempt Needed
        $contact_attempt_needed = DT_Posts::list_posts( 'contacts', [
            'assigned_to' => [ 'me' ],
            'subassigned' => [ 'me' ],
            'combine' => [ 'subassigned' ],
            'overall_status' => [ 'active' ],
            'seeker_path' => [ 'none' ],
            'type' => [ 'access' ],
            'limit' => 0,
        ] );

        // 4. Active Groups
        $active_groups = DT_Posts::list_posts( 'groups', [
            'assigned_to' => [ 'me' ],
            'group_status' => [ 'active' ],
            'limit' => 0,
        ] );

        return [
            'active_contacts'        => intval( $active_contacts['total'] ?? 0 ),
            'update_needed'          => intval( $update_needed['total'] ?? 0 ),
            'contact_attempt_needed' => intval( $contact_attempt_needed['total'] ?? 0 ),
            'active_groups'          => intval( $active_groups['total'] ?? 0 ),
        ];
    }
}
Disciple_Tools_Dashboard_Endpoints::instance();
