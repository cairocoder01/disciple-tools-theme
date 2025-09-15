<template>
  <div class="dt-admin-layout">
    <SideNavigation />
    <div class="dt-admin-main-content">
      <router-view></router-view>
    </div>
  </div>
</template>

<script>
import SideNavigation from './components/SideNavigation.vue'

export default {
  name: 'App',
  components: {
    SideNavigation,
  },
  data() {
    return {
      dtAdminData: window.dtAdminData || {},
      slots: {}, // Object to store registered slot components
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
          children: [],
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
    }
  },
  provide() {
    return {
      navigationItems: this.navigationItems,
      slots: this.slots,
      router: this.$router,
    }
  },
  mounted() {
    this.addStyles()
    console.log('DT Admin Vue App mounted successfully')
  },
  methods: {
    registerPlugin(pluginConfig) {
      if (!pluginConfig || !pluginConfig.name || !pluginConfig.path || !pluginConfig.component) {
        console.error('DT Admin Plugin Registration Error: Missing required parameters (name, path, component)')
        return false
      }

      const basePath = '/dt-admin/extensions/'
      let pluginPath = pluginConfig.path
      if (!pluginPath.startsWith(basePath)) {
        pluginPath = basePath + pluginPath.replace(/^\/+/, '')
      }

      const extensionsItem = this.navigationItems.find(item => item.name === 'Extensions')
      if (!extensionsItem) {
        console.error('DT Admin Plugin Registration Error: Extensions navigation item not found')
        return false
      }

      const existingPlugin = extensionsItem.children.find(child => child.path === pluginPath)
      if (existingPlugin) {
        console.warn(`DT Admin Plugin Registration Warning: Plugin with path ${pluginPath} already registered`)
        return false
      }

      extensionsItem.children.push({
        name: pluginConfig.name,
        path: pluginPath,
        plugin: true,
      })

      this.$router.addRoute({
        path: pluginPath,
        component: pluginConfig.component,
        name: pluginConfig.name.replace(/\s+/g, '_').toLowerCase(),
      })

      console.log(`DT Admin Plugin registered: ${pluginConfig.name} at ${pluginPath}`)
      return true
    },
    registerSlot(slotConfig) {
      if (!slotConfig || !slotConfig.name || !slotConfig.component) {
        console.error('DT Admin Slot Registration Error: Missing required parameters (name, component)')
        return false
      }

      if (!this.slots[slotConfig.name]) {
        this.slots[slotConfig.name] = []
      }

      const existingComponent = this.slots[slotConfig.name].find(
        comp => comp.id === slotConfig.id || comp.name === slotConfig.name
      )
      if (existingComponent) {
        console.warn(`DT Admin Slot Registration Warning: Component already registered for slot ${slotConfig.name}`)
        return false
      }

      this.slots[slotConfig.name].push({
        id: slotConfig.id || `component-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
        name: slotConfig.name,
        component: slotConfig.component,
        props: slotConfig.props || {},
      })

      console.log(`DT Admin Slot component registered: ${slotConfig.name || 'unnamed'} in slot ${slotConfig.name}`)
      return true
    },
    addStyles() {
      // Styles are now imported via CSS file
    },
  },
}
</script>
