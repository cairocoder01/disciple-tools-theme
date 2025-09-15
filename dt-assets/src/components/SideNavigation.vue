<template>
  <div class="dt-admin-sidebar">
    <div class="dt-admin-sidebar-header">
      <h2>DT Admin</h2>
    </div>
    <nav class="dt-admin-nav">
      <ul class="dt-admin-nav-list">
        <li v-for="navItem in navigationItems" :key="navItem.path" class="dt-admin-nav-item">
          <router-link
            v-if="!navItem.children || navItem.children.length === 0"
            :to="navItem.path"
            class="dt-admin-nav-link"
          >
            {{ navItem.name }}
          </router-link>
          <a
            v-else
            @click="toggleExpanded(navItem.name)"
            class="dt-admin-nav-link has-children"
            :class="{ 'expanded': isExpanded(navItem.name) }"
          >
            {{ navItem.name }}
          </a>
          <ul
            v-if="navItem.children && navItem.children.length > 0 && isExpanded(navItem.name)"
            class="dt-admin-nav-subnav"
          >
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
</template>

<script>
export default {
  name: 'SideNavigation',
  inject: ['navigationItems'],
  data() {
    return {
      expandedItems: [], // Start with all parent items expanded
    }
  },
  methods: {
    toggleExpanded(itemName) {
      const index = this.expandedItems.indexOf(itemName)
      if (index > -1) {
        this.expandedItems.splice(index, 1)
      } else {
        this.expandedItems.push(itemName)
      }
    },
    isExpanded(itemName) {
      return this.expandedItems.includes(itemName)
    },
  },
}
</script>
