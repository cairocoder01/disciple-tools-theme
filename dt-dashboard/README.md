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

### Layout & Foundation

The dashboard is built on a responsive grid system defined in `_dashboard.scss` and `template.php`.

- **Responsive Grid:**
    - **Desktop (≥1024px):** Uses a 12-column CSS Grid. Layout areas are defined by span counts (e.g., stats tiles span 3 columns each for a 4-across row).
    - **Tablet (768px–1023px):** Shifts to a 4-column or 2-column layout depending on the section complexity.
    - **Mobile (<768px):** Single-column stacked layout.
- **Card UI:** A shared `.dashboard-card` class provides the consistent white background, rounded corners (8px), and subtle shadows used across most sections.
- **Assets:**
    - `dashboard.js` handles client-side interactions (Accept/Decline, fetching dynamic stats).
    - `endpoints.php` provides the `dt/v1/dashboard/` REST API namespace.

---

### Pending Contacts Section

A full-width highlight banner at the top of the dashboard for contacts requiring immediate action.

- **Visuals:** Orange background with a large semi-transparent icon. Horizontal scrolling list of cards.
- **Implementation:**
    - PHP renders the initial list using `DT_Posts::search_viewable_post` for performance on first load.
    - `dashboard.js` handles the "Accept" and "Decline" actions via the `dt/v1/contacts/{id}/accept` endpoint.
    - Successfully accepted/declined cards are removed with a fade-out animation.
- **Deep Link:** "See Details" links directly to the contact's record.

---

### Your Apps Section

A row of quick-access icons for frequently used D.T. modules.

- **Data Source:** Directly integrates with the `DT_Home_Apps` singleton class, ensuring consistency with the main "Home Screen" app launcher.
- **Visuals:** Compact 75px square cards with a horizontal flex-scroll container. Features a CSS `mask-image` gradient to fade out overlapping content at the edges.
- **Customization:** Respects the `dt_dashboard_apps` filter, allowing plugins to add or remove dashboard apps independently of the main app list.

---

### Stats Tiles Row

Four high-level summary metrics that provide quick counts and deep links to filtered lists.

- **Metrics:**
    - **Active Contacts:** Total contacts assigned/sub-assigned to the user with `active` status.
    - **Update Needed:** Active contacts with the `requires_update` flag set.
    - **Contact Needed:** Active contacts with no seeker path progress.
    - **Active Groups:** Total groups assigned to the user with `active` status.
- **Implementation:**
    - **Entirely Clickable:** The whole tile is an `<a>` tag for better UX.
    - **Canonical Deep Links:** Uses `Disciple_Tools_Dashboard::get_list_url()` to generate URLs that match D.T.'s internal filtering logic exactly.
    - **Link Stability:** Query parameters are encoded to match D.T. canonical formats (`rawurlencode` for Base64 query/labels, `urlencode` for filter names) to prevent history-polluting redirects on the list pages.
    - **Accuracy:** The `dt/v1/dashboard/stats` endpoint uses `DT_Posts::list_posts()` with the same query parameters as the links, ensuring the count on the tile matches the count on the resulting list page.

---

### Contact Workload Section

Allows users to broadcast their availability for new contact assignments to dispatchers.

- **Implementation:**
    - **Server-Side Load:** The initial `workload_status` is retrieved via `get_user_option()` in `template.php`, ensuring the correct button is highlighted instantly on page load.
    - **Persistence:** Status is stored as a standard WordPress user option (`workload_status`).
    - **REST API:** A `PUT` endpoint at `dt/v1/dashboard/workload-status` handles seamless updates from the UI.
- **UI & Responsiveness:**
    - **Mobile:** Buttons are displayed 3-across horizontally with icons on top and compact labels.
    - **Desktop (≥1024px):** Layout shifts to "icon-left, label-right" with larger font sizes and 2-line label wrapping.
    - **Visual Feedback:** Buttons use theme-standard success (green), warning (orange), and alert (red) colors for their respective states.
- **Deep Link:** Includes a direct link to the user's availability settings page.

---

### Phase 6: Shared Post List Component (Backend + Frontend)

**Goal:** Build a reusable, post-type-agnostic post list component used by both the Contacts and Groups cards (and extensible to future record types). This phase creates all shared infrastructure; Phase 7 instantiates it for contacts and groups.

#### 6A — Shared REST Endpoint

- [ ] **6A.1** Add a single generic endpoint in `endpoints.php`: `GET dt/v1/dashboard/posts?post_type={type}&filter={filter}&limit={n}`
    - Accept parameters:
        - `post_type` (required) — e.g., `contacts`, `groups`, or any registered D.T post type.
        - `filter` (optional) — one of `recently_updated`, `favorites`, `update_needed`, `active`. Defaults to smart resolution (see 6A.3).
        - `limit` (optional, default 10).
        - `fields` (optional) — comma-separated list of fields to return (e.g., `name,overall_status,last_modified`). Defaults to a sensible set per post type.
    - Validate `post_type` against registered D.T post types (`DT_Posts::get_post_types()`).
    - Build `DT_Posts::list_posts()` args dynamically:
        - `recently_updated`: `sort=-last_modified`.
        - `favorites`: `favorited=true`.
        - `update_needed`: `requires_update=true`, assigned to me, active status.
        - `active`: status field = `active` (use the post type's status field — `overall_status` for contacts, `group_status` for groups, etc., looked up from field settings).
    - Return shape: `{ posts: [{ id, name, status: { key, label, color }, last_modified, permalink, ...requested_fields }], total, filter_applied }`.

- [ ] **6A.2** Add a helper method `get_status_field_key( $post_type )` that looks up which field represents the "status" for a given post type from its field settings. This avoids hardcoding `overall_status` vs `group_status`.

- [ ] **6A.3** Add a companion endpoint (or extend the above): `GET dt/v1/dashboard/posts-filters?post_type={type}`
    - Returns the **smart default filter** for the current user and post type, determined by this priority:
        1. **User saved preference** — if the user has explicitly saved/pinned a view for this post type (see 6D), use that.
        2. **Updates Needed** — if `update_needed` count > 0, return `update_needed`.
        3. **Favorites** — if the user has any favorited records of this post type, return `favorites`.
        4. **Active** — fallback.
    - Return shape: `{ default_filter, counts: { recently_updated, favorites, update_needed, active } }`.
    - The counts are used by the frontend to show indicators (e.g., badge on "Update Needed" menu item) and to drive the smart default without extra round-trips.

#### 6B — Shared Frontend Component (`PostListCard`)

- [ ] **6B.1** In `dashboard.js`, create a reusable `PostListCard` class/factory function:
    ```js
    class PostListCard {
      constructor({ container, postType, label, labelSingular, listPageUrl, newUrl, fields, limit })
      async init()           // fetch default filter, then load records
      async loadRecords(filter)
      renderRows(posts)
      renderEmptyState(filter)
      updateSubtitle(filter)
      toggleMenu()
      setFilter(filter)
      getSeeAllUrl(filter)   // returns list page URL with the active filter's query params
    }
    ```
    - `fields` is an array of field config objects: `{ key, label, render(value, post) }` — this allows each instantiation to define which columns appear and how they display (e.g., contacts might show `overall_status` as a colored badge, groups might show `member_count` as a number).
    - `limit` controls how many records to show (default 5).

- [ ] **6B.2** **Smart default on init:**
    - On `init()`, first call the `posts-filters` endpoint to get the smart default filter.
    - Then call `loadRecords(defaultFilter)` to populate the list.
    - Update the subtitle and menu checkmark accordingly.

- [ ] **6B.3** **Filter menu** (⋮ button in top-right):
    - Menu items: Recently Updated, Favorites, Update Needed, Active.
    - Each item shows a checkmark (✓) if it's the active filter.
    - Each item can optionally show a count badge (from the counts returned by `posts-filters`).
    - Selecting a filter calls `setFilter(filter)`, which:
        1. Updates the active checkmark.
        2. Calls `loadRecords(filter)`.
        3. Updates the card subtitle.
        4. Updates the "See all" link URL to include the appropriate filter params.

- [ ] **6B.4** **Card subtitle:**
    - Below the card title (e.g., "Contacts"), display a dynamic subtitle that describes the current view.
    - Examples: "Recently Updated", "⭐ Favorites", "⚠ Update Needed", "Active".
    - The subtitle updates on every filter change.

- [ ] **6B.5** **Empty state:**
    - When the API returns 0 records for the active filter, display a friendly message:
        - Text: "No {post_type_label} found for {filter_label}." (e.g., "No contacts found for Update Needed.")
        - Include a link: "Add a new {post_type_label_singular}" pointing to the new-record URL for that post type (e.g., `/contacts/new`).
    - Style the empty state with a subtle icon or illustration, centered in the card.

- [ ] **6B.6** **"See all" link:**
    - Always visible in the section header, regardless of whether records are shown.
    - Links to the list page for the post type with query parameters matching the active filter.
    - Uses `Disciple_Tools_Dashboard::get_list_url()` pattern to generate canonical D.T filter URLs.
    - Example: if filter is `update_needed` on contacts, links to `/contacts?filter=requires_update` (or the appropriate D.T query format).

- [ ] **6B.7** **Loading state:**
    - Show a skeleton/spinner inside the card body during data fetch.
    - On filter change, show a brief loading indicator while new data loads.

#### 6C — Shared Styles

- [ ] **6C.1** In `_dashboard.scss`, create shared classes scoped under `.post-list-card`:
    - `.post-list-header`: flex row — title (h2), subtitle, spacer, "See all" link, ⋮ menu button.
    - `.post-list-subtitle`: smaller text below the title, color `var(--secondary-text-color)`.
    - `.post-list-menu`: absolute-positioned dropdown with filter items, checkmarks, and optional count badges.
    - `.post-list-table .table-header`: grid row for column headers (gray text, smaller font).
    - `.post-list-table .table-row`: grid row with bottom border, hover highlight, cursor pointer. Avatar circle, name, status badge, date, and any additional configured fields.
    - `.status-badge`: colored pill using D.T's existing status color definitions.
    - `.post-list-empty`: centered message with subtle styling and "add new" link.
    - `.post-list-loading`: skeleton/spinner styles.
    - On mobile: hide table header, show each record as a compact card row (name + status on one line, date below).
    - The column grid template should be configurable via a CSS custom property or data attribute to accommodate different field counts per post type.

- [ ] **6C.2** No post-type-specific styles needed — all visual differences come from the field configuration passed to `PostListCard`.

#### 6D — User View Preference (Saved Filter)

**Goal:** Allow users to "pin" a preferred view for each post list, overriding the smart default logic. Also provide an easy way to revert to automatic defaults.

- [ ] **6D.1** Add REST endpoints for view preferences:
    - `PUT dt/v1/dashboard/post-list-preference` — body: `{ post_type, filter }`. Stores the user's preferred filter as a user option (e.g., `dt_dashboard_post_list_{post_type}_filter`).
    - `DELETE dt/v1/dashboard/post-list-preference?post_type={type}` — removes the saved preference, reverting to smart defaults.

- [ ] **6D.2** **UI options for saving/reverting**:

    **Option A — "Pin this view" in the filter menu (Recommended):**
    - Add a divider and a "Pin this view" / "Unpin" toggle at the bottom of the filter dropdown menu.
    - When a user selects a filter and clicks "Pin this view", that filter becomes their persistent default for this post type.
    - A small pin icon (📌) appears next to the subtitle when a view is pinned.
    - Clicking "Unpin" or "Use smart default" reverts to automatic logic.
    - **Pros:** Discoverable, inline with the filter workflow. Simple UI.
    - **Cons:** Adds one more item to the dropdown.

- [ ] **6D.3** In `PostListCard`, integrate the preference:
    - On `init()`, the `posts-default-filter` endpoint already checks for a saved preference (6A.3).
    - When `setFilter()` is called, it just changes the current view — it does **not** auto-save the preference.
    - Only the explicit "Pin this view" action saves the preference via the REST endpoint.
    - Show a visual indicator (pin icon on subtitle) when a saved preference is active.
    - Provide a "Use smart default" option to revert (calls the DELETE endpoint).

#### 6E — Configurable Fields

- [ ] **6E.1** Define a field configuration format used by both PHP (for server-side rendering hints) and JS (for column rendering):
    ```js
    // Example field configs for contacts:
    [
      { key: 'name', label: 'Name', render: (val, post) => avatarAndName(val, post) },
      { key: 'overall_status', label: 'Status', render: (val) => statusBadge(val) },
      { key: 'last_modified', label: 'Last Updated', render: (val) => formatDate(val) },
    ]

    // Example field configs for groups:
    [
      { key: 'name', label: 'Name', render: (val, post) => avatarAndName(val, post) },
      { key: 'group_status', label: 'Status', render: (val) => statusBadge(val) },
      { key: 'member_count', label: 'Members', render: (val) => val || '0' },
      { key: 'last_modified', label: 'Last Updated', render: (val) => formatDate(val) },
    ]
    ```
    - Provide built-in render helpers: `avatarAndName()`, `statusBadge()`, `formatDate()`, `plainText()` — reusable across post types.
    - The `fields` config is passed to `PostListCard` at instantiation time. New post types can define their own field configs without modifying the shared component.

- [ ] **6E.2** Configuration is passed via HTML `data-` attributes on each post list container element (see Phase 7.1 for the full HTML). This avoids a JS-global config object and keeps each card self-contained. The key data attributes are:
    - `data-post-type` — the D.T post type slug (e.g., `contacts`, `groups`).
    - `data-label` — plural display name (e.g., "Contacts").
    - `data-label-singular` — singular display name (e.g., "Contact").
    - `data-list-url` — URL to the full list page (e.g., `/contacts`).
    - `data-new-url` — URL to create a new record (e.g., `/contacts/new`).
    - `data-fields` — JSON-encoded array of field keys (e.g., `["name","overall_status","last_modified"]`).
    - `data-limit` — max records to show (e.g., `5`).
    - In `template.php`, these attributes are rendered server-side using `esc_attr()` for proper escaping.
    - Plugins can add additional post list containers via a `dt_dashboard_post_lists` filter (which adds new `<section>` elements with the appropriate data attributes to the template output).

---

### Phase 7: Contacts & Groups List Instances

**Goal:** Instantiate the shared `PostListCard` for contacts and groups. This phase is intentionally thin — all logic lives in the shared component from Phase 6.

- [ ] **7.1** Add HTML containers in `template.php`. All configuration is embedded as `data-` attributes on the `<section>` element so the JS component is fully self-initializing:
    ```html
    <section id="post-list-contacts"
             class="dashboard-card post-list-card"
             data-post-type="contacts"
             data-label="<?php esc_attr_e( 'Contacts', 'disciple_tools' ); ?>"
             data-label-singular="<?php esc_attr_e( 'Contact', 'disciple_tools' ); ?>"
             data-list-url="<?php echo esc_url( site_url( '/contacts' ) ); ?>"
             data-new-url="<?php echo esc_url( site_url( '/contacts/new' ) ); ?>"
             data-fields='<?php echo esc_attr( wp_json_encode( [ 'name', 'overall_status', 'last_modified' ] ) ); ?>'
             data-limit="5">
        <div class="post-list-header">
            <div class="post-list-titles">
                <h2><?php esc_html_e( 'Contacts', 'disciple_tools' ); ?></h2>
                <span class="post-list-subtitle"></span>
            </div>
            <a href="<?php echo esc_url( site_url( '/contacts' ) ); ?>" class="see-all-link"><?php esc_html_e( 'See all', 'disciple_tools' ); ?> &gt;</a>
            <button class="post-list-menu-btn" aria-label="<?php esc_attr_e( 'Filter options', 'disciple_tools' ); ?>">⋮</button>
            <div class="post-list-menu hidden"></div>
        </div>
        <div class="post-list-table">
            <div class="table-header"></div>
            <div class="table-body"></div>
        </div>
    </section>

    <section id="post-list-groups"
             class="dashboard-card post-list-card"
             data-post-type="groups"
             data-label="<?php esc_attr_e( 'Groups', 'disciple_tools' ); ?>"
             data-label-singular="<?php esc_attr_e( 'Group', 'disciple_tools' ); ?>"
             data-list-url="<?php echo esc_url( site_url( '/groups' ) ); ?>"
             data-new-url="<?php echo esc_url( site_url( '/groups/new' ) ); ?>"
             data-fields='<?php echo esc_attr( wp_json_encode( [ 'name', 'group_status', 'member_count', 'last_modified' ] ) ); ?>'
             data-limit="5">
        <div class="post-list-header">
            <div class="post-list-titles">
                <h2><?php esc_html_e( 'Groups', 'disciple_tools' ); ?></h2>
                <span class="post-list-subtitle"></span>
            </div>
            <a href="<?php echo esc_url( site_url( '/groups' ) ); ?>" class="see-all-link"><?php esc_html_e( 'See all', 'disciple_tools' ); ?> &gt;</a>
            <button class="post-list-menu-btn" aria-label="<?php esc_attr_e( 'Filter options', 'disciple_tools' ); ?>">⋮</button>
            <div class="post-list-menu hidden"></div>
        </div>
        <div class="post-list-table">
            <div class="table-header"></div>
            <div class="table-body"></div>
        </div>
    </section>
    ```

- [ ] **7.2** In `dashboard.js`, auto-discover and instantiate `PostListCard` instances by querying the DOM for all `.post-list-card` elements and reading their `data-` attributes:
    ```js
    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('.post-list-card').forEach((el) => {
        const card = new PostListCard({
          container: el,
          postType: el.dataset.postType,
          label: el.dataset.label,
          labelSingular: el.dataset.labelSingular,
          listPageUrl: el.dataset.listUrl,
          newUrl: el.dataset.newUrl,
          fields: JSON.parse(el.dataset.fields || '[]'),
          limit: parseInt(el.dataset.limit, 10) || 5,
        });
        card.init();
      });
    });
    ```
    - This DOM-driven instantiation means adding a new post type list only requires adding a new `<section class="post-list-card">` element with the appropriate `data-` attributes in the template — no JS changes needed.
    - No `wp_localize_script()` config is needed for post lists; each card is fully self-describing via its HTML attributes.

- [ ] **7.3** Layout in `_dashboard.scss`:
    - Desktop: `#post-list-contacts` and `#post-list-groups` sit side-by-side (2-column grid within the dashboard layout, each spanning 6 of 12 columns).
    - Mobile: stacked vertically, full-width.

- [ ] **7.4** Integration test: Verify both cards independently:
    - Load with smart default filter (updates needed → favorites → active).
    - Switch filters via the menu — confirm subtitle updates, rows re-render, "See all" link updates.
    - Test empty state for each filter.
    - Test pin/unpin preference persistence.
    - Test with a post type that has no records at all.

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
| `dt-dashboard/dashboard.js` | **New** — all dashboard JS (fetch data, render, interactions, shared `PostListCard` component) |
| `dt-dashboard/endpoints.php` | **New** — REST API endpoints for all dashboard data (including generic `post-list` endpoint) |

### Recommended Implementation Order

Phases 1 → 2 → 4 → 3 → 5 → 6 → 7 → 8 → 9 → 10 → 11

Start with the layout scaffolding and the most visible/impactful sections (Pending Contacts, Stats Tiles), then build out the data tables and charts, finishing with polish and the final slug rename.
