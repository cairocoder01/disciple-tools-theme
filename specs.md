# DT Admin Section Specifications

## Overview
This document outlines the implementation of a new `/dt-admin` section for the DiscipleTools theme, providing a single-page application (SPA) for administrative functionality.

## Requirements Fulfilled

### 1. URL Routing
- **Base URL**: `/dt-admin`
- **Additional URLs**:
  - `/dt-admin/mapping`
  - `/dt-admin/mapping/focus`
  - `/dt-admin/extensions/demo-content`
  - `/dt-admin/settings/general`

### 2. Template System
- Created `template-dt-admin.php` to handle all dt-admin routes
- Integrated with WordPress authentication system
- Permission checks for admin access

### 3. Vue.js Single Page Application
- Created `app.vue.js` using actual Vue.js 3 and Vue Router 4
- Vue.js and Vue Router loaded from public CDN (unpkg.com)
- `dtApp` instance stored in `window.dtApp`
- Proper Vue components with template syntax
- Vue Router for client-side routing and navigation
- All specified URLs functional within SPA

## Implementation Details

### URL Routing System
**File**: `functions.php` (lines 439-447)

Modified the `dt_url_loader()` method to intercept URLs starting with `dt-admin`:

```php
// Handle dt-admin routes with SPA support
if ( strpos( $url_path, 'dt-admin' ) === 0 && dt_please_log_in() ) {
    $template_filename = locate_template( 'template-dt-admin.php', true );
    if ( $template_filename ) {
        exit(); // just exit if template was found and loaded
    } else {
        throw new Error( 'Expected to find template template-dt-admin.php' );
    }
}
```

### Template File
**File**: `template-dt-admin.php`

Features:
- WordPress authentication integration via `dt_please_log_in()`
- Permission checks using `current_user_can()`
- Vue.js 3 and Vue Router 4 enqueued from CDN
- Custom Vue application script enqueuing
- Data localization for frontend use
- Semantic HTML structure with loading state

### Vue.js Application
**File**: `dt-assets/js/app.vue.js`

Architecture:
- **Vue.js 3**: Modern reactive framework from unpkg.com CDN
- **Vue Router 4**: Official Vue.js routing library
- **Vue Components**: Individual components for each route (Home, Mapping, etc.)
  - Template-based component definitions with clean content-focused layouts
  - No embedded navigation (handled by sidebar)
  - Proper Vue.js component structure
- **Sidebar Navigation**: Centralized navigation component with expand/collapse functionality
  - Navigation items stored in Vue state for dynamic modification
  - Nested sub-navigation for second-level routes with expand/collapse behavior
  - Main-level items with children are non-navigable and toggle their children visibility
  - Items without children (Dashboard) remain directly navigable
  - Professional sidebar layout with animated expand/collapse indicators
- **Main App**: Vue application with sidebar layout and router-view
- **Router Configuration**: Declarative route definitions with path matching

### Supported Routes
1. **Home** (`/dt-admin`, `/dt-admin/home`)
   - Dashboard with navigation to other sections

2. **Mapping** (`/dt-admin/mapping`)
   - Mapping configuration tools
   - Sub-navigation to focus areas

3. **Mapping Focus** (`/dt-admin/mapping/focus`)
   - Focus area configuration
   - Breadcrumb navigation

4. **Extensions Demo Content** (`/dt-admin/extensions/demo-content`)
   - Demo content management tools

5. **Settings General** (`/dt-admin/settings/general`)
   - General administrative settings

### Security Features
- WordPress authentication required
- User capability checks (`manage_options` or `access_disciple_tools`)
- WordPress nonce integration for REST API calls
- Sanitized URL handling

### Browser Support
- Modern browsers with ES6+ support
- HTML5 History API required for SPA routing
- Graceful fallback with loading state

## File Structure
```
disciple-tools-theme/
├── functions.php                    # Modified for dt-admin routing
├── template-dt-admin.php           # Main template file
├── dt-assets/
│   └── js/
│       └── app.vue.js              # Vue.js SPA application
└── specs.md                        # This documentation file
```

## Data Flow
1. User navigates to `/dt-admin/*` URL
2. WordPress routing intercepts via `dt_url_loader()`
3. `template-dt-admin.php` loads with authentication
4. `app.vue.js` script enqueued with localized data
5. `DTAdminApp` initializes and mounts Vue SPA
6. Client-side routing handles subsequent navigation

## Extensibility
The implementation allows for easy extension:
- Additional routes can be added to the `routes` object
- Custom components can be registered
- WordPress hooks available for plugin integration
- REST API integration prepared with nonce system

## Performance Considerations
- Single template file handles all dt-admin routes
- Minimal JavaScript footprint with custom router
- CSS styles injected dynamically to avoid conflicts
- File versioning based on modification time for cache busting

## Browser Navigation
- Full browser back/forward button support
- URL updates reflect current page state
- Direct URL access supported for all routes
- Bookmark-friendly URLs

## Plugin Registration System

The DT Admin Vue app now supports dynamic plugin registration, allowing WordPress plugins to add their own admin pages under the Extensions section.

### Plugin Registration API

WordPress plugins can register new admin pages by enqueuing a script that calls:

```javascript
window.dtApp.registerPlugin({
  name: 'My Plugin Name',        // Display name in navigation
  path: 'my-plugin-slug',        // URL slug (will be prefixed with /dt-admin/extensions/)
  component: MyPluginComponent   // Vue component to render
});
```

### Plugin Component Example

```javascript
const MyPluginComponent = {
  template: `
    <div class="my-plugin-admin">
      <h1>My Plugin Admin</h1>
      <p>Plugin administration interface goes here.</p>
    </div>
  `,
  mounted() {
    console.log('My Plugin component mounted');
  }
};
```

### Registration Features

- **Automatic Path Prefixing**: Plugin paths are automatically prefixed with `/dt-admin/extensions/`
- **Navigation Integration**: Plugin items are added under the Extensions section in the sidebar
- **Route Registration**: Vue Router routes are dynamically registered for each plugin
- **Duplicate Prevention**: System prevents registration of plugins with duplicate paths
- **Error Handling**: Comprehensive validation and error logging
- **Console Feedback**: Success and error messages logged to browser console

### Plugin Integration Workflow

1. Plugin enqueues JavaScript file after DT Admin Vue app loads
2. Plugin defines Vue component for its admin interface
3. Plugin calls `window.dtApp.registerPlugin()` with configuration
4. System validates parameters and registers navigation item + route
5. Plugin page becomes accessible via `/dt-admin/extensions/plugin-slug`

## Future Enhancements
- REST API endpoints for dynamic content
- User preference storage
- Enhanced permission granularity
- Mobile responsive design improvements
- Advanced Vue.js components with composition API
- State management with Pinia or Vuex
- Plugin permission management
- Plugin configuration storage

---

*Created: September 8, 2025*
*Updated: September 8, 2025*
*Implementation completed for DiscipleTools theme dt-admin section with plugin extensibility*
