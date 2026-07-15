<template>
  <div class="container-fluid py-4">
    <div class="dashboard-header">
      <div>
        <h1>Trang chủ</h1>
        <p>Tổng quan tài liệu và hoạt động trong hệ thống.</p>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="stats-grid">
      <div v-for="item in statCards" :key="item.key" class="stat-card">
        <div class="stat-icon" :class="item.key">
          <i :class="item.icon"></i>
        </div>
        <div>
          <span>{{ item.label }}</span>
          <strong>{{ loading ? "..." : item.value }}</strong>
        </div>
      </div>
    </div>

    <section class="popular-documents-section">
      <div class="section-heading">
        <div>
          <h2>Số lượng truy cập</h2>
          <p>5 tài liệu được truy cập nhiều nhất trong hệ thống.</p>
        </div>
      </div>

      <Loading v-if="loading" type="list" :count="3" />

      <p v-else-if="!popularDocuments.length" class="text-muted">Chưa có dữ liệu truy cập tài liệu.</p>

      <div v-else class="popular-table-wrap">
        <table class="popular-table">
          <thead>
            <tr>
              <th>Tài liệu</th>
              <th>Người đăng</th>
              <th>Phòng ban</th>
              <th>Lượt truy cập</th>
              <th>Lần cuối</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="document in popularDocuments"
              :key="document.id"
              role="button"
              tabindex="0"
              @click="viewDocument(document.id)"
              @keydown.enter="viewDocument(document.id)"
              @keydown.space.prevent="viewDocument(document.id)"
            >
              <td>
                <strong>{{ document.title }}</strong>
              </td>
              <td>{{ document.author || "Không rõ" }}</td>
              <td>{{ document.department || "Chưa có phòng ban" }}</td>
              <td>
                <span class="access-count">{{ document.access_count }}</span>
              </td>
              <td>{{ formatDateTime(document.last_accessed_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <div class="dashboard-tabs">
      <button type="button" :class="{ active: activeTab === 'recent' }" @click="activeTab = 'recent'">
        <i class="fas fa-clock me-2"></i>Tài liệu gần đây
      </button>
      <button type="button" :class="{ active: activeTab === 'favorites' }" @click="activeTab = 'favorites'">
        <i class="fas fa-star me-2"></i>Đánh dấu
      </button>
      <button type="button" :class="{ active: activeTab === 'activity' }" @click="activeTab = 'activity'">
        <i class="fas fa-list-check me-2"></i>Hoạt động gần đây
      </button>
    </div>

    <section class="dashboard-section">
      <div class="section-heading">
        <div>
          <h2>{{ sectionTitle }}</h2>
          <p v-if="activeTab === 'activity'">Các hoạt động gần đây trong hệ thống</p>
        </div>

        <router-link v-if="activeTab !== 'activity'" :to="activeTab === 'recent' ? '/documents' : '/favorites'">
          Xem tất cả
          <i class="fas fa-arrow-right ms-1"></i>
        </router-link>
        <button
          v-else-if="activities.length"
          type="button"
          class="section-action"
          @click="showActivityModal = true"
        >
          Xem tất cả
          <i class="fas fa-arrow-right ms-1"></i>
        </button>
      </div>

      <Loading v-if="loading" :type="activeTab === 'activity' ? 'list' : 'cards'" :count="3" />

      <template v-else-if="activeTab === 'activity'">
        <p v-if="!activities.length" class="text-muted">Chưa có hoạt động gần đây.</p>

        <div v-else class="activity-list">
          <article v-for="activity in visibleActivities" :key="activity.id" class="activity-item">
            <div class="activity-avatar">
              <img v-if="activity.user_avatar" :src="activity.user_avatar" alt="Avatar">
              <span v-else>{{ initials(activity.user_name) }}</span>
            </div>

            <div class="activity-content">
              <p>
                <strong>{{ activity.user_name }}</strong>
                {{ actionLabel(activity.action) }}
                <router-link
                  v-if="activity.document_id && activity.action !== 'deleted'"
                  :to="`/documents/${activity.document_id}`"
                >
                  {{ activity.document_title || activity.target_label }}
                </router-link>
                <strong v-else>{{ targetLabel(activity) }}</strong>
              </p>
              <time>{{ formatDateTime(activity.created_at) }}</time>
            </div>
          </article>
        </div>
      </template>

      <template v-else>
        <p v-if="!activeDocuments.length" class="text-muted">
          {{ activeTab === "recent" ? "Chưa có tài liệu gần đây." : "Chưa có tài liệu đánh dấu." }}
        </p>

        <div v-else class="row g-4">
          <div v-for="document in activeDocuments" :key="document.id" class="col-md-6 col-xl-4">
            <div
              class="card dashboard-document-card h-100"
              role="button"
              tabindex="0"
              @click="viewDocument(document.id)"
              @keydown.enter="viewDocument(document.id)"
              @keydown.space.prevent="viewDocument(document.id)"
            >
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                  <div>
                    <h5 class="card-title fw-bold">{{ document.title }}</h5>

                    <span v-if="document.category" class="badge bg-primary me-2">
                      {{ document.category }}
                    </span>

                    <span v-if="document.folder" class="badge bg-success">
                      {{ document.folder }}
                    </span>
                  </div>

                  <button
                    class="favorite-star"
                    type="button"
                    :class="{ active: document.is_favorite }"
                    :aria-label="document.is_favorite ? 'Bỏ đánh dấu' : 'Thêm đánh dấu'"
                    @click.stop="toggleFavorite(document)"
                  >
                    <i class="fas fa-star"></i>
                  </button>
                </div>

                <p class="text-muted">
                  {{ document.description || "Không có mô tả." }}
                </p>

                <div class="mb-3">
                  <span
                    v-for="tag in document.tags || []"
                    :key="tag"
                    class="badge rounded-pill bg-light text-dark border me-1"
                  >
                    {{ tag }}
                  </span>
                </div>
              </div>

              <div class="card-footer bg-white">
                <div class="dashboard-document-meta">
                  <span><i class="fas fa-user me-1"></i>Người đăng: {{ document.author || "Không rõ" }}</span>
                  <span><i class="fas fa-building me-1"></i>Phòng ban: {{ document.department || "Chưa có phòng ban" }}</span>
                  <span><i class="fas fa-clock me-1"></i>Cập nhật: {{ formatDateTime(document.updated_at) }}</span>
                  <span><i class="fas fa-eye me-1"></i>Lượt truy cập: {{ document.access_count || 0 }}</span>
              <span><i class="fas fa-weight-hanging me-1"></i>Kích thước: {{ formatFileSize(document.file_size) }}</span>
                  <span><i class="fas fa-upload me-1"></i>Tải lên: {{ formatDate(document.created_at) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </section>

    <div v-if="showActivityModal" class="activity-modal-backdrop" @click.self="showActivityModal = false">
      <div class="activity-modal" role="dialog" aria-modal="true" aria-labelledby="activity-modal-title">
        <div class="activity-modal-header">
          <div>
            <h2 id="activity-modal-title">Tất cả hoạt động</h2>
            <p>{{ activities.length }} logs gần nhất trong hệ thống</p>
          </div>

          <button type="button" class="activity-modal-close" aria-label="Đóng" @click="showActivityModal = false">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="activity-modal-body">
          <p v-if="!activities.length" class="text-muted">Chưa có hoạt động gần đây.</p>

          <div v-else class="activity-list">
            <article v-for="activity in activities" :key="`modal-${activity.id}`" class="activity-item">
              <div class="activity-avatar">
                <img v-if="activity.user_avatar" :src="activity.user_avatar" alt="Avatar">
                <span v-else>{{ initials(activity.user_name) }}</span>
              </div>

              <div class="activity-content">
                <p>
                  <strong>{{ activity.user_name }}</strong>
                  {{ actionLabel(activity.action) }}
                  <router-link
                    v-if="activity.document_id && activity.action !== 'deleted'"
                    :to="`/documents/${activity.document_id}`"
                    @click="showActivityModal = false"
                  >
                    {{ activity.document_title || activity.target_label }}
                  </router-link>
                  <strong v-else>{{ targetLabel(activity) }}</strong>
                </p>
                <time>{{ formatDateTime(activity.created_at) }}</time>
              </div>
            </article>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Loading from "@/components/common/Loading.vue";
import { getDashboard } from "@/services/dashboardService";
import { toggleFavoriteDocument } from "@/services/documentService";
import { subscribeRealtimeActivity } from "@/services/realtimeService";

export default {
  name: "Dashboard",
  components: { Loading },
  data() {
    return {
      activeTab: "recent",
      stats: {
        documents: 0,
        folders: 0,
        favorites: 0,
        pending: 0,
        recent: 0,
      },
      recentDocuments: [],
      favoriteDocuments: [],
      popularDocuments: [],
      activities: [],
      showActivityModal: false,
      unsubscribeActivity: null,
      loading: false,
      error: "",
    };
  },
  computed: {
    statCards() {
      return [
        { key: "documents", label: "Tổng tài liệu", value: this.stats.documents, icon: "fas fa-file-alt" },
        { key: "folders", label: "Thư mục", value: this.stats.folders, icon: "fas fa-folder" },
        { key: "favorites", label: "Đánh dấu", value: this.stats.favorites, icon: "fas fa-star" },
        { key: "pending", label: "Chờ duyệt", value: this.stats.pending, icon: "fas fa-clock" },
      ];
    },
    activeDocuments() {
      return this.activeTab === "recent" ? this.recentDocuments : this.favoriteDocuments;
    },
    visibleActivities() {
      return this.activities.slice(0, 10);
    },
    sectionTitle() {
      if (this.activeTab === "favorites") return "Tài liệu đã đánh dấu";
      if (this.activeTab === "activity") return "Hoạt động gần đây";
      return "Tài liệu gần đây";
    },
  },
  async mounted() {
    await this.loadDashboard();
    this.unsubscribeActivity = subscribeRealtimeActivity(this.handleRealtimeActivity);
  },
  beforeUnmount() {
    this.unsubscribeActivity?.();
  },
  methods: {
    async loadDashboard() {
      this.loading = true;
      this.error = "";

      try {
        const data = await getDashboard();
        this.stats = data.stats || this.stats;
        this.recentDocuments = data.recent_documents || [];
        this.favoriteDocuments = data.favorite_documents || [];
        this.popularDocuments = data.popular_documents || [];
        this.activities = data.activities || [];
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể tải dữ liệu dashboard.";
      } finally {
        this.loading = false;
      }
    },
    viewDocument(id) {
      this.$router.push(`/documents/${id}`);
    },
    async toggleFavorite(document) {
      try {
        const result = await toggleFavoriteDocument(document.id);
        this.applyFavoriteState(document.id, result.is_favorite);
        this.stats.favorites += result.is_favorite ? 1 : -1;

        if (!result.is_favorite && this.activeTab === "favorites") {
          this.favoriteDocuments = this.favoriteDocuments.filter((item) => item.id !== document.id);
        }
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể cập nhật đánh dấu.";
      }
    },
    applyFavoriteState(id, isFavorite) {
      this.recentDocuments = this.recentDocuments.map((document) =>
        document.id === id ? { ...document, is_favorite: isFavorite } : document
      );
      this.favoriteDocuments = this.favoriteDocuments.map((document) =>
        document.id === id ? { ...document, is_favorite: isFavorite } : document
      );
    },
    handleRealtimeActivity(payload = {}) {
      const activity = payload.activity;
      if (!activity?.id) return;

      this.activities = [activity, ...this.activities]
        .filter((item, index, items) => items.findIndex((candidate) => candidate.id === item.id) === index);

      this.applyRealtimeAccessStat(activity);
    },
    applyRealtimeAccessStat(activity) {
      if (activity.action !== "viewed" || !activity.document_id) return;

      const existing = this.popularDocuments.find((document) => document.id === activity.document_id);
      if (existing) {
        existing.access_count += 1;
        existing.last_accessed_at = activity.created_at;
      } else {
        this.popularDocuments.push({
          id: activity.document_id,
          title: activity.document_title || activity.target_label || "Tài liệu",
          author: activity.user_name,
          department: "",
          access_count: 1,
          last_accessed_at: activity.created_at,
        });
      }

      this.popularDocuments = [...this.popularDocuments]
        .sort((a, b) => {
          if (b.access_count !== a.access_count) return b.access_count - a.access_count;
          return this.parseDate(b.last_accessed_at) - this.parseDate(a.last_accessed_at);
        })
        .slice(0, 5);
    },
    actionLabel(action) {
      if (action === "viewed") return "đã truy cập";

      return {
        login: "đã đăng nhập vào",
        guest_login: "đã đăng nhập với tư cách người xem vào",
        logout: "đã đăng xuất khỏi",
        uploaded: "đã tải lên",
        downloaded: "đã tải xuống",
        updated: "đã cập nhật",
        approved: "đã phê duyệt",
        rejected: "đã từ chối",
        favorited: "đã đánh dấu",
        unfavorited: "đã bỏ đánh dấu",
        deleted: "đã xóa",
      }[action] || "đã thao tác với";
    },
    targetLabel(activity) {
      return activity.target_label || activity.document_title || "hệ thống";
    },
    initials(name = "") {
      return name
        .split(" ")
        .filter(Boolean)
        .slice(-2)
        .map((part) => part[0])
        .join("")
        .toUpperCase() || "U";
    },
    formatDate(date) {
      if (!date) return "Chưa có";
      return this.parseDate(date).toLocaleDateString("vi-VN", {
        timeZone: "Asia/Ho_Chi_Minh",
      });
    },
    formatDateTime(date) {
      if (!date) return "Chưa có";
      return this.parseDate(date).toLocaleString("vi-VN", {
        timeZone: "Asia/Ho_Chi_Minh",
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });
    },
    parseDate(date) {
      if (!date || date instanceof Date) return date || new Date(0);
      if (typeof date !== "string") return new Date(date);

      const normalized = date.includes("T") ? date : date.replace(" ", "T");
      const hasTimezone = /(?:Z|[+-]\d{2}:?\d{2})$/i.test(normalized);

      return new Date(hasTimezone ? normalized : `${normalized}Z`);
    },
    formatFileSize(bytes) {
      if (!bytes) return "0 Bytes";
      const units = ["Bytes", "KB", "MB", "GB"];
      const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
      return `${Math.round((bytes / Math.pow(1024, index)) * 100) / 100} ${units[index]}`;
    },
  },
};
</script>

<style scoped>
.dashboard-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 22px;
}

.dashboard-header h1 {
  margin: 0;
  font-size: 1.8rem;
  font-weight: 800;
}

.dashboard-header p {
  margin: 6px 0 0;
  color: #707070;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.popular-documents-section {
  margin-bottom: 24px;
  padding: 22px;
  border: 1px solid #dededb;
  border-radius: 8px;
  background: #fff;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 14px;
  min-height: 104px;
  padding: 18px;
  border: 1px solid #dededb;
  border-radius: 8px;
  background: #fff;
}

.stat-icon {
  display: grid;
  width: 44px;
  height: 44px;
  flex: 0 0 44px;
  place-items: center;
  border-radius: 8px;
  background: #171717;
  color: #fff;
}

.stat-icon.folders {
  background: #0f766e;
}

.stat-icon.favorites {
  background: #b45309;
}

.stat-icon.pending {
  background: #7c3aed;
}

.stat-card span {
  display: block;
  color: #707070;
  font-size: 0.82rem;
}

.stat-card strong {
  display: block;
  margin-top: 3px;
  color: #171717;
  font-size: 1.55rem;
  line-height: 1;
}

.popular-table-wrap {
  overflow-x: auto;
}

.popular-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 720px;
}

.popular-table th,
.popular-table td {
  padding: 14px 12px;
  border-top: 1px solid #ededeb;
  vertical-align: middle;
}

.popular-table th {
  color: #707070;
  font-size: 0.78rem;
  font-weight: 800;
  text-transform: uppercase;
}

.popular-table tbody tr {
  cursor: pointer;
}

.popular-table tbody tr:hover,
.popular-table tbody tr:focus {
  background: #f7f7f5;
  outline: 0;
}

.popular-table strong {
  color: #171717;
}

.access-count {
  display: inline-grid;
  min-width: 34px;
  height: 28px;
  place-items: center;
  border-radius: 999px;
  background: #171717;
  color: #fff;
  font-weight: 800;
}

.dashboard-tabs {
  display: inline-flex;
  gap: 6px;
  margin-bottom: 16px;
  padding: 4px;
  border: 1px solid #dededb;
  border-radius: 8px;
  background: #fff;
}

.dashboard-tabs button {
  min-height: 34px;
  border: 0;
  border-radius: 6px;
  padding: 0 12px;
  background: transparent;
  color: #555;
  font-weight: 700;
}

.dashboard-tabs button.active {
  background: #171717;
  color: #fff;
}

.dashboard-section {
  padding: 24px;
  border: 1px solid #dededb;
  border-radius: 8px;
  background: #fff;
}

.section-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  margin-bottom: 22px;
}

.section-heading h2 {
  margin: 0;
  font-size: 1.45rem;
  font-weight: 800;
}

.section-heading p {
  margin: 5px 0 0;
  color: #707070;
}

.section-heading a,
.section-action {
  color: #171717;
  font-weight: 700;
  text-decoration: none;
}

.section-action {
  border: 0;
  background: transparent;
}

.section-action:hover {
  text-decoration: underline;
}

.dashboard-document-card {
  border: 1px solid #dededb;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.25s ease;
}

.dashboard-document-card:hover,
.dashboard-document-card:focus {
  transform: translateY(-4px);
  border-color: #171717;
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.08);
  outline: 0;
}

.card-title {
  font-size: 1.1rem;
}

.badge {
  font-weight: 500;
}

.favorite-star {
  display: inline-grid;
  width: 32px;
  height: 32px;
  flex: 0 0 32px;
  place-items: center;
  border: 0;
  border-radius: 50%;
  background: #f1f1ef;
  color: #9a9a93;
}

.favorite-star:hover,
.favorite-star.active {
  background: #fff7d6;
  color: #d99800;
}

.card-footer {
  border-top: 1px solid #dededb;
}

.dashboard-document-meta {
  display: grid;
  gap: 6px;
  color: #707070;
  font-size: 0.78rem;
}

.activity-list {
  display: grid;
  gap: 26px;
}

.activity-item {
  display: grid;
  grid-template-columns: 48px minmax(0, 1fr);
  gap: 18px;
  align-items: start;
}

.activity-avatar {
  display: grid;
  width: 48px;
  height: 48px;
  place-items: center;
  overflow: hidden;
  border-radius: 50%;
  background: #171717;
  color: #fff;
  font-weight: 800;
}

.activity-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.activity-content p {
  margin: 2px 0 5px;
  color: #555;
}

.activity-content strong {
  color: #171717;
}

.activity-content a {
  color: #171717;
  font-weight: 800;
  text-decoration: none;
}

.activity-content a:hover {
  text-decoration: underline;
}

.activity-content time {
  color: #707070;
  font-size: 0.85rem;
}

.activity-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 1050;
  display: grid;
  place-items: center;
  padding: 24px;
  background: rgba(0, 0, 0, 0.45);
}

.activity-modal {
  width: min(820px, 100%);
  max-height: min(760px, calc(100vh - 48px));
  display: grid;
  grid-template-rows: auto minmax(0, 1fr);
  border: 1px solid #dededb;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 22px 55px rgba(0, 0, 0, 0.22);
}

.activity-modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 20px 22px;
  border-bottom: 1px solid #dededb;
}

.activity-modal-header h2 {
  margin: 0;
  font-size: 1.3rem;
  font-weight: 800;
}

.activity-modal-header p {
  margin: 4px 0 0;
  color: #707070;
}

.activity-modal-close {
  display: grid;
  width: 38px;
  height: 38px;
  place-items: center;
  border: 1px solid #dededb;
  border-radius: 8px;
  background: #fff;
  color: #171717;
}

.activity-modal-close:hover {
  background: #f4f4f2;
}

.activity-modal-body {
  overflow: auto;
  padding: 22px;
}

@media (max-width: 850px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 700px) {
  .dashboard-header,
  .section-heading {
    display: grid;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>

