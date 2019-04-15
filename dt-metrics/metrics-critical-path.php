<?php

Disciple_Tools_Metrics_Critical_Path::instance();
class Disciple_Tools_Metrics_Critical_Path extends Disciple_Tools_Metrics_Hooks_Base {
    public $permissions = [ 'view_any_contacts', 'view_project_metrics' ];
    private static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    } // End instance()

    public function __construct() {
        if ( !$this->has_permission() ) {
            return;
        }
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );


        $url_path = dt_get_url_path();
        if ( 'metrics' === substr( $url_path, '0', 7 ) ) {

            add_filter( 'dt_templates_for_urls', [ $this, 'add_url' ] ); // add custom URL
            add_filter( 'dt_metrics_menu', [ $this, 'add_menu' ], 50 );

            if ( 'metrics/critical-path' === $url_path ) {
                add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 99 );
            }
        }
    }

    public function add_url( $template_for_url ) {
        $template_for_url['metrics/critical-path'] = 'template-metrics.php';

        return $template_for_url;
    }

    public function add_menu( $content ) {
        $content .= '
            <li><a href="' . site_url( '/metrics/critical-path/' ) . '#project_critical_path" onclick="project_critical_path()">' . esc_html__( 'Critical Path', 'disciple_tools' ) . '</a></li>
            ';
//            <li><a href="">' . esc_html__( 'Critical Path', 'disciple_tools' ) . '</a>
//                <ul class="menu vertical nested" id="path-menu" aria-expanded="true">
//                    <li><a href="' . site_url( '/metrics/critical-path/' ) . '#project_critical_path" onclick="project_critical_path()">' . esc_html__( 'Critical Path', 'disciple_tools' ) . '</a></li>
//                </ul>
//            </li>

        return $content;
    }

    public function scripts() {
        wp_enqueue_script( 'dt_metrics_project_script', get_stylesheet_directory_uri() . '/dt-metrics/metrics-critical-path.js', [
            'moment',
            'jquery',
            'jquery-ui-core',
            'amcharts-core',
            'amcharts-charts',
            'datepicker',
            'wp-i18n'
        ], filemtime( get_theme_file_path() . '/dt-metrics/metrics-critical-path.js' ) );

        wp_localize_script(
            'dt_metrics_project_script', 'dtMetricsProject', [
                'root'               => esc_url_raw( rest_url() ),
                'theme_uri'          => get_stylesheet_directory_uri(),
                'nonce'              => wp_create_nonce( 'wp_rest' ),
                'current_user_login' => wp_get_current_user()->user_login,
                'current_user_id'    => get_current_user_id(),
                'map_key'            => dt_get_option( 'map_key' ),
                'data'               => $this->data(),
            ]
        );
    }

    public function data() {

        /**
         * Apply Filters before final enqueue. This provides opportunity for complete override or modification of chart.
         */

        return [
            'cp' => self::critical_path_activity( dt_date_start_of_year(), time() )
        ];
    }

    /**
     * API Routes
     */
    public function add_api_routes() {
        $version   = '1';
        $namespace = 'dt/v' . $version;

        register_rest_route(
            $namespace, '/metrics/critical_path_by_year/(?P<id>[\w-]+)', [
                [
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => [ $this, 'critical_path_by_year' ],
                ],
            ]
        );
        register_rest_route(
            $namespace, '/metrics/critical_path_activity', [
                [
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => [ $this, 'critical_path_activity_callback' ],
                ],
            ]
        );
    }

    public function critical_path_by_year( WP_REST_Request $request ) {
        if ( !$this->has_permission() ) {
            return new WP_Error( "critical_path_by_year", "Missing Permissions", [ 'status' => 400 ] );
        }
        $params = $request->get_params();
        if ( isset( $params['id'] ) ) {
            if ( $params['id'] == 'all' ) {
                $start = 0;
                $end   = PHP_INT_MAX;
            } else {
                $year  = (int) $params['id'];
                $start = DateTime::createFromFormat( "Y-m-d", $year . '-01-01' )->getTimestamp();
                $end   = DateTime::createFromFormat( "Y-m-d", ( $year + 1 ) . '-01-01' )->getTimestamp();
            }
            $result = Disciple_Tools_Metrics_Hooks_Base::chart_critical_path( $start, $end );
            if ( is_wp_error( $result ) ) {
                return $result;
            } else {
                return new WP_REST_Response( $result );
            }
        } else {
            return new WP_Error( "critical_path_by_year", "Missing a valid contact id", [ 'status' => 400 ] );
        }
    }

    public function _no_results() {
        return '<p>' . esc_attr( 'No Results', 'disciple_tools' ) . '</p>';
    }

    public function critical_path_activity_callback( WP_REST_Request $request ){
        if ( !$this->has_permission() ) {
            return new WP_Error( "critical_path_activity_callback", "Missing Permissions", [ 'status' => 400 ] );
        }
        $params = $request->get_params();
        if ( isset( $params["start"], $params["end"] ) ){
            $start = strtotime( $params["start"] );
            $end = strtotime( $params["end"] );
            $result = self::critical_path_activity( $start, $end );
            if ( is_wp_error( $result ) ) {
                return $result;
            } else {
                return new WP_REST_Response( $result );
            }
        } else {
            return new WP_Error( "critical_path_activity_callback", "Missing a valid values", [ 'status' => 400 ] );
        }
    }


    /**
     * Each row will be of the format:
     * [
     *      "key" => "new_contacts"
     *      "label => "New contacts"
     *      "value" => 1939
     * ]
     * @param int $start
     * @param int $end
     *
     * @return array
     */
    public static function critical_path_activity( $start = 0, $end = 0 ){
        $data = [];
        $manual_additions = Disciple_Tools_Counter_Outreach::get_outreach_count( 'manual_additions', $start, $end );
        foreach ( $manual_additions as $addition ){
            if ( $addition["section"] == "before") {
                $data[] = [
                    "key" => $addition["source"],
                    "label" => $addition["label"],
                    "outreach" => $addition["total"]
                ];
            }
        }
        $new_contacts = Disciple_Tools_Counter_Contacts::new_contact_count( $start, $end );
        $current_contacts = Disciple_Tools_Counter_Contacts::new_contact_count( 0, $end );
        $data[] = [
            "key" => "new_contacts",
            "label" => __( "New Contacts", "disciple_tools" ),
            "value" => (int) $new_contacts,
            "total" => (int) $current_contacts,
            "type" => "activity"
        ];
        $status_at_date = Disciple_Tools_Counter_Contacts::overall_status_at_date( $end );
        $assigned_contacts = Disciple_Tools_Counter_Contacts::assigned_contacts_count( $start, $end );
        $data[] = [
            "key" => "assigned_contacts",
            "label" => __( "Assigned Contacts", "disciple_tools" ),
            "value" => (int) $assigned_contacts,
            "total" => (int) $status_at_date["assigned"]["value"],
            "type" => "activity"
        ];
        $active_contacts = Disciple_Tools_Counter_Contacts::active_contacts_count( $start, $end );
        $data[] = [
            "key" => "active_contacts",
            "label" => __( "Active Contacts", "disciple_tools" ),
            "value" => (int) $active_contacts,
            "total" => (int) $status_at_date["active"]["value"],
            "type" => "activity"
        ];
        $seeker_path_counts = Disciple_Tools_Counter_Contacts::seeker_path_at_date( $end );
        $seeker_path_activity = Disciple_Tools_Counter_Contacts::seeker_path_activity( $start, $end );
        foreach ( $seeker_path_counts as $key => $val ){
            if ( $key !== "none" ){
                $data[] = [
                    "key" => $key,
                    "label" => $val["label"],
                    "value" => (int) $seeker_path_activity[$key]["value"],
                    "total" => (int) $val["value"],
                    "type" => ( $key == "ongoing" || $key == "coaching" ) ? "ongoing" : "activity"
                ];
            }
        }
        $baptisms = Disciple_Tools_Counter_Baptism::get_number_of_baptisms( $start, $end );
        $baptisms_total = Disciple_Tools_Counter_Baptism::get_number_of_baptisms( $start, $end );
        $data[] = [
            "key" => "baptisms",
            "label" => __( "Baptisms", "disciple_tools" ),
            "value" => (int) $baptisms,
            "total" => (int) $baptisms_total,
            "type" => "activity"
        ];
        $baptism_generations = Disciple_Tools_Counter_Baptism::get_baptism_generations( $start, $end );
        $baptism_generations_total = Disciple_Tools_Counter_Baptism::get_baptism_generations( 0, $end );
        foreach ( $baptism_generations_total as $gen => $count ){
            $value = 0;
            foreach ( $baptism_generations as $g => $c ){
                if ( $g === $gen ){
                    $value = $c;
                }
            }
            $data[] = [
                "key" => "baptism_generation_$gen",
                "label" => sprintf( __( "Baptism Generation %s", "disciple_tools" ), $gen ),
                "value" => (int) $value,
                "total" => (int) $count,
                "type" => "activity"
            ];
        }
        $baptizers = Disciple_Tools_Counter_Baptism::get_number_of_baptizers( $start, $end );
        $total_baptizers = Disciple_Tools_Counter_Baptism::get_number_of_baptizers( 0, $end );
        $data[] = [
            "key" => "baptizers",
            "label" => __( "Baptizers", "disciple_tools" ),
            "value" => (int) $baptizers,
            "total" => (int) $total_baptizers,
            "type" => "activity"
        ];
        $active_groups = Disciple_Tools_Counter_Groups::get_groups_count( 'active_groups', $start, $end );
        $current_groups = Disciple_Tools_Counter_Groups::get_groups_count( 'active_groups', $end -1, $end );
        $data[] = [
            "key" => "active_groups",
            "label" => __( "Active Groups", "disciple_tools" ),
            "value" => (int) $active_groups,
            "total" => (int) $current_groups,
            "type" => "ongoing"
        ];
        $active_churches = Disciple_Tools_Counter_Groups::get_groups_count( 'active_churches', $start, $end );
        $current_churches = Disciple_Tools_Counter_Groups::get_groups_count( 'active_churches', $end - 1, $end );
        $data[] = [
            "key" => "active_churches",
            "label" => __( "Active Churches", "disciple_tools" ),
            "value" => (int) $active_churches,
            "total" => (int) $current_churches,
            "type" => "ongoing"
        ];
//        @todo churches + groups
        $church_generations = Disciple_Tools_Counter_Groups::get_groups_count( 'church_generations', $start, $end );
        $current_church_generations = Disciple_Tools_Counter_Groups::get_groups_count( 'church_generations', $end - 1, $end );
        $max_gen = max( sizeof( $church_generations ), sizeof( $current_church_generations ) );
        for ( $i = 1;  $i <= $max_gen; $i++ ){
            $data[] = [
                "key" => "church_generation_$i",
                "label" => sprintf( __( "Church Generation %s", "disciple_tools" ), $i ),
                "value" => (int) isset( $church_generations[$i] ) ? $church_generations[$i] : 0,
                "total" => (int) isset( $current_church_generations[$i] ) ? $current_church_generations[$i] : 0,
                "type" => "ongoing"
            ];
        }
        $church_planters = Disciple_Tools_Counter_Groups::get_groups_count( 'church_planters', $start, $end );
        $total_church_planters = Disciple_Tools_Counter_Groups::get_groups_count( 'church_planters', 0, $end );
        $data[] = [
            "key" => "church_planters",
            "label" => __( "Church Planters", "disciple_tools" ),
            "value" => (int) $church_planters,
            "total" => (int) $total_church_planters,
            "type" => "ongoing"
        ];
        foreach ( $manual_additions as $addition ){
            if ( $addition["section"] == "after") {
                $data[] = [
                    "key" => $addition["source"],
                    "label" => $addition["label"],
                    "total" => $addition["total"],
                    "type" => "ongoing"
                ];
            }
        }

        return $data;
    }

}