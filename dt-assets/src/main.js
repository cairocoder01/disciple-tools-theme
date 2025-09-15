import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import './styles/admin.css';

// Import views
import Home from './views/Home.vue';
import Mapping from './views/Mapping.vue';
import MappingFocus from './views/MappingFocus.vue';
import ExtensionsDemo from './views/ExtensionsDemo.vue';
import SettingsGeneral from './views/SettingsGeneral.vue';
import NotFound from './views/NotFound.vue';

// Router configuration
// todo: find a way to await injection of other routes when deep-linking
// into routes created by other plugins
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

// Initialize the Vue app when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
  // Create and mount the Vue app
  const dtApp = createApp(App);
  dtApp.use(router);

  // Mount the app and store instance in window object
  const mountedApp = dtApp.mount('#dt-admin-app');

  // Expose the registerPlugin and registerSlot methods on the window object for plugin access
  window.dtApp = {
    ...mountedApp,
    registerPlugin: mountedApp.registerPlugin.bind(mountedApp),
    registerSlot: mountedApp.registerSlot.bind(mountedApp),
  };

  console.log('DT Admin Vue.js application initialized');
  console.log(
    'Plugin registration available at: window.dtApp.registerPlugin(config)',
  );
});
