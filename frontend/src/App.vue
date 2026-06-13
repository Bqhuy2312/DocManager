<template>
  <div>
    <Navbar v-if="isLoggedIn" />
    <div class="d-flex">
      <Sidebar v-if="isLoggedIn" class="flex-shrink-0" />
      <div class="flex-grow-1">
        <router-view />
      </div>
    </div>
    <NotificationHost />
  </div>
</template>

<script>
import Navbar from './components/Navbar.vue'
import Sidebar from './components/Sidebar.vue'
import NotificationHost from './components/common/NotificationHost.vue'
import { applyAppSettings, getStoredAppSettings } from './services/appSettingsService'
import { getSettings } from './services/settingsService'

export default {
  components: {
    Navbar,
    NotificationHost,
    Sidebar
  },
  mounted() {
    applyAppSettings(getStoredAppSettings())
    this.loadAppSettings()
  },
  watch: {
    '$route.path'() {
      this.loadAppSettings()
    }
  },
  computed: {
    isLoggedIn() {
      return this.$route.path !== '/login' && localStorage.getItem('user')
    }
  },
  methods: {
    async loadAppSettings() {
      if (!this.isLoggedIn) return

      try {
        const data = await getSettings()
        applyAppSettings(data.settings)
      } catch {
        applyAppSettings(getStoredAppSettings())
      }
    }
  }
}


</script>

<style>
body {
  margin: 0;
  padding: 0;
}
</style>
