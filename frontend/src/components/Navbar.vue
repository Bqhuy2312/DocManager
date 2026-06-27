<template>
  <nav class="navbar navbar-expand-lg doc-navbar">
    <div class="container-fluid navbar-layout">
      <router-link to="/dashboard" class="navbar-brand">
        <strong><i class="fas fa-file me-2"></i>Hệ thống Quản lý Tài liệu Nội Bộ</strong>
      </router-link>

      <form class="navbar-search" @submit.prevent="submitSearch">
        <i class="fas fa-search"></i>
        <input v-model="searchQuery" type="search" placeholder="Tìm kiếm tài liệu..." aria-label="Tìm kiếm tài liệu">
      </form>

      <div class="navbar-actions">
        <div class="dropdown">
          <button
            class="notification-button"
            type="button"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
            aria-label="Thông báo"
            @click="fetchNotifications(false)"
          >
            <i class="far fa-bell"></i>
            <span v-if="unreadCount" class="notification-count">{{ unreadCountLabel }}</span>
          </button>
          <div class="dropdown-menu dropdown-menu-end notification-menu">
            <div class="notification-header">
              <strong>Thông báo</strong>
              <button v-if="unreadCount" type="button" @click="markAllRead">Đánh dấu đã đọc</button>
            </div>
            <div v-if="notificationLoading" class="notification-empty">Đang tải...</div>
            <div v-else-if="!notifications.length" class="notification-empty">Chưa có thông báo.</div>
            <template v-else>
              <button
                v-for="item in notifications"
                :key="item.id"
                type="button"
                class="notification-item"
                :class="{ unread: !item.is_read }"
                @click="openNotification(item)"
              >
                <span class="notification-item-icon">
                  <i class="fas" :class="notificationIcon(item.type)"></i>
                </span>
                <span>
                  <strong>{{ item.title }}</strong>
                  <small>{{ item.message }}</small>
                  <time>{{ formatNotificationTime(item.created_at) }}</time>
                </span>
              </button>
            </template>
          </div>
        </div>

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
import { notify } from "@/services/notificationService";
import {
  getNotifications,
  markAllNotificationsAsRead,
  markNotificationAsRead,
} from "@/services/notificationApiService";

export default {
  name: "Navbar",
  data() {
    return {
      currentUser: this.getStoredUser(),
      searchQuery: "",
      notifications: [],
      unreadCount: 0,
      notificationLoading: false,
      notificationPoller: null,
      initializedNotifications: false,
      knownUnreadIds: new Set(),
    };
  },
  mounted() {
    window.addEventListener("user-updated", this.refreshUser);
    this.fetchNotifications(false);
    this.notificationPoller = window.setInterval(() => {
      this.fetchNotifications(true);
    }, 3000);
  },
  beforeUnmount() {
    window.removeEventListener("user-updated", this.refreshUser);
    window.clearInterval(this.notificationPoller);
  },
  computed: {
    roleLabel() {
      return {
        admin: "Quản trị viên",
        editor: "Biên tập viên",
        viewer: "Người xem",
      }[this.currentUser?.role] || "";
    },
    unreadCountLabel() {
      return this.unreadCount > 9 ? "9+" : String(this.unreadCount);
    },
  },
  methods: {
    getStoredUser() {
      try {
        return JSON.parse(localStorage.getItem("user"));
      } catch {
        return null;
      }
    },
    refreshUser() {
      this.currentUser = this.getStoredUser();
    },
    submitSearch() {
      const query = this.searchQuery.trim();
      this.$router.push({ path: "/documents", query: query ? { search: query } : {} });
    },
    logout() {
      localStorage.removeItem("user");
      localStorage.removeItem("token");
      this.$router.push("/login");
    },
    async fetchNotifications(showToast = false) {
      if (!localStorage.getItem("token")) return;

      this.notificationLoading = !this.initializedNotifications;
      try {
        const data = await getNotifications();
        const unreadItems = data.notifications.filter((item) => !item.is_read);
        const newUnreadItems = unreadItems.filter((item) => !this.knownUnreadIds.has(item.id));

        this.notifications = data.notifications;
        this.unreadCount = data.unread_count;

        if (showToast && this.initializedNotifications && newUnreadItems.length) {
          const newest = newUnreadItems[0];
          notify({
            title: newest.title,
            message: newest.message,
            type: newest.type === "rejected" ? "warning" : "info",
          });
        }

        this.knownUnreadIds = new Set(unreadItems.map((item) => item.id));
        this.initializedNotifications = true;
      } catch {
        // Navbar polling should stay quiet if the session expires or the server is unavailable.
      } finally {
        this.notificationLoading = false;
      }
    },
    async markRead(item) {
      if (!item || item.is_read) return;

      try {
        await markNotificationAsRead(item.id);
        await this.fetchNotifications(false);
      } catch {
        notify({
          title: "Không thể cập nhật thông báo",
          message: "Vui lòng thử lại sau.",
          type: "danger",
        });
      }
    },
    async openNotification(item) {
      if (!item) return;

      if (!item.is_read) {
        await this.markRead(item);
      }

      if (item.link && this.$route.path !== item.link) {
        this.$router.push(item.link);
      }
    },
    async markAllRead() {
      try {
        await markAllNotificationsAsRead();
        await this.fetchNotifications(false);
      } catch {
        notify({
          title: "Không thể cập nhật thông báo",
          message: "Vui lòng thử lại sau.",
          type: "danger",
        });
      }
    },
    notificationIcon(type) {
      return {
        upload: "fa-arrow-up",
        approved: "fa-check",
        rejected: "fa-xmark",
      }[type] || "fa-bell";
    },
    formatNotificationTime(value) {
      if (!value) return "";

      const date = new Date(value);
      const diff = Math.max(0, Date.now() - date.getTime());
      const minutes = Math.floor(diff / 60000);

      if (minutes < 1) return "Vừa xong";
      if (minutes < 60) return `${minutes} phút trước`;

      const hours = Math.floor(minutes / 60);
      if (hours < 24) return `${hours} giờ trước`;

      return date.toLocaleDateString("vi-VN");
    },
  },
};
</script>

<style scoped>
.doc-navbar {
  position: sticky;
  top: 0;
  z-index: 1030;
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

.notification-count {
  position: absolute;
  top: -3px;
  right: -3px;
  display: grid;
  min-width: 17px;
  height: 17px;
  place-items: center;
  padding: 0 4px;
  border-radius: 50%;
  background: #dc2626;
  color: #fff;
  font-size: 0.65rem;
  font-weight: 800;
  line-height: 1;
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

.notification-menu {
  width: min(380px, calc(100vw - 24px));
  padding: 0;
  overflow: hidden;
}

.notification-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px;
  border-bottom: 1px solid #dededb;
}

.notification-header button {
  border: 0;
  background: transparent;
  color: #707070;
  font-size: 0.78rem;
  font-weight: 700;
}

.notification-empty {
  padding: 18px 12px;
  color: #707070;
  text-align: center;
}

.notification-item {
  display: grid;
  width: 100%;
  grid-template-columns: auto minmax(0, 1fr);
  gap: 10px;
  padding: 12px;
  border: 0;
  border-bottom: 1px solid #eeeeec;
  background: #fff;
  color: #171717;
  text-align: left;
}

.notification-item:hover {
  background: #f8f8f7;
}

.notification-item.unread {
  background: #f5f7ff;
}

.notification-item-icon {
  display: grid;
  width: 30px;
  height: 30px;
  place-items: center;
  border-radius: 50%;
  background: #171717;
  color: #fff;
}

.notification-item strong,
.notification-item small,
.notification-item time {
  display: block;
}

.notification-item strong {
  font-size: 0.86rem;
}

.notification-item small {
  margin-top: 2px;
  color: #707070;
  font-size: 0.78rem;
}

.notification-item time {
  margin-top: 5px;
  color: #8a8a84;
  font-size: 0.72rem;
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
