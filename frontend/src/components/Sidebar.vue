<template>
  <div class="sidebar">
    <nav class="nav flex-column">
      <router-link to="/dashboard" class="nav-link" active-class="active">
        <i class="fas fa-home me-2"></i>Trang chủ
      </router-link>
      <router-link to="/documents" class="nav-link" active-class="active">
        <i class="fas fa-book me-2"></i>Tất cả tài liệu
      </router-link>
      <router-link to="/favorites" class="nav-link" active-class="active">
        <i class="fas fa-star me-2"></i>Yêu thích
      </router-link>
      <router-link to="/categories" class="nav-link" active-class="active">
        <i class="fas fa-tag me-2"></i>Danh mục
      </router-link>
      <router-link to="/folders" class="nav-link" active-class="active">
        <i class="fas fa-folder me-2"></i>Thư mục
      </router-link>
      <router-link to="/upload" class="nav-link" active-class="active">
        <i class="fas fa-arrow-up me-2"></i>Tải lên
      </router-link>
      <router-link v-if="isAdmin" to="/members" class="nav-link" active-class="active">
        <i class="fas fa-users me-2"></i>Thành viên
      </router-link>
    </nav>
  </div>
</template>

<script>
export default {
  name: 'Sidebar',
  computed: {
    isAdmin() {
      try {
        return JSON.parse(localStorage.getItem('user'))?.role === 'admin'
      } catch {
        return false
      }
    }
  }
}
</script>

<style scoped>
.sidebar {
  width: 220px;
  background-color: #fff;
  border-right: 1px solid #dededb;
  min-height: calc(100vh - 56px);
  padding: 18px 10px;
}

.nav-link {
  margin-bottom: 3px;
  padding: 10px 12px;
  border-radius: 6px;
  color: #292929;
  text-decoration: none;
  display: block;
}

.nav-link:hover {
  background-color: #f1f1ef;
  color: #171717;
}

.nav-link.active {
  background-color: #171717;
  color: white !important;
}
</style>
