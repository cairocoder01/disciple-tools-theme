# Dashboard Implementation Plan

Based on the mobile and desktop mockups, the existing code in `dt-dashboard/template.php`, `dt-dashboard/dashboard.php`, and the SCSS file `dt-assets/scss/_dashboard.scss`, here is a phased implementation plan.

> **Note:** The current URL `/dashboard2` is temporary to avoid conflicts during development. All references below use "dashboard" as the final name. The slug will be changed from `dashboard2` to `dashboard` in the final phase.

### Design Mockups

Refer to these mockup images in the project root for the visual design targets:

- **Mobile layout:** [`mobile.png`](../mobile.png)
- **Desktop layout:** [`desktop.png`](../desktop.png)

All sections described below should match these mockups as closely as possible. When in doubt, reference the mockups for spacing, colors, typography, and responsive behavior.

### Key Files

| File | Purpose |
|------|---------|
| `dt-dashboard/template.php` | The PHP template that renders the dashboard HTML. Uses `get_header()` / `get_footer()` and wraps content in `.template-dashboard`. |
| `dt-dashboard/dashboard.php` | The `Disciple_Tools_Dashboard` singleton class. Handles URL routing (via `dt_templates_for_urls` filter), script/style enqueueing, and navbar registration. |
| `dt-assets/scss/_dashboard.scss` | SCSS partial for all dashboard styles. Currently contains initial `#pending-contacts` styles (grid layout, icon, title). This file is compiled as part of the theme's main SCSS build. |

### Coding Conventions

- **PHP:** Follow WordPress coding standards. Use `esc_html__( 'String', 'disciple_tools' )` for all user-facing strings (internationalization). Escape all output with `esc_html()`, `esc_attr()`, `esc_url()`, etc.
- **SCSS:** Nest styles under `.template-dashboard` to scope them. Use existing CSS custom properties (e.g., `var(--alert-color)`, `var(--text-color-inverse)`) from the theme rather than hardcoded colors. Check `dt-assets/scss/` for available variables.
- **JS:** Use `window.SHAREDFUNCTIONS` and `window.API` utilities already available in D.T for making REST API calls (e.g., `window.API.get_post()`, `jQuery.ajax()` with `window.wpApiShare.nonce`). Localize script data via `wp_localize_script()`. Avoid usage of jQuery in favor of native browser APIs.
- **REST API:** Register endpoints under the `dt/v1` namespace using `register_rest_route()`. Use `dt_has_permissions()` or capability checks for authorization.

---

### Phase 1: Foundation & Layout Scaffolding

**Goal:** Set up the responsive grid layout within the existing SCSS and JS architecture so that subsequent phases can drop sections into place.

- [x] **1.1** Expand `dt-assets/scss/_dashboard.scss` to define the top-level `.template-dashboard` responsive grid layout:
    - Desktop (≥1024px): Use CSS Grid with named areas or a multi-column layout. The desktop mockup shows rows with varying column counts (full-width banner, 2-column middle sections, 3-column bottom row, 4-column stats row).
    - Tablet (768px–1023px): 2-column layout where possible, stacking to 1 column for complex sections.
    - Mobile (<768px): Single-column stacked layout matching the mobile mockup.
    - Add a shared `.dashboard-card` class for the white rounded-corner card style used by most sections (subtle `box-shadow`, `border-radius: 8px`, `padding: 1.5rem`, white background).
- [x] **1.2** Create `dt-dashboard/dashboard.js` and enqueue it in `dashboard.php` → `scripts()` method using `wp_enqueue_script()`. Set `in_footer` to `true`. Add `wp_localize_script()` to pass `dtDashboard` object with: `rest_url` (from `rest_url('dt/v1/')`), `nonce` (from `wp_create_nonce('wp_rest')`), `current_user_id`, and `translations` object.
- [x] **1.3** Update `template.php` to contain the full HTML skeleton with empty semantic section containers:
    - `<section id="pending-contacts">` (already exists — keep and extend)
    - `<section id="your-apps">`
    - `<section id="contact-workload">`
    - `<section id="stats-tiles">`
    - `<section id="recent-contacts">`
    - `<section id="recent-groups">`
    - `<section id="faith-milestones">`
    - `<section id="seeker-path">`
    - `<section id="tasks">`
    - Each section should have a comment indicating which phase implements it.
- [x] **1.4** Create `dt-dashboard/endpoints.php` with a `Disciple_Tools_Dashboard_Endpoints` class. Register it via `rest_api_init` action in `dashboard.php`. Initially, the class can be a skeleton with empty route registrations that will be filled in by later phases.

---

### Phase 2: Pending Contacts Section (Top Banner)

**Goal:** Full-width orange/blue banner at top of page with horizontally scrollable contact cards. SCSS foundation already exists in `_dashboard.scss` (lines 2–25 define the grid layout, icon styling, and title).

**Visual reference:** Top section of both mockups — orange background, large semi-transparent contact icon on left, "Pending Contacts" title, and white contact cards.

- [x] **2.1** Extend `#pending-contacts` styles in `_dashboard.scss`:
    - Add `.contacts-list` as a horizontal flex container with `overflow-x: auto` for scrolling and `gap: 1rem` between cards.
    - Style `.contact-card` with white background, rounded corners, padding, and min-width (~250px) so cards don't collapse.
    - Style action buttons: `.btn-accept` (green, matching theme `--success-color`), `.btn-decline` (red/dark), `.btn-details` (outline/secondary).
    - On mobile: cards scroll horizontally; on desktop: cards sit side-by-side (wrapping if many).
- [x] **2.2** Update HTML in `template.php` `#pending-contacts` section. The `.contacts-list` div is already present — cards will be injected here by JS. Add a loading placeholder (e.g., `<div class="loading-spinner"></div>`) inside `.contacts-list`.
- [x] **2.3** Each contact card should display (rendered by JS):
    - **Name** (bold, larger font)
    - **Location** (city, region, country — from contact's `location_grid_meta`)
    - **Gender** (from `gender` field)
    - **Age range** (from `age` field, e.g., "18-25 years old")
    - Three buttons: **Accept** (calls `POST /dt/v1/contact/{id}` to update `overall_status` to `active` and `assigned_to` to current user), **Decline** (updates `overall_status` to `unassigned` or removes assignment), **See Details** (navigates to `/contacts/{id}`).
- [x] **2.4** Add REST endpoint in `endpoints.php`: `GET dt/v1/dashboard/pending-contacts`
    - Query contacts where `assigned_to` is current user AND `overall_status` is one of: `unassigned`, `assigned` (pending acceptance). Use `DT_Posts::list_posts('contacts', ...)` with appropriate filters.
    - Return array of objects with: `id`, `name`, `location` (formatted string), `gender`, `age`, `permalink`.
    - Require `access_disciple_tools` capability.
- [x] **2.5** In `dashboard.js`: On page load, fetch pending contacts and render cards into `.contacts-list`. Wire up button click handlers. On Accept/Decline success, remove the card with a fade-out animation. If no pending contacts, show a friendly "No pending contacts" message.
- [x] **2.6** Hide the entire `#pending-contacts` section if the user has no pending contacts (check after fetch, remove section or add `.hidden` class).

---

### Phase 3: Your Apps Section

**Goal:** Row of icon buttons for quick navigation to frequently used pages. See the "Your Apps" row in both mockups — grid icon, list icons, group icon, plus icon, flag icon.

- [x] **3.1** Add `#your-apps` HTML structure to `template.php`:
    ```html
    <section id="your-apps">
        <h2><span class="grid-icon"></span> Your Apps</h2>
        <div class="apps-grid"></div>
    </section>
    ```
- [x] **3.2** Add styles to `_dashboard.scss` (reference `dt-apps/dt-home/assets/css/home-screen.css` for shared patterns):

    #### Styling notes (from `dt-apps/dt-home` CSS)

    **Container — horizontal scroll, no wrapping:**
    The dashboard apps row must **never wrap** to a second line. Use a horizontal scroll container:
    ```css
    .apps-grid {
        display: flex;
        flex-direction: row;
        gap: 1rem;
        overflow-x: auto;
        overflow-y: hidden;
        flex-wrap: nowrap;           /* never wrap to second line */
        padding: 0.5rem 10px;        /* 10px padding for gradient fade */
        margin-inline: -10px;        /* Pull back to align with header */
        width: calc(100% + 20px);
        box-sizing: border-box;
        scrollbar-width: thin;       /* Firefox: subtle scrollbar */
        -webkit-overflow-scrolling: touch; /* iOS momentum scrolling */

        /* Fade out overflow at ends */
        mask-image: linear-gradient(to right, transparent, black 10px, black calc(100% - 10px), transparent);
        -webkit-mask-image: linear-gradient(to right, transparent, black 10px, black calc(100% - 10px), transparent);
    }
    .apps-grid::-webkit-scrollbar {
        height: 4px;
    }
    .apps-grid::-webkit-scrollbar-thumb {
        background: var(--border-color, #e1e5e9);
        border-radius: 2px;
    }
    ```
    > **Note:** The dt-home screen uses `display: grid` with `repeat(auto-fit, minmax(75px, 1fr))` because it's the main app launcher and benefits from a reflowing grid. The dashboard "Your Apps" section is a *summary row* — it should use `display: flex` with `flex-wrap: nowrap` and `overflow-x: auto` so that excess apps scroll horizontally rather than wrapping to a new line.

    **App card (shared from dt-home `home-screen.css` lines 393–423):**
    ```css
    .app-card {
        background: var(--app-card-bg, #ffffff);
        border: 1px solid var(--app-card-border, #e1e5e9);
        border-radius: 24px;
        padding: 0.6rem;
        text-align: center;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        aspect-ratio: 1;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        width: 75px;
        flex-shrink: 0;             /* prevent cards from shrinking in flex row */
        transition: all 0.2s ease;
    }
    .app-card:hover {
        transform: scale(1.05);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border-color: var(--primary-color, #3f729b);
    }
    ```

    **App icon (shared from dt-home `home-screen.css` lines 425–435):**
    ```css
    .app-icon {
        font-size: 2.1rem;
        color: var(--app-icon-default, #0a0a0a);
        display: flex;
        align-items: center;
        justify-content: center;
        width: 48px;
        height: 48px;
        transition: all 0.2s ease;
    }
    ```

    **App title (shared from dt-home `home-screen.css` lines 437–451):**
    ```css
    .app-title {
        font-size: 0.66rem;
        font-weight: 500;
        color: var(--text-color, #0a0a0a);
        line-height: 1.2;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 75px;
        width: 100%;
        text-align: center;
    }
    ```

    **CSS variables (reuse from dt-home or define locally):**
    The dt-home stylesheet defines these variables that the dashboard should share or mirror:
    ```css
    :root {
        --app-card-bg: #ffffff;
        --app-card-border: #e1e5e9;
        --app-card-text: #0a0a0a;
        --app-icon-default: #0a0a0a;
        --primary-color: #3f729b;
        --border-color: #e1e5e9;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
        --shadow-md: 0 2px 8px rgba(0,0,0,0.1);
    }
    /* Dark mode overrides (from dt-home body.theme-dark) */
    body.theme-dark {
        --app-card-bg: #2a2a2a;
        --app-card-border: #404040;
        --app-icon-default: #ffffff;
        --primary-color: #4a9eff;
    }
    ```

    **Responsive:** On larger screens (≥768px) the dt-home module switches to wider cards with side-by-side icon+label layout. For the dashboard summary row, keep the compact 75px square cards at all breakpoints since horizontal scroll handles overflow.
- [x] **3.3** Define the default apps list in PHP (in `dashboard.php` or a helper function) as an array of `['slug' => ..., 'label' => ..., 'icon' => ..., 'url' => ...]`. Default apps based on mockup:
    - Home Screen → `/` (grid icon)
    - User Contact List → `/contacts` (list icon)
    - User Group List → `/groups` (people icon)
    - Create Contact → `/contacts/new` (plus icon)
    - My Coached Contacts → `/contacts?filter=coached_by_me` (flag icon — desktop only, or show on all)
- [x] **3.4** Pass the apps list through a WordPress filter: `$apps = apply_filters( 'dt_dashboard_apps', $default_apps );` and then pass to JS via `wp_localize_script()` or render directly in PHP.
- [x] **3.5** Render app icons in JS or directly in PHP template. Each app is an `<a>` tag linking to its URL.
    - **Implementation details** (based on the in-theme `dt-apps/dt-home` module — see `dt-apps/dt-home/includes/class-home-apps.php`):

    > **Note:** The standalone `dt-home` plugin (`disciple-tools-home-screen`) is **deprecated**. Its functionality has been reimplemented directly in the D.T theme at `dt-apps/dt-home/`. The dashboard should use the in-theme `DT_Home_Apps` class — no external plugin or DI container needed.

    #### How the in-theme dt-home module manages apps (`DT_Home_Apps`)
    The `DT_Home_Apps` singleton class (at `dt-apps/dt-home/includes/class-home-apps.php`) provides a complete app management system with multiple sources, admin customization, role-based permissions, and magic-link URL hydration. The dashboard "Your Apps" section should **directly use `DT_Home_Apps`** since it's part of the theme.

    #### App data structure
    Each app in `DT_Home_Apps` is an associative array with these keys:
    ```php
    [
        'id'             => 'contacts',         // Unique identifier (same as slug for coded apps)
        'slug'           => 'contacts',         // Unique slug
        'creation_type'  => 'coded',            // 'coded' (from filter/magic-link) or 'custom' (admin-created)
        'type'           => 'app',              // 'app' (magic-link/coded) or 'link' (simple URL)
        'title'          => 'Contacts',         // Display label
        'description'    => '',                 // App description
        'url'            => '/contacts',         // Target URL (hydrated with magic-link key for coded apps)
        'icon'           => 'mdi mdi-contacts', // MDI icon class or URL
        'color'          => '',                 // Custom hex color (empty = default)
        'enabled'        => true,               // Whether app is enabled
        'order'          => 10,                 // Sort order (lower = first)
        'roles'          => [],                 // Role-based access restrictions
        'user_roles_type' => '',                // Role restriction type
        'magic_link_meta' => [                  // Only for coded magic-link apps
            'post_type' => 'user',
            'root'      => 'apps',
            'type'      => 'contacts',
            'meta_key'  => 'apps_contacts_magic_key',
        ],
    ]
    ```

    #### App sources (layered architecture)
    `DT_Home_Apps` aggregates apps from multiple sources during the `init` hook (priority 20):
    1. **Magic Link Apps** (`load_magic_link_apps()`) — auto-discovered from all registered magic link types via `dt_magic_url_register_types` filter. Only apps with `meta.show_in_home_apps = true` or `templates/contacts` type are included. Stored in `$this->ml_apps`.
    2. **Home Apps** (`load_home_apps()`) — coded apps registered by plugins/themes via the `dt_home_apps` WordPress filter. Stored in `$this->home_apps`.
    3. **Database Apps** (`get_option('dt_home_apps')`) — admin-configured apps and customizations stored in `wp_options`. For coded apps, only customization fields (icon, color, enabled, order, roles) are stored and merged back. Full custom apps are stored entirely.

    Apps from all sources are merged by `id` in `get_all_apps()`, with database customizations overlaid on coded app defaults.

    #### Retrieving apps for the current user
    Use `DT_Home_Apps::instance()->get_apps_for_user( $user_id )` — this is the primary method:
    ```php
    $apps_manager = DT_Home_Apps::instance();
    $apps = $apps_manager->get_apps_for_user( get_current_user_id() );
    ```
    This method:
    1. Calls `get_apps_for_frontend()` which merges all app sources, filters to enabled apps only, and hydrates magic-link URLs (resolves the user's magic key via `get_user_option()` and builds full URLs via `DT_Magic_URL::get_link_url()`)
    2. Filters out apps the user doesn't have permission to access (role-based via `DT_Home_Roles_Permissions::filter_apps_by_permissions()`)
    3. Sorts by `order` field

    The magic-link-home-app also exposes a REST endpoint at `GET /apps/v1/launcher?action=get_apps` which returns the app list (see `magic-link-home-app.php` line 509), but for the dashboard we can call the PHP method directly since it's in-theme.

    #### Registering apps via the `dt_home_apps` filter
    Plugins and themes add apps using the `dt_home_apps` filter (consumed by `DT_Home_Apps::load_home_apps()`):
    ```php
    add_filter( 'dt_home_apps', function ( $apps ) {
        $apps[] = [
            'name' => 'Contacts',
            'type' => 'link',           // 'app' or 'link'
            'icon' => 'mdi mdi-contacts',
            'url'  => site_url( '/contacts' ),
            'sort' => 10,
            'slug' => 'contacts',
        ];
        $apps[] = [
            'name' => 'Groups',
            'type' => 'link',
            'icon' => 'mdi mdi-account-group',
            'url'  => site_url( '/groups' ),
            'sort' => 20,
            'slug' => 'groups',
        ];
        return $apps;
    } );
    ```

    #### Recommended approach for the dashboard
    Since `DT_Home_Apps` is now part of the theme (not a separate plugin), the dashboard can **always** use it directly — no need to check if a plugin is installed:
    ```php
    // In the dashboard template or endpoint:
    $apps_manager = DT_Home_Apps::instance();
    $apps = $apps_manager->get_apps_for_user( get_current_user_id() );

    // Pass to JS via wp_localize_script() or render directly in PHP template
    wp_localize_script( 'dt-dashboard', 'dtDashboard', [
        'apps' => $apps,
        // ... other dashboard data
    ] );
    ```
    This gives the dashboard the same app list as the home screen, including admin customizations, role-based filtering, and magic-link URL hydration — with zero duplication.

    #### Performance note
    Retrieving the app list is lightweight — `get_all_apps()` reads from `wp_options` (one row, auto-loaded by WordPress) and merges with in-memory coded apps. The `dt_home_apps` and `dt_magic_url_register_types` filters run PHP callbacks in memory. Magic-link URL hydration calls `get_user_option()` per coded app (cached after first read). No database joins or expensive queries are involved. The entire operation is suitable for synchronous rendering in the PHP template (no need for a separate REST endpoint). If rendering via JS, pass the apps array via `wp_localize_script()` to avoid an extra HTTP round-trip.

---

### Phase 4: Stats Tiles Row

**Goal:** Four summary count tiles in a row, each showing a metric name, large number, and "See all >" link. See both mockups — the row of 4 white cards below the apps section.

| Tile | Description | "See all" Link (Refined) |
|------|-------------|-------------------------|
| Active Contacts | Count of user's contacts with `overall_status` = `active` | `/contacts?filter_id=my_active&query=...&labels=...` |
| Update Needed | Contacts with `requires_update` = `true` | `/contacts?filter_id=my_update_needed&query=...&labels=...` |
| Contact Attempt Needed | Contacts with `seeker_path` = `none` and status `active` | `/contacts?filter_id=my_none&query=...&labels=...` |
| Active Groups | Count of user's groups with `group_status` = `active` | `/groups?filter_id=my_active&query=...&labels=...` |

> **Note on Deep Linking:** D.T list pages use base64-encoded JSON for `query` and `labels` parameters. To ensure the link correctly applies the filter and shows the right labels in the UI, use the `Disciple_Tools_Dashboard::get_list_url()` helper (see implementation in `dashboard.php`).

#### Deep Link Query/Label Structures

**Active Contacts**
- **Query:** `{"assigned_to":["me"],"subassigned":["me"],"combine":["subassigned"],"type":["access"],"overall_status":["active"],"sort":"seeker_path"}`
- **Labels:** `[{"name":"Active"},{"name":"Assigned to me","field":"assigned_to","id":"me"},{"name":"Sub-assigned to me","field":"subassigned","id":"me"}]`

**Update Needed**
- **Query:** `{"assigned_to":["me"],"subassigned":["me"],"combine":["subassigned"],"overall_status":["active"],"requires_update":[true],"type":["access"],"sort":"seeker_path"}`
- **Labels:** `[{"name":"Update Needed"},{"name":"Assigned to me","field":"assigned_to","id":"me"},{"name":"Sub-assigned to me","field":"subassigned","id":"me"}]`

**Contact Attempt Needed**
- **Query:** `{"assigned_to":["me"],"subassigned":["me"],"combine":["subassigned"],"overall_status":["active"],"seeker_path":["none"],"type":["access"],"sort":"name"}`
- **Labels:** `[{"name":"Contact Attempt Needed"},{"name":"Assigned to me","field":"assigned_to","id":"me"},{"name":"Sub-assigned to me","field":"subassigned","id":"me"}]`

**Active Groups**
- **Query:** `{"assigned_to":["me"],"group_status":["active"]}`
- **Labels:** `[{"name":"Active"}]`

- [x] **4.1** Add `#stats-tiles` HTML to `template.php`:
    ```html
    <section id="stats-tiles">
        <div class="stats-grid">
            <div class="stat-tile" data-stat="active_contacts">
                <span class="stat-label">Active Contacts</span>
                <span class="stat-count">—</span>
                <a class="stat-link" href="/contacts?status=active">See all &gt;</a>
            </div>
            <!-- repeat for other 3 tiles -->
        </div>
    </section>
    ```
- [x] **4.2** Add styles to `_dashboard.scss`: `.stats-grid` uses CSS Grid with `grid-template-columns: repeat(4, 1fr)` on desktop, `repeat(2, 1fr)` on mobile. Each `.stat-tile` is a white card (use `.dashboard-card` mixin/class), centered text, `.stat-count` in large bold font (~2.5rem), `.stat-label` smaller above it, `.stat-link` smaller below in theme link color.
- [x] **4.3** Add REST endpoint: `GET dt/v1/dashboard/stats`
    - Require `access_disciple_tools` capability.
    - Return: `{ "active_contacts": 12, "update_needed": 9, "contact_attempt_needed": 2, "active_groups": 2 }`.
    - **Implementation details per stat** (based on the previous `disciple-tools-dashboard` plugin's `rest-api.php`):

    #### Active Contacts
    The previous plugin used a direct `$wpdb` count query for performance (see `get_active_contacts()` in the old plugin). This is the recommended approach:
    ```php
    $active_contacts = $wpdb->get_var( $wpdb->prepare( "
        SELECT count(a.ID)
        FROM $wpdb->posts as a
        INNER JOIN $wpdb->postmeta as assigned_to
            ON a.ID = assigned_to.post_id
            AND assigned_to.meta_key = 'assigned_to'
            AND assigned_to.meta_value = CONCAT( 'user-', %s )
        JOIN $wpdb->postmeta as b
            ON a.ID = b.post_id
            AND b.meta_key = 'overall_status'
            AND b.meta_value = 'active'
        WHERE a.post_status = 'publish'
            AND post_type = 'contacts'
            AND a.ID NOT IN (
                SELECT post_id FROM $wpdb->postmeta
                WHERE meta_key = 'type' AND meta_value = 'user'
                GROUP BY post_id
            )
    ", get_current_user_id() ) );
    ```
    Key points: filters to `assigned_to = 'user-{current_user_id}'`, `overall_status = 'active'`, `post_type = 'contacts'`, excludes contacts where `type = 'user'` (user-type contacts are internal D.T records, not real contacts).

    #### Update Needed
    The previous plugin used `DT_Posts::search_viewable_post()` (now `DT_Posts::list_posts()`):
    ```php
    $update_needed = DT_Posts::list_posts( 'contacts', [
        'requires_update' => [ 'true' ],
        'assigned_to'     => [ 'me' ],
        'overall_status'  => [ '-closed' ],
        'sort'            => 'last_modified',
        'limit'           => 0,
    ] );
    $update_needed_count = $update_needed['total'] ?? 0;
    ```
    Alternatively, a direct `$wpdb` count query joining on `meta_key = 'requires_update'` with `meta_value = 'yes'` would be more performant if only the count is needed.

    #### Contact Attempt Needed
    This stat was not in the previous plugin. Query contacts assigned to the current user where `seeker_path = 'none'` and `overall_status = 'active'`:
    ```php
    $contact_attempt_needed = DT_Posts::list_posts( 'contacts', [
        'seeker_path'    => [ 'none' ],
        'overall_status' => [ 'active' ],
        'assigned_to'    => [ 'me' ],
        'limit'          => 0,
    ] );
    $contact_attempt_count = $contact_attempt_needed['total'] ?? 0;
    ```
    Or use a direct `$wpdb` count query for better performance:
    ```php
    $contact_attempt_count = $wpdb->get_var( $wpdb->prepare( "
        SELECT count(a.ID)
        FROM $wpdb->posts as a
        INNER JOIN $wpdb->postmeta as assigned_to
            ON a.ID = assigned_to.post_id
            AND assigned_to.meta_key = 'assigned_to'
            AND assigned_to.meta_value = CONCAT( 'user-', %s )
        JOIN $wpdb->postmeta as status
            ON a.ID = status.post_id
            AND status.meta_key = 'overall_status'
            AND status.meta_value = 'active'
        JOIN $wpdb->postmeta as seeker
            ON a.ID = seeker.post_id
            AND seeker.meta_key = 'seeker_path'
            AND seeker.meta_value = 'none'
        WHERE a.post_status = 'publish'
            AND post_type = 'contacts'
            AND a.ID NOT IN (
                SELECT post_id FROM $wpdb->postmeta
                WHERE meta_key = 'type' AND meta_value = 'user'
                GROUP BY post_id
            )
    ", get_current_user_id() ) );
    ```

    #### Active Groups
    This stat was not in the previous plugin. Use the same direct query pattern but for groups:
    ```php
    $active_groups = $wpdb->get_var( $wpdb->prepare( "
        SELECT count(a.ID)
        FROM $wpdb->posts as a
        INNER JOIN $wpdb->postmeta as assigned_to
            ON a.ID = assigned_to.post_id
            AND assigned_to.meta_key = 'assigned_to'
            AND assigned_to.meta_value = CONCAT( 'user-', %s )
        JOIN $wpdb->postmeta as status
            ON a.ID = status.post_id
            AND status.meta_key = 'group_status'
            AND status.meta_value = 'active'
        WHERE a.post_status = 'publish'
            AND post_type = 'groups'
    ", get_current_user_id() ) );
    ```
    Note: Groups use `group_status` instead of `overall_status`, and the `type != 'user'` exclusion is not needed for groups.

    #### Performance note
    The previous plugin favored direct `$wpdb` queries over `DT_Posts` API calls for count-only stats. This is recommended here as well — `DT_Posts::list_posts()` with `limit=0` still loads all matching post data which is wasteful when only a count is needed. Direct SQL count queries are significantly faster, especially for users with many contacts. Consider combining multiple counts into a single endpoint call to reduce HTTP overhead (the old plugin's `get_other_stats()` method bundled multiple stats into one response).
- [x] **4.4** In `dashboard.js`: Fetch stats on page load, populate each `.stat-count` by matching `data-stat` attribute. Show "—" or a spinner while loading.

---

### Phase 5: Contact Workload Section

**Goal:** Let user set their availability status for dispatchers. See the "Contact Workload" section in desktop mockup (right side, next to Your Apps) — three colored toggle buttons and a travel link.

- [ ] **5.1** Add `#contact-workload` HTML to `template.php`:
    ```html
    <section id="contact-workload">
        <h2>Contact Workload</h2>
        <p>Choose an option to let the dispatcher(s) know if you are ready for new contacts</p>
        <div class="workload-options">
            <button class="workload-btn accepting" data-status="active">
                <span class="icon">▶</span> Accepting new contacts
            </button>
            <button class="workload-btn investing" data-status="existing">
                <span class="icon">❚❚</span> I'm only investing in existing contacts
            </button>
            <button class="workload-btn too-many" data-status="too_many">
                <span class="icon">■</span> I have too many contacts
            </button>
        </div>
        <a href="#" class="travel-link">🧳 Set travel or dates unavailable</a>
    </section>
    ```
- [ ] **5.2** Styles in `_dashboard.scss`: `.workload-options` is a flex row. Each `.workload-btn` has a distinct left-border or background color: green for accepting (`--success-color`), orange for investing (`--warning-color`), red for too-many (`--alert-color`). The currently active option should be visually highlighted (e.g., filled background, others outlined). On mobile, stack buttons vertically.
- [ ] **5.3** Add REST endpoints: `GET dt/v1/dashboard/workload-status` (returns current user's workload status) and `PUT dt/v1/dashboard/workload-status` (updates it).
    - Require `access_disciple_tools` capability.
    - **Implementation details** (based on the previous `disciple-tools-dashboard` plugin's `rest-api.php`):

    #### How workload status is stored
    The previous plugin stored the workload status as a **WordPress user option** via `update_user_option()`. The relevant code from the old plugin's `update_user()` method (`POST /user` endpoint):
    ```php
    public function update_user( WP_REST_Request $request ) {
        $body = $request->get_json_params();
        $user = wp_get_current_user();
        if ( !empty( $body['workload_status'] ) ) {
            update_user_option( $user->ID, 'workload_status', $body['workload_status'] );
        }
        return true;
    }
    ```
    Key points:
    - Uses `update_user_option()` / `get_user_option()` (stores in `wp_usermeta` with a blog-prefix key), **not** `dt_user_meta`.
    - The status values are strings: `'active'` (accepting), `'existing'` (investing in existing only), `'too_many'` (too many contacts).
    - The old plugin combined this into a generic `POST /user` endpoint. For our implementation, dedicated `GET` and `PUT` endpoints are cleaner.

    #### GET endpoint (read current status)
    ```php
    public static function get_workload_status( $request ) {
        $user_id = get_current_user_id();
        $status = get_user_option( 'workload_status', $user_id );
        return [
            'workload_status' => $status ?: 'active', // default to 'active' if not set
        ];
    }
    ```

    #### PUT endpoint (update status)
    ```php
    public static function update_workload_status( $request ) {
        $body = $request->get_json_params();
        $user_id = get_current_user_id();
        $allowed = [ 'active', 'existing', 'too_many' ];
        if ( empty( $body['workload_status'] ) || !in_array( $body['workload_status'], $allowed, true ) ) {
            return new WP_Error( 'invalid_status', 'Invalid workload status.', [ 'status' => 400 ] );
        }
        update_user_option( $user_id, 'workload_status', sanitize_text_field( $body['workload_status'] ) );
        return [ 'workload_status' => $body['workload_status'] ];
    }
    ```

    #### Performance note
    Reading/writing a single user option is already very fast (single row lookup in `wp_usermeta`). No optimization needed beyond standard WordPress caching, which `get_user_option()` benefits from automatically. The old plugin's approach of using `update_user_option()` is the correct and most performant way to store per-user settings like this — avoid using `DT_Posts` or custom tables for simple user preferences.
- [ ] **5.4** In `dashboard.js`: On load, fetch current status and highlight the matching button. On button click, PUT the new status and update UI. Add a `.selected` class to the active button.
- [ ] **5.5** Layout: On desktop, this section sits to the right of "Your Apps" (they share a row). On mobile, it stacks below "Your Apps".

---

### Phase 6: Contacts (Recently Updated) Table

**Goal:** Table/list of recently updated contacts with status badges and a filter dropdown. See the "Contacts (Recently Updated)" section in both mockups — table with Name, Status badge, Last Updated columns, and a ⋮ menu.

- [ ] **6.1** Add `#recent-contacts` HTML to `template.php`:
    ```html
    <section id="recent-contacts" class="dashboard-card">
        <div class="section-header">
            <h2>Contacts (Recently Updated)</h2>
            <a href="/contacts" class="see-all">See all &gt;</a>
            <button class="menu-btn">⋮</button>
            <div class="filter-dropdown hidden">
                <ul>
                    <li data-filter="recently_updated" class="active">✓ Recently Updated</li>
                    <li data-filter="favorites">Favorites</li>
                    <li data-filter="update_needed">Update Needed</li>
                    <li data-filter="active">Active</li>
                </ul>
            </div>
        </div>
        <div class="contacts-table">
            <div class="table-header">
                <span class="col-name">Name</span>
                <span class="col-status">Status</span>
                <span class="col-date">Last Updated</span>
            </div>
            <div class="table-body"></div>
        </div>
    </section>
    ```
- [ ] **6.2** Styles in `_dashboard.scss`:
    - `.section-header`: flex row with title, "See all" link, and menu button right-aligned.
    - `.filter-dropdown`: absolute positioned dropdown below ⋮ button, white background, shadow, z-index.
    - `.contacts-table .table-header`: grid row with 3 columns, gray text, smaller font.
    - `.table-body .table-row`: grid row matching header columns, with bottom border, hover highlight, cursor pointer. Each row has an avatar circle (first letter of name or generic icon), name text, a colored `.status-badge` (colored pill — green for Active, orange for Waiting to be accepted, red for Paused, blue for New Contact, etc.), and a date string.
    - Status badge colors should match existing D.T status colors. Check `dt-assets/scss/` for existing status color definitions.
    - On mobile: hide the table header, show each contact as a card-style row (name + status badge on one line, date below).
- [ ] **6.3** Add REST endpoint: `GET dt/v1/dashboard/recent-contacts?filter=recently_updated&limit=8`
    - Use `DT_Posts::list_posts('contacts', [...])` with `sort=-last_modified`, appropriate filters based on the `filter` param, and `limit`.
    - For `recently_updated`: sort by `last_modified` desc.
    - For `favorites`: filter by `favorited` = true.
    - For `update_needed`: filter by `requires_update` = true.
    - For `active`: filter by `overall_status` = `active`.
    - Return: array of `{ id, name, status: { key, label, color }, last_modified, permalink }`.
- [ ] **6.4** In `dashboard.js`:
    - On page load, fetch with default filter (`recently_updated`) and render rows into `.table-body`.
    - Each row is an `<a>` or clickable `<div>` linking to `/contacts/{id}`.
    - Format dates as "Month Day, Year" (e.g., "Sept 1, 2025") using `Intl.DateTimeFormat` or D.T's existing date formatting utilities.
    - Wire up ⋮ button to toggle `.filter-dropdown`. On filter selection, re-fetch with new filter, update the active checkmark, and re-render rows.
    - Show a loading spinner inside `.table-body` during fetch.
    - If no contacts, show "No contacts found" message.

---

### Phase 7: Groups (Recently Updated) Table

**Goal:** Same pattern as Phase 6 but for groups. See the "Groups (Recently Updated)" section in both mockups — same table layout, positioned to the right of Contacts on desktop.

- [ ] **7.1** Add `#recent-groups` HTML to `template.php` — same structure as `#recent-contacts` but with `id="recent-groups"`, title "Groups (Recently Updated)", and link to `/groups`.
- [ ] **7.2** Reuse the same CSS classes from Phase 6 (`.section-header`, `.filter-dropdown`, `.table-header`, `.table-body`, `.table-row`, `.status-badge`). Add any group-specific overrides to `_dashboard.scss` under `#recent-groups` if needed.
- [ ] **7.3** Add REST endpoint: `GET dt/v1/dashboard/recent-groups?filter=recently_updated&limit=5`
    - Use `DT_Posts::list_posts('groups', [...])` with same filter options as contacts but for group fields (`group_status` instead of `overall_status`).
    - Return same shape: `{ id, name, status: { key, label, color }, last_modified, permalink }`.
- [ ] **7.4** In `dashboard.js`: Implement with the same fetch/render/filter pattern as contacts. Consider extracting a shared `renderPostTable(sectionId, postType, endpoint)` function to avoid code duplication between Phases 6 and 7.
- [ ] **7.5** Layout: On desktop, `#recent-contacts` and `#recent-groups` sit side-by-side (2-column grid). On mobile, `#recent-groups` stacks below `#recent-contacts`.

---

### Phase 8: Tasks Section

**Goal:** List of upcoming tasks/reminders with action buttons. See the "Tasks" section in both mockups — each row has a date badge (month + day), contact name, description, and action buttons (checkmark + delete).

- [ ] **8.1** Add `#tasks` HTML to `template.php`:
    ```html
    <section id="tasks" class="dashboard-card">
        <h2>Tasks</h2>
        <div class="tasks-list"></div>
    </section>
    ```
- [ ] **8.2** Styles in `_dashboard.scss`:
    - `.tasks-list .task-row`: flex row with items centered, padding, bottom border.
    - `.task-date`: small square/rectangle with month abbreviation on top (smaller, uppercase, red/dark text) and day number below (larger, bold). Background light gray, rounded corners. About 45×50px.
    - `.task-info`: flex column with contact name (bold) and task description (lighter text) below.
    - `.task-actions`: flex row of icon buttons on the right side. Green circle checkmark button (`.btn-complete`) and red square delete button (`.btn-dismiss`). Use SVG icons or Unicode characters styled appropriately.
    - On mobile: full-width card. On desktop: appears as one of three columns in the bottom row (right column).
- [ ] **8.3** Add REST endpoint: `GET dt/v1/dashboard/tasks`
    - Query the user's upcoming tasks/activities. In D.T, tasks are stored as post activity or in the `dt_activity_log` / `dt_notifications` tables, or as `task` post meta. Check the existing D.T codebase for how tasks are queried (look for `DT_Posts::get_user_tasks()` or similar).
    - Return: array of `{ id, date (ISO string), contact_name, contact_id, description, type }`.
    - Sorted by date ascending (soonest first).
- [ ] **8.4** In `dashboard.js`:
    - Fetch tasks on load, render into `.tasks-list`.
    - Format date badge: extract month abbreviation (e.g., "Jan") and day number (e.g., "10") from the ISO date.
    - Complete button: POST to mark task complete (check D.T's existing task completion endpoint), then remove row with animation.
    - Dismiss button: POST to dismiss/delete the task, then remove row.
    - If no tasks, show "No upcoming tasks" message.

---

### Phase 9: Faith Milestone Totals Section

**Goal:** Vertical list of faith milestones with icons and horizontal progress bars showing counts. See "Faith Milestone Totals" in both mockups — left column of bottom row on desktop.

**Milestones to display** (in order, matching mockup):
1. Has Bible
2. States Belief
3. Sharing Gospel/Testimony
4. Baptizing
5. Starting Churches
6. Reading Bible
7. Can Share Gospel/Testimony
8. Baptized
9. In Church/Group

- [ ] **9.1** Add `#faith-milestones` HTML to `template.php`:
    ```html
    <section id="faith-milestones" class="dashboard-card">
        <h2>Faith Milestone Totals</h2>
        <p class="subtitle">Milestones on your active contacts</p>
        <div class="milestones-list"></div>
    </section>
    ```
- [ ] **9.2** Styles in `_dashboard.scss`:
    - `.milestones-list .milestone-row`: flex row, items centered, small gap, margin-bottom.
    - `.milestone-icon`: 30×30px icon image (use existing D.T milestone icons from `dt-assets/images/` — check for SVGs like `bible.svg`, `baptism.svg`, etc.).
    - `.milestone-label`: text label, smaller font.
    - `.milestone-bar`: horizontal bar chart — a container div with a fixed width, inside which a colored fill div (`background: var(--primary-color)` or a dark blue matching mockup) has its width set as a percentage or pixel value based on the count. Show the count number to the right of (or inside) the bar.
    - On mobile: full-width card. On desktop: left column of the 3-column bottom row.
- [ ] **9.3** Add REST endpoint: `GET dt/v1/dashboard/faith-milestones`
    - Query contacts assigned to current user with `overall_status` = `active`, then count how many have each milestone field set to `true`. The milestone fields in D.T contacts are typically: `milestones_has_bible`, `milestones_states_belief`, `milestones_sharing_gospel`, `milestones_baptizing`, `milestones_starting_churches`, `milestones_reading_bible`, `milestones_can_share_gospel`, `milestones_baptized`, `milestones_in_group`. Verify exact field names in the D.T contacts field settings.
    - Return: array of `{ key, label, icon, count }` for each milestone.
- [ ] **9.4** In `dashboard.js`: Fetch milestones on load, render each as a row in `.milestones-list`. Calculate bar widths relative to the maximum count (or relative to total active contacts). If all counts are 0, show bars at 0 width.

---

### Phase 10: Seeker Path Progress Section

**Goal:** Funnel/waterfall chart showing how many contacts are at each stage of the seeker path. See "Seeker Path Progress" in both mockups — middle column of bottom row on desktop, shows a tapering funnel visualization.

**Seeker path stages** (top to bottom, widest to narrowest):
1. Contact Attempt Needed
2. Contact Established
3. Being Coached
4. (Additional stages if present in D.T configuration)

- [ ] **10.1** Add `#seeker-path` HTML to `template.php`:
    ```html
    <section id="seeker-path" class="dashboard-card">
        <h2>Seeker Path Progress</h2>
        <div class="funnel-chart"></div>
    </section>
    ```
- [ ] **10.2** Styles in `_dashboard.scss`: Build a CSS-only funnel:
    - `.funnel-chart .funnel-stage`: block-level element, centered, with text inside showing stage name and count.
    - Each stage has a lighter blue background (matching mockup — light blue/gray tones). Stages get progressively narrower using decreasing `width` percentages (e.g., 100%, 70%, 50%) and `margin: 0 auto` for centering.
    - Add slight `border-radius` on the bottom stages. Alternatively, use `clip-path` or border tricks for true trapezoid shapes matching the mockup.
    - Keep font size small enough to fit inside the narrower stages.
- [ ] **10.3** Add REST endpoint: `GET dt/v1/dashboard/seeker-path`
    - Query contacts assigned to current user with `overall_status` = `active`, grouped by `seeker_path` field value.
    - Use `DT_Posts::list_posts()` with `fields_to_return=['seeker_path']` and `limit=1000` or use a direct `$wpdb` count query grouped by seeker path for performance.
    - Return: array of `{ key, label, count }` ordered by seeker path progression.
- [ ] **10.4** In `dashboard.js`: Fetch seeker path data, render funnel stages. Calculate stage widths relative to the largest count. If a stage has 0 contacts, still show it but with minimal width and "0" label.

---

### Phase 11: Polish & Integration

**Goal:** Final quality improvements, edge case handling, and preparation for production.

- [ ] **11.1** Add loading skeletons/spinners for each section: Use a `.skeleton` CSS class with a pulsing gray gradient animation. Apply to each section's content area before data loads, then replace with real content.
- [ ] **11.2** Error handling: If any REST call fails, show a subtle inline error message within that section (e.g., "Unable to load data. Try refreshing."). Use `try/catch` around all `fetch()` calls in JS. Do not let one section's failure break other sections — each section should load independently.
- [ ] **11.3** Accessibility:
    - Add `aria-label` attributes to all interactive elements (buttons, links).
    - Ensure color is not the only indicator of status — include text labels alongside colored badges.
    - Add `role="table"`, `role="row"`, `role="cell"` to the custom table markup if not using `<table>` elements.
    - Ensure keyboard navigation works: all interactive elements are focusable and operable with Enter/Space.
- [ ] **11.4** Performance: Each section fetches independently on page load. Consider using `Promise.all()` to parallelize all initial fetches. For sections below the fold, consider `IntersectionObserver` to defer loading until visible.
- [ ] **11.5** Internationalization: Audit all strings in both PHP and JS. PHP strings should use `esc_html__('...', 'disciple_tools')`. JS strings should be passed via `wp_localize_script()` in a `translations` object and referenced as `dtDashboard.translations.key`.
- [ ] **11.6** Cross-browser testing: Test in Chrome, Firefox, Safari, and Edge. Verify CSS Grid fallbacks if needed.
- [ ] **11.7** Responsive testing: Test at common breakpoints (320px, 375px, 768px, 1024px, 1440px) to ensure layout matches mockups.
- [ ] **11.8** Final slug change: In `dashboard.php`, change `private static string $slug = 'dashboard2';` to `private static string $slug = 'dashboard';` and remove or redirect the old dashboard route. Verify navigation menu link updates correctly.

---

### Summary of Files to Create/Modify

| File | Action |
|------|--------|
| `dt-dashboard/template.php` | Rewrite with full dashboard HTML structure (all section containers) |
| `dt-dashboard/dashboard.php` | Add script/style enqueues, register REST routes, final slug change |
| `dt-assets/scss/_dashboard.scss` | Expand with styles for all sections (building on existing foundation) |
| `dt-dashboard/dashboard.js` | **New** — all dashboard JS (fetch data, render, interactions) |
| `dt-dashboard/endpoints.php` | **New** — REST API endpoints for all dashboard data |

### Recommended Implementation Order

Phases 1 → 2 → 4 → 3 → 5 → 6 → 7 → 8 → 9 → 10 → 11

Start with the layout scaffolding and the most visible/impactful sections (Pending Contacts, Stats Tiles), then build out the data tables and charts, finishing with polish and the final slug rename.
