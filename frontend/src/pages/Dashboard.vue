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
      </div>

      <p v-if="loading" class="text-muted">Đang tải dữ liệu dashboard...</p>

      <template v-else-if="activeTab === 'activity'">
        <p v-if="!activities.length" class="text-muted">Chưa có hoạt động gần đây.</p>

        <div v-else class="activity-list">
          <article v-for="activity in activities" :key="activity.id" class="activity-item">
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
                  {{ activity.document_title }}
                </router-link>
                <strong v-else>{{ activity.document_title }}</strong>
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
                  <span><i class="fas fa-weight-hanging me-1"></i>Kích thước: {{ formatFileSize(document.file_size) }}</span>
                  <span><i class="fas fa-upload me-1"></i>Tải lên: {{ formatDate(document.created_at) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </section>
  </div>
</template>

<script>
import { getDashboard } from "@/services/dashboardService";
import { toggleFavoriteDocument } from "@/services/documentService";

export default {
  name: "Dashboard",
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
      activities: [],
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
    sectionTitle() {
      if (this.activeTab === "favorites") return "Tài liệu đã đánh dấu";
      if (this.activeTab === "activity") return "Hoạt động gần đây";
      return "Tài liệu gần đây";
    },
  },
  async mounted() {
    await this.loadDashboard();
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
    actionLabel(action) {
      return {
        uploaded: "đã tải lên",
        approved: "đã phê duyệt",
        rejected: "đã từ chối",
        favorited: "đã đánh dấu",
        unfavorited: "đã bỏ đánh dấu",
        deleted: "đã xóa",
      }[action] || "đã thao tác với";
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
      return new Date(date).toLocaleDateString("vi-VN");
    },
    formatDateTime(date) {
      if (!date) return "Chưa có";
      return new Date(date).toLocaleString("vi-VN", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });
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

.section-heading a {
  color: #171717;
  font-weight: 700;
  text-decoration: none;
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
