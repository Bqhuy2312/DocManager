<template>
  <div>
    <Navbar v-if="isLoggedIn" />
    <div class="d-flex">
      <Sidebar v-if="isLoggedIn" class="flex-shrink-0" />
      <div class="flex-grow-1">
        <router-view />
      </div>
    </div>
  </div>
</template>

<script>
import Navbar from './components/Navbar.vue'
import Sidebar from './components/Sidebar.vue'

export default {
  components: {
    Navbar,
    Sidebar
  },
  computed: {
    isLoggedIn() {
      return this.$route.path !== '/login' && localStorage.getItem('user')
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