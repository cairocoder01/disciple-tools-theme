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

- [ ] **3.1** Add `#your-apps` HTML structure to `template.php`:
    ```html
    <section id="your-apps">
        <h2><span class="grid-icon"></span> Your Apps</h2>
        <div class="apps-grid"></div>
    </section>
    ```
- [ ] **3.2** Add styles to `_dashboard.scss`: `.apps-grid` should be a flex row with `gap: 1rem`, items centered. Each `.app-icon` is a square (roughly 60×60px) with an icon image/SVG, a label below, and a hover effect (slight scale or shadow). On mobile, make it horizontally scrollable with `overflow-x: auto`.
- [ ] **3.3** Define the default apps list in PHP (in `dashboard.php` or a helper function) as an array of `['slug' => ..., 'label' => ..., 'icon' => ..., 'url' => ...]`. Default apps based on mockup:
    - Home Screen → `/` (grid icon)
    - User Contact List → `/contacts` (list icon)
    - User Group List → `/groups` (people icon)
    - Create Contact → `/contacts/new` (plus icon)
    - My Coached Contacts → `/contacts?filter=coached_by_me` (flag icon — desktop only, or show on all)
- [ ] **3.4** Pass the apps list through a WordPress filter: `$apps = apply_filters( 'dt_dashboard_apps', $default_apps );` and then pass to JS via `wp_localize_script()` or render directly in PHP.
- [ ] **3.5** Render app icons in JS or directly in PHP template. Each app is an `<a>` tag linking to its URL.

---

### Phase 4: Stats Tiles Row

**Goal:** Four summary count tiles in a row, each showing a metric name, large number, and "See all >" link. See both mockups — the row of 4 white cards below the apps section.

| Tile | Description | "See all" Link |
|------|-------------|---------------|
| Active Contacts | Count of user's contacts with `overall_status` = `active` | `/contacts?status=active` |
| Update Needed | Contacts with `requires_update` = `true` | `/contacts?requires_update=true` |
| Contact Attempt Needed | Contacts with `seeker_path` = `none` and status `assigned` | `/contacts?seeker_path=none` |
| Active Groups | Count of user's groups with `group_status` = `active` | `/groups?status=active` |

- [ ] **4.1** Add `#stats-tiles` HTML to `template.php`:
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
- [ ] **4.2** Add styles to `_dashboard.scss`: `.stats-grid` uses CSS Grid with `grid-template-columns: repeat(4, 1fr)` on desktop, `repeat(2, 1fr)` on mobile. Each `.stat-tile` is a white card (use `.dashboard-card` mixin/class), centered text, `.stat-count` in large bold font (~2.5rem), `.stat-label` smaller above it, `.stat-link` smaller below in theme link color.
- [ ] **4.3** Add REST endpoint: `GET dt/v1/dashboard/stats`
    - Use `DT_Posts::list_posts()` with `limit=0` (or use `$wpdb` count queries for performance) for each of the four metrics.
    - Return: `{ "active_contacts": 12, "update_needed": 9, "contact_attempt_needed": 2, "active_groups": 2 }`.
    - Require `access_disciple_tools` capability.
- [ ] **4.4** In `dashboard.js`: Fetch stats on page load, populate each `.stat-count` by matching `data-stat` attribute. Show "—" or a spinner while loading.

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
- [ ] **5.3** Add REST endpoints: `GET dt/v1/dashboard/workload-status` (returns current user's workload status) and `PUT dt/v1/dashboard/workload-status` (updates it). Use existing user meta key `workload_status` — check how the existing D.T codebase stores this (likely in `dt_user_meta` or `usermeta` table).
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
