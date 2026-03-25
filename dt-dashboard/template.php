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
            <div id="contact-workload" class="dashboard-card"></div>

            <!-- Phase 4: Stats Tiles -->
            <div id="stats-tiles"></div>

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
