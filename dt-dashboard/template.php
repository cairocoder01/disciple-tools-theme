<?php
/*
Template Name: Dashboard
*/
dt_please_log_in();

if ( !current_user_can( 'access_disciple_tools' ) ) {
    wp_safe_redirect( '/registered' );
    exit();
}

$to_accept = DT_Posts::search_viewable_post( 'contacts', [
    'overall_status' => [ 'assigned' ],
    'assigned_to'    => [ 'me' ],
    'type'           => [ 'access' ]
] );

// Phase 4: Stat Tile URLs
$active_contacts_url = Disciple_Tools_Dashboard::get_list_url( 'contacts', [
    'assigned_to' => [ 'me' ],
    'subassigned' => [ 'me' ],
    'combine' => [ 'subassigned' ],
    'type' => [ 'access' ],
    'overall_status' => [ 'active' ],
    'sort' => 'seeker_path'
    ], [
    [ 'name' => __( 'Active', 'disciple_tools' ) ],
    [ 'name' => __( 'Assigned to me', 'disciple_tools' ), 'field' => 'assigned_to', 'id' => 'me' ],
    [ 'name' => __( 'Sub-assigned to me', 'disciple_tools' ), 'field' => 'subassigned', 'id' => 'me' ],
], 'my_active', 'default', __( 'Active', 'disciple_tools' ) );

$update_needed_url = Disciple_Tools_Dashboard::get_list_url( 'contacts', [
    'assigned_to' => [ 'me' ],
    'subassigned' => [ 'me' ],
    'combine' => [ 'subassigned' ],
    'overall_status' => [ 'active' ],
    'requires_update' => [ true ],
    'type' => [ 'access' ],
    'sort' => 'seeker_path'
    ], [
    [ 'name' => __( 'Requires Update', 'disciple_tools' ) ],
    [ 'name' => __( 'Assigned to me', 'disciple_tools' ), 'field' => 'assigned_to', 'id' => 'me' ],
    [ 'name' => __( 'Sub-assigned to me', 'disciple_tools' ), 'field' => 'subassigned', 'id' => 'me' ],
], 'my_update_needed', 'default', __( 'Requires Update', 'disciple_tools' ) );

$contact_attempt_needed_url = Disciple_Tools_Dashboard::get_list_url( 'contacts', [
    'assigned_to' => [ 'me' ],
    'subassigned' => [ 'me' ],
    'combine' => [ 'subassigned' ],
    'overall_status' => [ 'active' ],
    'seeker_path' => [ 'none' ],
    'type' => [ 'access' ],
    'sort' => 'name'
    ], [
    [ 'name' => __( 'Contact Attempt Needed', 'disciple_tools' ) ],
    [ 'name' => __( 'Assigned to me', 'disciple_tools' ), 'field' => 'assigned_to', 'id' => 'me' ],
    [ 'name' => __( 'Sub-assigned to me', 'disciple_tools' ), 'field' => 'subassigned', 'id' => 'me' ],
], 'my_none', 'default', __( 'Contact Attempt Needed', 'disciple_tools' ) );

$active_groups_url = Disciple_Tools_Dashboard::get_list_url( 'groups', [
    'assigned_to' => [ 'me' ],
    'group_status' => [ 'active' ],
    'sort' => '-post_date',
    ], [
    [ 'id' => 'my_active', 'name' => __( 'Active', 'disciple_tools' ) ]
], 'my_active', 'assigned_to_me', __( 'Active', 'disciple_tools' ) );

get_header(); ?>

    <div class="template-dashboard">
        <!-- Pending Contacts -->
        <?php if ( ! empty( $to_accept['posts'] ) ): ?>
        <section id="pending-contacts">
            <div class="title-icon">
                <img src="<?php echo esc_url( get_template_directory_uri() ) . '/dt-assets/images/assigned-to.svg' ?>" alt="">
            </div>
            <h2 class="title-label"><?php esc_html_e( 'Pending Contacts', 'disciple_tools' ) ?></h2>
            <div class="contacts-list">
                <?php
                foreach ( $to_accept['posts'] as $contact ) :
                    set_query_var( 'contact', $contact );
                    get_template_part( 'dt-dashboard/parts/pending', 'contact-card' );
                    endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <section id="dashboard-grid">

            <!-- Phase 3: Your Apps -->
            <section id="your-apps">
                <h2><span class="section-icon mdi mdi-apps"></span> <?php esc_html_e( 'Your Apps', 'disciple_tools' ) ?></h2>
                <div class="apps-grid">
                    <?php
                    $apps = [];
                    if ( class_exists( 'DT_Home_Apps' ) ) {
                        $apps = DT_Home_Apps::instance()->get_apps_for_user( get_current_user_id() );
                    }
                    $apps = apply_filters( 'dt_dashboard_apps', $apps );

                    if ( ! empty( $apps ) ) :
                        foreach ( $apps as $app ) :
                            $url   = esc_url( $app['url'] ?? '#' );
                            $title = esc_html( $app['title'] ?? $app['name'] ?? '' );
                            $icon  = $app['icon'] ?? '';
                            ?>
                            <a class="app-card" href="<?php echo $url; ?>" title="<?php echo $title; ?>">
                                <span class="app-icon-box">
                                    <span class="app-icon <?php echo esc_attr( $icon ); ?>"></span>
                                </span>
                                <span class="app-title"><?php echo $title; ?></span>
                            </a>
                        <?php endforeach;
                    else : ?>
                        <p class="no-apps-message"><?php esc_html_e( 'No apps available', 'disciple_tools' ) ?></p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Phase 5: Contact Workload -->
            <?php $workload_status = get_user_option( 'workload_status', get_current_user_id() ) ?: 'active'; ?>
            <section id="contact-workload">
                <h2><?php esc_html_e( 'Contact Workload', 'disciple_tools' ) ?></h2>
                <p><?php esc_html_e( 'Choose an option to let the dispatcher(s) know if you are ready for new contacts', 'disciple_tools' ) ?></p>
                <div class="workload-options">
                    <button class="workload-btn accepting <?php echo $workload_status === 'active' ? 'selected' : '' ?>" data-status="active">
                        <span class="workload-btn--icon mdi mdi-play"></span>
                        <span class="workload-btn--label"><?php esc_html_e( 'Accepting new contacts', 'disciple_tools' ) ?></span>
                    </button>
                    <button class="workload-btn investing <?php echo $workload_status === 'existing' ? 'selected' : '' ?>" data-status="existing">
                        <span class="workload-btn--icon mdi mdi-pause"></span>
                        <span class="workload-btn--label"><?php esc_html_e( 'I\'m only investing in existing contacts', 'disciple_tools' ) ?></span>
                    </button>
                    <button class="workload-btn too-many <?php echo $workload_status === 'too_many' ? 'selected' : '' ?>" data-status="too_many">
                        <span class="workload-btn--icon mdi mdi-stop"></span>
                        <span class="workload-btn--label"><?php esc_html_e( 'I have too many contacts', 'disciple_tools' ) ?></span>
                    </button>
                </div>
                <a href="<?php echo esc_url( site_url( '/settings/#availability' ) ); ?>" class="travel-link">
                    <span class="icon mdi mdi-briefcase-outline"></span> <?php esc_html_e( 'Set travel or dates unavailable', 'disciple_tools' ) ?>
                </a>
            </section>

            <!-- Phase 4: Stats Tiles -->
            <a class="stat-tile" data-stat="active_contacts" href="<?php echo esc_url( $active_contacts_url ); ?>">
                <span class="stat-label"><?php esc_html_e( 'Active Contacts', 'disciple_tools' ) ?></span>
                <span class="stat-count">—</span>
                <span class="stat-link"><?php esc_html_e( 'See all >', 'disciple_tools' ) ?></span>
            </a>
            <a class="stat-tile" data-stat="update_needed" href="<?php echo esc_url( $update_needed_url ); ?>">
                <span class="stat-label"><?php esc_html_e( 'Requires Update', 'disciple_tools' ) ?></span>
                <span class="stat-count">—</span>
                <span class="stat-link"><?php esc_html_e( 'See all >', 'disciple_tools' ) ?></span>
            </a>
            <a class="stat-tile" data-stat="contact_attempt_needed" href="<?php echo esc_url( $contact_attempt_needed_url ); ?>">
                <span class="stat-label"><?php esc_html_e( 'Contact Needed', 'disciple_tools' ) ?></span>
                <span class="stat-count">—</span>
                <span class="stat-link"><?php esc_html_e( 'See all >', 'disciple_tools' ) ?></span>
            </a>
            <a class="stat-tile" data-stat="active_groups" href="<?php echo esc_url( $active_groups_url ); ?>">
                <span class="stat-label"><?php esc_html_e( 'Active Groups', 'disciple_tools' ) ?></span>
                <span class="stat-count">—</span>
                <span class="stat-link"><?php esc_html_e( 'See all >', 'disciple_tools' ) ?></span>
            </a>

            <!-- Phase 6: Contacts (Recently Updated) -->
            <div id="recent-contacts" class="dashboard-card post-list"></div>

            <!-- Phase 7: Groups (Recently Updated) -->
            <div id="recent-groups" class="dashboard-card post-list"></div>

            <!-- Phase 9: Faith Milestone Totals -->
            <div id="faith-milestones" class="dashboard-card"></div>

            <!-- Phase 10: Seeker Path Progress -->
            <div id="seeker-path" class="dashboard-card"></div>

            <!-- Phase 8: Tasks -->
            <div id="tasks" class="dashboard-card"></div>

        </section>

    </div> <!-- end .template-dashboard -->

<?php get_footer(); ?>
