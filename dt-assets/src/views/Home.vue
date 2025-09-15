<template>
  <div class="dt-admin-home">
    <h1>DT Admin Dashboard</h1>
    <p>Welcome to the DiscipleTools Admin area.</p>
    <div class="dashboard-content">
      <p>
        Use the navigation sidebar to access different administrative sections.
      </p>

      <!-- Admin Dashboard Slot for Plugin Tiles -->
      <div
        v-if="slots['admin-dashboard'] && slots['admin-dashboard'].length > 0"
        class="admin-dashboard-tiles"
      >
        <h2>Dashboard Tiles</h2>
        <div class="tiles-grid">
          <div
            v-for="tileComponent in tiles"
            :key="tileComponent.id"
            class="dashboard-tile"
          >
            <component
              :is="tileComponent.component"
              v-bind="tileComponent.props"
            ></component>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Home',
  inject: ['slots'],
  computed: {
    tiles() {
      const tiles = this.slots['admin-dashboard'] || [];
      /*tiles.push({
        id: 'test-tile',
        name: 'admin-dashboard-test',
        component: {
          template: `<h3>Test tile</h3>`,
        },
        props: {},
      });*/
      return tiles;
    },
  },
};
</script>
