<template>
  <nav class="navbar navbar-expand-lg doc-navbar">
    <div class="container-fluid navbar-layout">
      <router-link to="/dashboard" class="navbar-brand">
        <strong><i class="fas fa-file me-2"></i>DocManager</strong>
      </router-link>

      <form class="navbar-search" @submit.prevent="submitSearch">
        <i class="fas fa-search"></i>
        <input v-model="searchQuery" type="search" placeholder="Tìm kiếm tài liệu..." aria-label="Tìm kiếm tài liệu">
      </form>

      <div class="navbar-actions">
        <button class="notification-button" type="button" aria-label="Thông báo">
          <i class="far fa-bell"></i>
          <span class="notification-dot"></span>
        </button>

        <div class="user-summary">
          <strong>{{ currentUser?.full_name || "Tài khoản" }}</strong>
          <small>{{ roleLabel }}</small>
        </div>

        <div class="dropdown">
          <button
            class="avatar-button"
            type="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            aria-label="Mở menu tài khoản"
          >
            <img v-if="currentUser?.avatar" :src="currentUser.avatar" alt="Avatar" class="avatar-image">
            <i v-else class="fas fa-user"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <router-link to="/settings" class="dropdown-item">
                <i class="fas fa-cog me-2"></i>Cài đặt
              </router-link>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <button class="dropdown-item logout-item" type="button" @click="logout">
                <i class="fas fa-door-open me-2"></i>Đăng xuất
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </nav>
</template>

<script>
export default {
  name: 'Navbar',
  data() {
    return {
      currentUser: this.getStoredUser(),
      searchQuery: ""
    }
  },
  mounted() {
    window.addEventListener('user-updated', this.refreshUser)
  },
  beforeUnmount() {
    window.removeEventListener('user-updated', this.refreshUser)
  },
  computed: {
    roleLabel() {
      return {
        admin: "Quản trị viên",
        editor: "Biên tập viên",
        viewer: "Người xem"
      }[this.currentUser?.role] || ""
    }
  },
  methods: {
    getStoredUser() {
      try {
        return JSON.parse(localStorage.getItem('user'))
      } catch {
        return null
      }
    },
    refreshUser() {
      this.currentUser = this.getStoredUser()
    },
    submitSearch() {
      const query = this.searchQuery.trim()
      this.$router.push({ path: '/documents', query: query ? { search: query } : {} })
    },
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
  min-height: 56px;
  background: #fff;
  border-bottom: 1px solid #dededb;
}

.navbar-layout {
  display: grid;
  grid-template-columns: minmax(180px, 1fr) minmax(260px, 460px) minmax(250px, 1fr);
  gap: 18px;
  align-items: center;
}

.navbar-brand {
  font-size: 1.25rem;
  text-decoration: none;
  color: #171717 !important;
}

.navbar-search {
  display: flex;
  height: 36px;
  align-items: center;
  gap: 8px;
  padding: 0 12px;
  border: 1px solid #dededb;
  border-radius: 6px;
  background: #fbfbfa;
  color: #707070;
}

.navbar-search:focus-within {
  border-color: #171717;
}

.navbar-search input {
  width: 100%;
  border: 0;
  outline: 0;
  background: transparent;
  color: #171717;
  font-size: 0.85rem;
}

.navbar-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
}

.notification-button {
  position: relative;
  width: 34px;
  height: 34px;
  border: 0;
  border-radius: 50%;
  background: transparent;
  color: #292929;
}

.notification-button:hover {
  background: #f1f1ef;
}

.notification-dot {
  position: absolute;
  top: 7px;
  right: 7px;
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #171717;
}

.user-summary {
  display: grid;
  line-height: 1.2;
  text-align: right;
}

.user-summary small {
  margin-top: 2px;
  color: #707070;
  font-size: 0.72rem;
}

.avatar-button {
  display: flex;
  width: 34px;
  height: 34px;
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

.avatar-image {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}

.dropdown-menu {
  min-width: 155px;
  margin-top: 6px;
  border-color: #dededb;
  border-radius: 8px;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}

.dropdown-item {
  padding: 7px 12px;
  font-size: 0.875rem;
  color: #171717;
}

.dropdown-item:hover,
.dropdown-item:focus {
  background: #f1f1ef;
  color: #171717;
}

.logout-item {
  color: #dc2626;
}

.logout-item:hover,
.logout-item:focus {
  background: #fee2e2;
  color: #b91c1c;
}

@media (max-width: 850px) {
  .navbar-layout {
    grid-template-columns: auto minmax(180px, 1fr) auto;
    gap: 10px;
  }

  .user-summary {
    display: none;
  }
}

@media (max-width: 600px) {
  .navbar-layout {
    grid-template-columns: 1fr auto;
  }

  .navbar-search {
    display: none;
  }
}
</style>
