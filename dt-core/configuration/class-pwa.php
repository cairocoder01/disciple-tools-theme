<?php
/**
 * Disciple_Tools PWA to add Progressive Web App features to D.T
 *
 * Current Features Implemented:
 * - Add to Home Screen
 *
 * Possible Future Features:
 * - Push notifications
 * - Offline access
 *
 * @class   Disciple_Tools_PWA
 * @version 1.0.0
 * @since   1.67.0
 * @package Disciple.Tools
 *
 */

/**
 * Class Disciple_Tools_PWA
 */
class Disciple_Tools_PWA
{
    /**
     * The single instance of Disciple_Tools_PWA
     *
     * @var    object
     * @access private
     * @since  1.0.0
     */
    private static $_instance = null;

    /**
     * Main Disciple_Tools_PWA Instance
     * Ensures only one instance of Disciple_Tools_PWA is loaded or can be loaded.
     *
     * @since  1.0.0
     * @static
     * @return Disciple_Tools_PWA instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    } // End instance()

    public function __construct()
    {
        add_action( 'parse_request', [ $this, 'parse_request' ], 999 );

        add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 999 );
    }

    public function parse_request()
    {
        if ( !isset( $_SERVER['REQUEST_URI'] ) ) {
            return;
        }
        $uri = dt_get_url_path( true );

        if ( 'manifest.json' === $uri ) {
            $this->print_manifest_json();
            return;
        }

        if ( 'service-worker.js' === $uri ) {
            $this->print_service_worker();
            return;
        }
    }

    /**
     * Handle "/service-worker.js" request.
     *
     * @since 1.0.0
     */
    private function print_service_worker() {

        header( 'Content-Type: application/javascript; charset=utf-8' );
        echo file_get_contents( $_SERVER['DOCUMENT_ROOT'].'/wp-content/themes/disciple-tools-theme/dt-assets/js/service-worker.js' );
        
        exit;
    }

    /**
     * Handle "/manifest.json" request.
     *
     * @since 1.0.0
     */
    private function print_manifest_json() {

        $instance_name = get_bloginfo( 'name' );
        $instance_desc = get_bloginfo( 'description' );

        $data = array(
            'id'               => home_url(),
            'name'             => $instance_name,
            'short_name'       => $instance_name,
            'description'      => $instance_desc,
            'theme_color'      => '#3f729b',
            'background_color' => '#3f729b',
            'orientation'      => 'portrait',
            'display'          => 'minimal-ui',
            'scope'            => '/',
            'start_url'        => '/',
            'icons'            => [
                [
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'src' => esc_url( get_template_directory_uri() ) . '/dt-assets/favicons/android-chrome-192x192.png',
                ],
                [
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'src' => esc_url( get_template_directory_uri() ) . '/dt-assets/favicons/android-chrome-512x512.png',
                ],
            ],
            'shortcuts'        => [],
            'categories'       => [ 'productivity', 'utilities' ],
        );

        // add shortcuts for all main menu items
        $dt_nav_tabs = dt_default_menu_array();
        foreach ( $dt_nav_tabs['main'] as $dt_main_tabs ) {
            if ( ! ( isset( $dt_main_tabs['hidden'] ) && $dt_main_tabs['hidden'] ) ) {
                $data['shortcuts'][] = [
                    'name' => $dt_main_tabs['label'],
                    'url' => esc_url( $dt_main_tabs['link'] ),
                    'icons' => [
                        [
                            'sizes' => '96x96',
                            'src' => esc_url( get_template_directory_uri() ) . '/dt-assets/favicons/android-chrome-96x96.png',
                        ],
                    ],
                ];
            }
        }

        header( 'Content-Type: application/json' );

        // remove empty values
        foreach ( $data as $key => $value ) {
            if ( empty( $value ) ) {
                unset( $data[ $key ] );
            }
        }

        /**
         * filter manifest data
         */
        $data = apply_filters(
            'disciple_tools_manifest_data',
            $data
        );
        echo json_encode( $data );
        exit;
    }

    public function scripts() {
        // to add a custom install prompt, include this js.
        dt_theme_enqueue_script( 'pwa', 'dt-assets/js/pwa.js' );
        wp_localize_script(
            'pwa', 'wpPwa', array(
                'translations' => [
                    'created_title' => __( 'New Contact Created', 'disciple_tools' ),
                    'created_body' => __( 'A new contact was created and assigned to you.', 'disciple_tools' ),
                    'assigned_to_title' => __( 'New Contact Assigned', 'disciple_tools' ),
                    'assigned_to_body' => _x( 'You have been assigned a new contact.', 'Empty list results. Keep {{query}} as is in english', 'disciple_tools' ),
                    'assigned_to_other_title' => __( 'Contact Reassigned', 'disciple_tools' ),
                    'assigned_to_other_body' => __( 'A contact has been reassigned.', 'disciple_tools' ),
                    'share_title' => __( 'Contact Shared', 'disciple_tools' ),
                    'share_body' => __( 'A contact has been shared with you.', 'disciple_tools' ),
                    'mention_title' => __( 'New Mention', 'disciple_tools' ),
                    'mention_body' => __( 'You were mentioned on a contact.', 'disciple_tools' ),
                    'comment_title' => __( 'New Comment', 'disciple_tools' ),
                    'comment_body' => __( 'A new comment was left on a contact.', 'disciple_tools' ),
                    'subassigned_title' => __( 'New Contact Subassigned', 'disciple_tools' ),
                    'subassigned_body' => __( 'A new contact has been subassigned to you.', 'disciple_tools' ),
                    'milestone_title' => __( 'New Milestone', 'disciple_tools' ),
                    'milestone_body' => __( 'A new milestone was added to a contact.', 'disciple_tools' ),
                    'requires_update_title' => __( 'Update Required', 'disciple_tools' ),
                    'requires_update_body' => __( 'A contact requires an update.', 'disciple_tools' ),
                    'contact_info_update_title' => __( 'Contact Updated', 'disciple_tools' ),
                    'contact_info_update_body' => __( 'A contact\'s details were modified.', 'disciple_tools' ),
                    'assignment_declined_title' => __( 'User Declined Assignment' ),
                    'assignment_declined_body' => __( 'A user declined assignment on a contact.', 'disciple_tools' ),
                    'action_title' => __( 'Open' ),
                ],
            )
        );
    }

}