<template>
  <nav class="navbar navbar-expand-lg doc-navbar">
    <div class="container-fluid navbar-layout">
      <router-link to="/dashboard" class="navbar-brand">
        <strong><i class="fas fa-file me-2"></i>Quản lý Tài liệu Nội Bộ</strong>
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
              <div class="notification-header-actions">
                <button v-if="hasReadNotifications" type="button" @click="deleteRead">Xóa đã đọc</button>
                <button v-if="unreadCount" type="button" @click="markAllRead">Đánh dấu đã đọc</button>
              </div>
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

        <div class="dropdown account-dropdown">
          <button
            class="account-trigger"
            type="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            aria-label="Mở menu tài khoản"
          >
            <span class="user-summary">
              <strong>{{ currentUser?.full_name || "Tài khoản" }}</strong>
              <small>{{ roleLabel }}</small>
            </span>
            <span class="avatar-button">
              <img v-if="currentUser?.avatar" :src="currentUser.avatar" alt="Avatar" class="avatar-image">
              <i v-else class="fas fa-user"></i>
            </span>
            <i class="fas fa-chevron-down account-chevron" aria-hidden="true"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end account-menu">
            <li>
              <router-link to="/settings" class="dropdown-item account-menu-item">
                <span class="account-menu-icon"><i class="fas fa-cog"></i></span>
                <span class="account-menu-copy">
                  <strong>Cài đặt</strong>
                  <small>Tùy chỉnh tài khoản</small>
                </span>
              </router-link>
            </li>
            <li>
              <button class="dropdown-item account-menu-item logout-item" type="button" @click="logout">
                <span class="account-menu-icon"><i class="fas fa-door-open"></i></span>
                <span class="account-menu-copy">
                  <strong>Đăng xuất</strong>
                  <small>Kết thúc phiên làm việc</small>
                </span>
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
  deleteReadNotifications,
  getNotifications,
  markAllNotificationsAsRead,
  markNotificationAsRead,
} from "@/services/notificationApiService";
import {
  connectRealtime,
  disconnectRealtime,
  isRealtimeConnected,
} from "@/services/realtimeService";

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
    this.connectNotifications();
    this.notificationPoller = window.setInterval(() => {
      if (!isRealtimeConnected()) this.fetchNotifications(true);
    }, 30000);
  },
  beforeUnmount() {
    window.removeEventListener("user-updated", this.refreshUser);
    window.clearInterval(this.notificationPoller);
    disconnectRealtime();
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
    hasReadNotifications() {
      return this.notifications.some((item) => item.is_read);
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
      this.connectNotifications();
    },
    submitSearch() {
      const query = this.searchQuery.trim();
      this.$router.push({ path: "/documents", query: query ? { search: query } : {} });
    },
    logout() {
      disconnectRealtime();
      localStorage.removeItem("user");
      localStorage.removeItem("token");
      this.$router.push("/login");
    },
    connectNotifications() {
      const user = this.getStoredUser();
      if (!user?.id || !localStorage.getItem("token")) return;

      connectRealtime(user.id, {
        onNotification: this.handleRealtimeNotification,
        onNotificationState: this.handleRealtimeNotificationState,
      });
    },
    handleRealtimeNotification(payload = {}) {
      const notification = payload.notification;
      if (!notification?.id || this.knownUnreadIds.has(notification.id)) return;

      this.notifications = [notification, ...this.notifications]
        .filter((item, index, items) => items.findIndex((candidate) => candidate.id === item.id) === index)
        .slice(0, 20);
      this.unreadCount = payload.unread_count ?? this.unreadCount + 1;
      this.knownUnreadIds = new Set([
        ...this.knownUnreadIds,
        notification.id,
      ]);
      this.initializedNotifications = true;

      notify({
        title: notification.title,
        message: notification.message,
        type: notification.type === "rejected" ? "warning" : "info",
      });
    },
    handleRealtimeNotificationState(payload = {}) {
      if (typeof payload.unread_count === "number") {
        this.unreadCount = payload.unread_count;
      }
      this.fetchNotifications(false);
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
    async deleteRead() {
      try {
        const result = await deleteReadNotifications();
        this.notifications = this.notifications.filter((item) => !item.is_read);
        this.unreadCount = result.unread_count ?? this.unreadCount;
        notify({
          title: "Đã xóa thông báo",
          message: `${result.deleted || 0} thông báo đã đọc đã được xóa.`,
        });
      } catch {
        notify({
          title: "Không thể xóa thông báo",
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

.user-summary strong {
  max-width: 160px;
  overflow: hidden;
  font-size: 0.86rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.user-summary small {
  margin-top: 2px;
  color: #707070;
  font-size: 0.72rem;
}

.account-trigger {
  display: flex;
  min-width: 0;
  align-items: center;
  gap: 9px;
  padding: 4px 7px 4px 10px;
  border: 1px solid transparent;
  border-radius: 8px;
  background: transparent;
  color: #171717;
  transition: background-color 0.2s ease, border-color 0.2s ease;
}

.account-trigger:hover,
.account-trigger:focus,
.account-trigger[aria-expanded="true"] {
  border-color: #e5e5e2;
  background: #f5f5f3;
}

.account-chevron {
  color: #8a8a84;
  font-size: 0.64rem;
  transition: transform 0.2s ease;
}

.account-trigger[aria-expanded="true"] .account-chevron {
  transform: rotate(180deg);
}

.avatar-button {
  display: flex;
  width: 38px;
  height: 38px;
  flex: 0 0 38px;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border: 2px solid #fff;
  border-radius: 50%;
  background: #171717;
  color: #fff;
  box-shadow: 0 0 0 1px #d7d7d3;
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

.account-menu {
  width: 245px;
  padding: 6px;
}

.account-menu li + li {
  margin-top: 3px;
}

.account-menu .account-menu-item {
  display: grid;
  width: 100%;
  grid-template-columns: 34px minmax(0, 1fr);
  align-items: center;
  gap: 10px;
  padding: 9px;
  border: 0;
  border-radius: 6px;
  background: transparent;
  text-align: left;
}

.account-menu-icon {
  display: grid;
  width: 34px;
  height: 34px;
  place-items: center;
  border-radius: 6px;
  background: #ededeb;
  color: #292929;
}

.account-menu-copy {
  display: grid;
  min-width: 0;
  line-height: 1.25;
}

.account-menu-copy strong {
  font-size: 0.86rem;
}

.account-menu-copy small {
  margin-top: 2px;
  color: #797973;
  font-size: 0.72rem;
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

.notification-header-actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 8px;
}

.notification-header button,
.notification-header-actions button {
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

.logout-item .account-menu-icon {
  background: #fff0f0;
  color: #dc2626;
}

.logout-item .account-menu-copy small {
  color: #a86b6b;
}

.logout-item:hover,
.logout-item:focus {
  background: #fff1f1;
  color: #b91c1c;
}

:global(body.theme-dark) .account-trigger {
  color: #f4f4f5;
}

:global(body.theme-dark) .account-trigger:hover,
:global(body.theme-dark) .account-trigger:focus,
:global(body.theme-dark) .account-trigger[aria-expanded="true"] {
  border-color: #3f3f46;
  background: #2b2b30;
}

:global(body.theme-dark) .account-menu-icon {
  background: #34343a;
  color: #f4f4f5;
}

:global(body.theme-dark) .account-menu-copy small {
  color: #a1a1aa;
}

:global(body.theme-dark) .logout-item .account-menu-icon {
  background: #3a2528;
  color: #f87171;
}

:global(body.theme-dark) .logout-item .account-menu-copy small {
  color: #d49a9a;
}

@media (max-width: 850px) {
  .navbar-layout {
    grid-template-columns: auto minmax(180px, 1fr) auto;
    gap: 10px;
  }

  .user-summary {
    display: none;
  }

  .account-trigger {
    padding-left: 7px;
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
