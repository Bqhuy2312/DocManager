<template>
  <div class="sidebar">
    <nav class="nav flex-column">
      <div class="nav-section">
        <div class="nav-heading">Chung</div>
        <router-link to="/dashboard" class="nav-link" active-class="active">
          <i class="fas fa-home me-2"></i>Trang chủ
        </router-link>
        <router-link to="/documents" class="nav-link" active-class="active">
          <i class="fas fa-book me-2"></i>Tất cả tài liệu
        </router-link>
        <router-link to="/favorites" class="nav-link" active-class="active">
          <i class="fas fa-star me-2"></i>Đánh dấu
        </router-link>
        <router-link to="/categories" class="nav-link" active-class="active">
          <i class="fas fa-tag me-2"></i>Danh mục
        </router-link>
        <router-link v-if="canUpload" to="/upload" class="nav-link" active-class="active">
          <i class="fas fa-arrow-up me-2"></i>Tải lên
        </router-link>
      </div>

      <div v-if="canManageFolders || isAdmin" class="nav-section">
        <div class="nav-heading">Quản lý</div>
        <router-link v-if="canManageFolders" to="/folders" class="nav-link" active-class="active">
          <i class="fas fa-folder me-2"></i>Thư mục
        </router-link>
        <router-link v-if="isAdmin" to="/members" class="nav-link" active-class="active">
          <i class="fas fa-users me-2"></i>Thành viên
        </router-link>
        <router-link v-if="isAdmin" to="/approvals" class="nav-link" active-class="active">
          <i class="fas fa-clipboard-check me-2"></i>Phê duyệt
        </router-link>
      </div>
    </nav>
  </div>
</template>

<script>
export default {
  name: 'Sidebar',
  computed: {
    canUpload() {
      return ['admin', 'editor'].includes(this.currentUser?.role)
    },
    canManageFolders() {
      return ['admin', 'editor'].includes(this.currentUser?.role)
    },
    isAdmin() {
      return this.currentUser?.role === 'admin'
    },
    currentUser() {
      try {
        return JSON.parse(localStorage.getItem('user'))
      } catch {
        return null
      }
    }
  }
}
</script>

<style scoped>
.sidebar {
  width: 200px;
  background-color: #fff;
  border-right: 1px solid #dededb;
  min-height: calc(100vh - 56px);
  padding: 16px 10px;
}

.nav {
  display: grid;
  align-content: start;
  gap: 16px;
}

.nav-section {
  display: grid;
  gap: 6px;
}

.nav-heading {
  padding: 0 12px 2px;
  color: #8a8a84;
  font-size: 0.78rem;
  font-weight: 700;
}

.nav-link {
  display: flex;
  min-height: 42px;
  align-items: center;
  padding: 8px 12px;
  border-radius: 6px;
  color: #292929;
  text-decoration: none;
}

.nav-link i {
  width: 18px;
  margin-right: 10px !important;
  text-align: center;
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
