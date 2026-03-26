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
