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
      <router-link to="/members" class="nav-link" active-class="active">
        <i class="fas fa-users me-2"></i>Thành viên
      </router-link>
    </nav>
  </div>
</template>

<script>
export default {
  name: 'Sidebar'
}
</script>

<style scoped>
.sidebar {
  background-color: #f8f9fa;
  border-right: 1px solid #dee2e6;
  min-height: calc(100vh - 56px);
  padding: 20px 0;
}

.nav-link {
  padding: 10px 20px;
  color: #333;
  text-decoration: none;
  display: block;
}

.nav-link:hover {
  background-color: #e9ecef;
  color: #0d6efd;
}

.nav-link.active {
  background-color: #0d6efd;
  color: white !important;
  border-left: 4px solid #0d6efd;
}
</style>