<template>
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
      <router-link to="/dashboard" class="navbar-brand">
        <strong><i class="fas fa-file me-2"></i>DocManager</strong>
      </router-link>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <router-link to="/settings" class="nav-link"><i class="fas fa-cog me-2"></i>Cài đặt</router-link>
          </li>
          <li class="nav-item">
            <button class="btn btn-danger btn-sm ms-2" @click="logout"><i class="fas fa-door-open me-2"></i>Đăng xuất</button>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</template>

<script>
export default {
  name: 'Navbar',
  methods: {
    logout() {
      localStorage.removeItem('user')
      this.$router.push('/login')
    }
  }
}
</script>

<style scoped>
.navbar-brand {
  font-size: 1.5rem;
  text-decoration: none;
  color: white !important;
}

.nav-link {
  text-decoration: none;
}
</style>