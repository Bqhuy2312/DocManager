<template>
  <nav class="navbar navbar-expand-lg doc-navbar">
    <div class="container-fluid">
      <router-link to="/dashboard" class="navbar-brand">
        <strong><i class="fas fa-file me-2"></i>DocManager</strong>
      </router-link>
      <div class="dropdown">
        <button
          class="avatar-button"
          type="button"
          data-bs-toggle="dropdown"
          aria-expanded="false"
          aria-label="Mở menu tài khoản"
        >
          <i class="fas fa-user"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <router-link to="/settings" class="dropdown-item">
              <i class="fas fa-cog me-2"></i>Cài đặt
            </router-link>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <button class="dropdown-item" type="button" @click="logout">
              <i class="fas fa-door-open me-2"></i>Đăng xuất
            </button>
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
      localStorage.removeItem('token')
      this.$router.push('/login')
    }
  }
}
</script>

<style scoped>
.doc-navbar {
  min-height: 64px;
  background: #fff;
  border-bottom: 1px solid #dededb;
}

.navbar-brand {
  font-size: 1.5rem;
  text-decoration: none;
  color: #171717 !important;
}

.avatar-button {
  display: flex;
  width: 40px;
  height: 40px;
  align-items: center;
  justify-content: center;
  border: 1px solid #171717;
  border-radius: 50%;
  background: #171717;
  color: #fff;
  transition: background-color 0.2s ease;
}

.avatar-button:hover,
.avatar-button:focus {
  background: #3a3a3a;
}

.dropdown-menu {
  min-width: 170px;
  margin-top: 8px;
  border-color: #dededb;
  border-radius: 8px;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}

.dropdown-item {
  padding: 9px 14px;
  color: #171717;
}

.dropdown-item:hover,
.dropdown-item:focus {
  background: #f1f1ef;
  color: #171717;
}
</style>
