<?php
declare(strict_types=1);
?>
<div class="bordered-box list-box">
    <h5 class="hide-for-small-only" style="display: inline-block"><?php esc_html_e( "Groups", "disciple_tools" ); ?></h5>
    <div style="display: inline-block" class="loading-spinner active"></div>
    <span style="display: inline-block" class="filter-result-text"></span>
    <div style="display: inline-block" id="current-filters"></div>
    <div class="js-sort-dropdown" style="display: inline-block">
        <ul class="dropdown menu" data-dropdown-menu>
            <li>
                <a href="#"><?php esc_html_e( "Sort", "disciple_tools" ); ?></a>
                <ul class="menu">
                    <li><a href="#" class="js-sort-by" data-column-index="6" data-order="desc" data-field="post_date">
                        <?php esc_html_e( "Newest", "disciple_tools" ); ?>
                    </a></li>
                    <li><a href="#" class="js-sort-by" data-column-index="6" data-order="asc" data-field="post_date">
                        <?php esc_html_e( "Oldest", "disciple_tools" ); ?>
                    </a></li>
                    <li><a href="#" class="js-sort-by" data-column-index="6" data-order="desc" data-field="last_modified">
                        <?php esc_html_e( "Most recently modified", "disciple_tools" ); ?>
                    </a></li>
                    <li><a href="#" class="js-sort-by" data-column-index="6" data-order="asc" data-field="last_modified">
                        <?php esc_html_e( "Least recently modified", "disciple_tools" ); ?>
                    </a></li>
                </ul>
            </li>
        </ul>
    </div>
    <div class="show-closed-switch">
        <?php esc_html_e( "Inactive Groups", 'disciple_tools' ) ?>
        <div class="switch tiny">
            <input class="switch-input" id="show_closed" type="checkbox" name="testGroup">
            <label class="switch-paddle" for="show_closed"></label>
        </div>
    </div>
    <table class="table-remove-top-border js-list stack striped">
        <thead>
            <tr class="sortable">
                <th data-id="name" data-priority="2"><?php esc_html_e( "Name", "disciple_tools" ); ?></th>
                <th data-id="group_status" data-sort="asc"><?php esc_html_e( "Status", "disciple_tools" ); ?></th>
                <th data-id="group_type"><?php esc_html_e( "Type", "disciple_tools" ); ?></th>
                <th data-id="members"><?php esc_html_e( "Members", "disciple_tools" ); ?></th>
                <th data-id="leaders"><?php esc_html_e( "Leader", "disciple_tools" ); ?></th>
                <th data-id="location_grid"><?php esc_html_e( "Location", "disciple_tools" ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr class="js-list-loading"><td colspan=7><?php esc_html_e( "Loading...", "disciple_tools" ); ?></td></tr>
        </tbody>
    </table>
    <div class="center">
        <button id="load-more" class="button"><?php esc_html_e( "Load more groups", 'disciple_tools' ) ?></button>
    </div>
</div>

