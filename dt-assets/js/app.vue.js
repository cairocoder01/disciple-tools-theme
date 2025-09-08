/**
 * DT Admin Vue.js Application
 * Single Page Application for /dt-admin section using Vue.js 3 and Vue Router 4
 */

const { createApp } = Vue;
const { createRouter, createWebHistory } = VueRouter;

// Vue Components
const Home = {
  template: `
        <div class="dt-admin-home">
            <h1>DT Admin Dashboard</h1>
            <p>Welcome to the DiscipleTools Admin area.</p>
            <div class="dashboard-content">
                <p>Use the navigation sidebar to access different administrative sections.</p>
            </div>
        </div>
    `,
};

const Mapping = {
  template: `
        <div class="dt-admin-mapping">
            <h1>Mapping</h1>
            <p>Mapping configuration and tools.</p>
            <div class="mapping-content">
                <p>Configure your mapping settings here.</p>
                <p>Use the sidebar navigation to access Focus Areas.</p>
            </div>
        </div>
    `,
};

const MappingFocus = {
  template: `
        <div class="dt-admin-mapping-focus">
            <h1>Mapping - Focus Areas</h1>
            <p>Configure focus areas for mapping.</p>
            <div class="focus-areas-content">
                <p>Focus area configuration tools will appear here.</p>
            </div>
        </div>
    `,
};

const ExtensionsDemo = {
  template: `
        <div class="dt-admin-extensions-demo">
            <h1>Extensions - Demo Content</h1>
            <p>Manage demo content for your DiscipleTools installation.</p>
            <div class="demo-content-tools">
                <p>Demo content management tools will appear here.</p>
            </div>
        </div>
    `,
};

const SettingsGeneral = {
  template: `
        <div class="dt-admin-settings-general">
            <h1>Settings - General</h1>
            <p>General administrative settings.</p>
            <div class="general-settings-content">
                <p>General settings configuration will appear here.</p>
            </div>
        </div>
    `,
};

const NotFound = {
  template: `
        <div class="dt-admin-404">
            <h1>Page Not Found</h1>
            <p>The requested page could not be found.</p>
            <div class="not-found-content">
                <p>Use the sidebar navigation to return to a valid page.</p>
            </div>
        </div>
    `,
};

// Router configuration
const routes = [
  { path: '/dt-admin', component: Home },
  { path: '/dt-admin/', component: Home },
  { path: '/dt-admin/home', component: Home },
  { path: '/dt-admin/mapping', component: Mapping },
  { path: '/dt-admin/mapping/focus', component: MappingFocus },
  { path: '/dt-admin/extensions/demo-content', component: ExtensionsDemo },
  { path: '/dt-admin/settings/general', component: SettingsGeneral },
  { path: '/:pathMatch(.*)*', component: NotFound },
];

// Create Vue Router instance
const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Navigation Component
const SideNavigation = {
  template: `
        <div class="dt-admin-sidebar">
            <div class="dt-admin-sidebar-header">
                <h2>DT Admin</h2>
            </div>
            <nav class="dt-admin-nav">
                <ul class="dt-admin-nav-list">
                    <li v-for="navItem in navigationItems" :key="navItem.path" class="dt-admin-nav-item">
                        <!-- Navigable link for items without children -->
                        <router-link v-if="!navItem.children || navItem.children.length === 0"
                                   :to="navItem.path"
                                   class="dt-admin-nav-link">
                            {{ navItem.name }}
                        </router-link>
                        <!-- Non-navigable expandable link for items with children -->
                        <a v-else
                           @click="toggleExpanded(navItem.name)"
                           class="dt-admin-nav-link has-children"
                           :class="{ 'expanded': isExpanded(navItem.name) }">
                            {{ navItem.name }}
                        </a>
                        <ul v-if="navItem.children && navItem.children.length > 0 && isExpanded(navItem.name)"
                            class="dt-admin-nav-subnav">
                            <li v-for="subItem in navItem.children" :key="subItem.path" class="dt-admin-nav-subitem">
                                <router-link :to="subItem.path" class="dt-admin-nav-sublink">
                                    {{ subItem.name }}
                                </router-link>
                            </li>
                        </ul>
                    </li>
                </ul>
            </nav>
        </div>
    `,
  inject: ['navigationItems'],
  data() {
    return {
      expandedItems: [], // Start with all parent items expanded
    };
  },
  methods: {
    toggleExpanded(itemName) {
      const index = this.expandedItems.indexOf(itemName);
      if (index > -1) {
        this.expandedItems.splice(index, 1);
      } else {
        this.expandedItems.push(itemName);
      }
    },
    isExpanded(itemName) {
      return this.expandedItems.includes(itemName);
    },
  },
};

// Main Vue App
const App = {
  template: `
        <div class="dt-admin-layout">
            <SideNavigation />
            <div class="dt-admin-main-content">
                <router-view></router-view>
            </div>
        </div>
    `,
  components: {
    SideNavigation,
  },
  data() {
    return {
      dtAdminData: window.dtAdminData || {},
      navigationItems: [
        {
          name: 'Dashboard',
          path: '/dt-admin',
          children: [],
        },
        {
          name: 'Mapping',
          path: '/dt-admin/mapping',
          children: [
            {
              name: 'General',
              path: '/dt-admin/mapping',
            },
            {
              name: 'Focus Areas',
              path: '/dt-admin/mapping/focus',
            },
          ],
        },
        {
          name: 'Extensions',
          path: '/dt-admin/extensions',
          children: [
            {
              name: 'Demo Content',
              path: '/dt-admin/extensions/demo-content',
            },
          ],
        },
        {
          name: 'Settings',
          path: '/dt-admin/settings',
          children: [
            {
              name: 'General',
              path: '/dt-admin/settings/general',
            },
          ],
        },
      ],
    };
  },
  provide() {
    return {
      navigationItems: this.navigationItems,
    };
  },
  mounted() {
    this.addStyles();
    console.log('DT Admin Vue App mounted successfully');
  },
  methods: {
    addStyles() {
      const styles = `
                <style id="dt-admin-styles">
                    /* Layout */
                    .dt-admin-layout {
                        display: flex;
                        min-height: 100vh;
                    }

                    .dt-admin-sidebar {
                        width: 280px;
                        background: #f8f9fa;
                        border-right: 1px solid #dee2e6;
                        flex-shrink: 0;
                        overflow-y: auto;
                    }

                    .dt-admin-main-content {
                        flex: 1;
                        padding: 0;
                        background: #ffffff;
                        overflow-y: auto;
                    }

                    /* Sidebar Header */
                    .dt-admin-sidebar-header {
                        padding: 20px;
                        background: #0073aa;
                        color: white;
                        border-bottom: 1px solid #005177;
                    }

                    .dt-admin-sidebar-header h2 {
                        margin: 0;
                        font-size: 1.5em;
                        font-weight: 600;
                    }

                    /* Navigation */
                    .dt-admin-nav {
                        padding: 0;
                    }

                    .dt-admin-nav-list {
                        list-style: none;
                        padding: 0;
                        margin: 0;
                    }

                    .dt-admin-nav-item {
                        border-bottom: 1px solid #dee2e6;
                    }

                    .dt-admin-nav-link {
                        display: block;
                        padding: 15px 20px;
                        color: #495057;
                        text-decoration: none;
                        font-weight: 500;
                        border-left: 3px solid transparent;
                        transition: all 0.2s ease;
                    }

                    .dt-admin-nav-link:hover {
                        background: #e9ecef;
                        color: #0073aa;
                        border-left-color: #0073aa;
                    }

                    .dt-admin-nav-link.router-link-active {
                        background: #e3f2fd;
                        color: #0073aa;
                        border-left-color: #0073aa;
                        font-weight: 600;
                    }

                    .dt-admin-nav-link.has-children {
                        position: relative;
                        cursor: pointer;
                    }

                    .dt-admin-nav-link.has-children:after {
                        content: '▶';
                        position: absolute;
                        right: 20px;
                        font-size: 0.8em;
                        opacity: 0.6;
                        transition: transform 0.2s ease;
                    }

                    .dt-admin-nav-link.has-children.expanded:after {
                        transform: rotate(90deg);
                    }

                    .dt-admin-nav-link.has-children:hover {
                        background: #e9ecef;
                        color: #0073aa;
                        border-left-color: #0073aa;
                    }

                    /* Sub-navigation */
                    .dt-admin-nav-subnav {
                        list-style: none;
                        padding: 0;
                        margin: 0;
                        background: #f1f3f4;
                        border-top: 1px solid #dee2e6;
                    }

                    .dt-admin-nav-subitem {
                        border-bottom: 1px solid #e9ecef;
                    }

                    .dt-admin-nav-subitem:last-child {
                        border-bottom: none;
                    }

                    .dt-admin-nav-sublink {
                        display: block;
                        padding: 12px 20px 12px 40px;
                        color: #6c757d;
                        text-decoration: none;
                        font-size: 0.9em;
                        border-left: 3px solid transparent;
                        transition: all 0.2s ease;
                    }

                    .dt-admin-nav-sublink:hover {
                        background: #e9ecef;
                        color: #0073aa;
                        border-left-color: #0073aa;
                    }

                    .dt-admin-nav-sublink.router-link-active {
                        background: #d4edda;
                        color: #155724;
                        border-left-color: #28a745;
                        font-weight: 500;
                    }

                    /* Content Areas */
                    .dt-admin-home, .dt-admin-mapping, .dt-admin-mapping-focus,
                    .dt-admin-extensions-demo, .dt-admin-settings-general, .dt-admin-404 {
                        padding: 30px;
                        min-height: 400px;
                    }

                    .dt-admin-home h1, .dt-admin-mapping h1, .dt-admin-mapping-focus h1,
                    .dt-admin-extensions-demo h1, .dt-admin-settings-general h1, .dt-admin-404 h1 {
                        margin-top: 0;
                        color: #495057;
                        border-bottom: 2px solid #dee2e6;
                        padding-bottom: 15px;
                        margin-bottom: 20px;
                    }

                    /* Loading state */
                    .dt-admin-loading {
                        display: none;
                    }

                    /* Responsive */
                    @media (max-width: 768px) {
                        .dt-admin-layout {
                            flex-direction: column;
                        }

                        .dt-admin-sidebar {
                            width: 100%;
                            max-height: 200px;
                        }

                        .dt-admin-nav-subnav {
                            display: none;
                        }

                        .dt-admin-home, .dt-admin-mapping, .dt-admin-mapping-focus,
                        .dt-admin-extensions-demo, .dt-admin-settings-general, .dt-admin-404 {
                            padding: 20px;
                        }
                    }
                </style>
            `;

      // Only add styles if not already present
      if (!document.getElementById('dt-admin-styles')) {
        document.head.insertAdjacentHTML('beforeend', styles);
      }
    },
  },
};

// Initialize the Vue app when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
  // Create and mount the Vue app
  const dtApp = createApp(App);
  dtApp.use(router);

  // Mount the app and store instance in window object
  window.dtApp = dtApp.mount('#dt-admin-app');

  console.log('DT Admin Vue.js application initialized');
});
