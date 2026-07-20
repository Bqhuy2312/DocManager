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

      <div v-else class="access-insights">
        <article
          class="access-hero"
          role="button"
          tabindex="0"
          @click="viewDocument(topAccessDocument.id)"
          @keydown.enter="viewDocument(topAccessDocument.id)"
          @keydown.space.prevent="viewDocument(topAccessDocument.id)"
        >
          <div class="access-hero-top">
            <span class="access-kicker">Tài liệu nổi bật</span>
            <span class="access-rank-badge">
              <i class="fas fa-trophy"></i>
              Top 1
            </span>
          </div>

          <h3>{{ topAccessDocument.title }}</h3>
          <p>{{ topAccessDocument.author || "Không rõ" }} · {{ topAccessDocument.department || "Chưa có phòng ban" }}</p>

          <div class="access-hero-stats">
            <div>
              <strong>{{ topAccessDocument.access_count || 0 }}</strong>
              <span>Lượt truy cập</span>
            </div>
            <div>
              <strong>{{ totalPopularAccesses }}</strong>
              <span>Tổng top 5</span>
            </div>
          </div>

          <div class="access-hero-footer">
            <i class="fas fa-clock"></i>
            Lần cuối: {{ formatDateTime(topAccessDocument.last_accessed_at) }}
          </div>
        </article>

        <div class="access-ranking">
          <article
            v-for="(document, index) in popularDocuments"
            :key="document.id"
            class="access-rank-item"
            role="button"
            tabindex="0"
            @click="viewDocument(document.id)"
            @keydown.enter="viewDocument(document.id)"
            @keydown.space.prevent="viewDocument(document.id)"
          >
            <div class="rank-number">{{ index + 1 }}</div>

            <div class="rank-content">
              <div class="rank-main">
                <div>
                  <h3>{{ document.title }}</h3>
                  <p>{{ document.author || "Không rõ" }} · {{ document.department || "Chưa có phòng ban" }}</p>
                </div>
                <strong>{{ document.access_count || 0 }}</strong>
              </div>

              <div class="rank-progress" aria-hidden="true">
                <span :style="{ width: accessPercent(document) + '%' }"></span>
              </div>

              <div class="rank-meta">
                <span><i class="fas fa-eye me-1"></i>{{ document.access_count || 0 }} lượt</span>
                <span><i class="fas fa-clock me-1"></i>{{ formatDateTime(document.last_accessed_at) }}</span>
              </div>
            </div>
          </article>
        </div>
      </div>
    </section>

    <div class="dashboard-tabs">
      <button type="button" :class="{ active: activeTab === 'recent' }" @click="activeTab = 'recent'">
        <i class="fas fa-clock me-2"></i>Tài liệu gần đây
      </button>
      <button type="button" :class="{ active: activeTab === 'favorites' }" @click="activeTab = 'favorites'">
        <i class="fas fa-star me-2"></i>Đánh dấu
      </button>
      <button v-if="isAdmin" type="button" :class="{ active: activeTab === 'activity' }" @click="activeTab = 'activity'">
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
          @click="openActivityModal"
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
            <DocumentCard
              :document="document"
              @view="viewDocument"
              @toggle-favorite="toggleFavorite"
              @download="downloadDocument"
            />
          </div>
        </div>
      </template>
    </section>

    <div v-if="showActivityModal" class="activity-modal-backdrop" @click.self="closeActivityModal">
      <div class="activity-modal" role="dialog" aria-modal="true" aria-labelledby="activity-modal-title">
        <div class="activity-modal-header">
          <div>
            <h2 id="activity-modal-title">Tất cả hoạt động</h2>
            <p>{{ activityPagination.total }} log trong hệ thống</p>
          </div>

          <button type="button" class="activity-modal-close" aria-label="Đóng" @click="closeActivityModal">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div ref="activityModalBody" class="activity-modal-body">
          <div class="activity-modal-filters">
            <label class="activity-search-field">
              <span>Từ khóa</span>
              <div>
                <i class="fas fa-search"></i>
                <input
                  v-model="activitySearch"
                  type="search"
                  placeholder="Tên, email, tài liệu hoặc hành động..."
                  @input="scheduleActivitySearch"
                >
              </div>
            </label>

            <label>
              <span>Từ ngày</span>
              <input
                v-model="activityDateFrom"
                type="date"
                :max="activityDateTo || undefined"
                @change="applyActivityFilters"
              >
            </label>

            <label>
              <span>Đến ngày</span>
              <input
                v-model="activityDateTo"
                type="date"
                :min="activityDateFrom || undefined"
                @change="applyActivityFilters"
              >
            </label>

            <button
              type="button"
              class="activity-filter-reset"
              :disabled="!activitySearch && !activityDateFrom && !activityDateTo"
              title="Xóa bộ lọc"
              @click="resetActivityFilters"
            >
              <i class="fas fa-filter-circle-xmark"></i>
              Xóa lọc
            </button>
          </div>

          <div v-if="activityModalLoading" class="activity-modal-loading">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Đang tải lịch sử hoạt động...</span>
          </div>

          <p v-else-if="activityModalError" class="alert alert-danger">{{ activityModalError }}</p>

          <p v-else-if="!activityModalLogs.length" class="activity-modal-empty">
            Không tìm thấy hoạt động phù hợp với bộ lọc.
          </p>

          <div v-else class="activity-list">
            <article v-for="activity in activityModalLogs" :key="`modal-${activity.id}`" class="activity-item">
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
                    @click="closeActivityModal"
                  >
                    {{ activity.document_title || activity.target_label }}
                  </router-link>
                  <strong v-else>{{ targetLabel(activity) }}</strong>
                </p>
                <time>{{ formatDateTime(activity.created_at) }}</time>
              </div>
            </article>
          </div>

          <PaginationControls
            v-if="!activityModalLoading && !activityModalError"
            :page="activityPage"
            :per-page="activityPagination.per_page"
            :total="activityPagination.total"
            :scroll-on-change="false"
            @update:page="changeActivityPage"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Loading from "@/components/common/Loading.vue";
import DocumentCard from "@/components/common/DocumentCard.vue";
import PaginationControls from "@/components/common/PaginationControls.vue";
import { getDashboard, getDashboardActivities } from "@/services/dashboardService";
import { downloadDocumentFile, toggleFavoriteDocument } from "@/services/documentService";
import { subscribeRealtimeActivity } from "@/services/realtimeService";
import realtimeRefresh from "@/mixins/realtimeRefresh";

export default {
  name: "Dashboard",
  mixins: [realtimeRefresh],
  realtimeScopes: ["document", "folder", "member", "department", "backup"],
  components: { Loading, DocumentCard, PaginationControls },
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
      activityModalLogs: [],
      activityModalLoading: false,
      activityModalError: "",
      activitySearch: "",
      activityDateFrom: "",
      activityDateTo: "",
      activityPage: 1,
      activityPagination: {
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total: 0,
      },
      activitySearchTimer: null,
      activityRealtimeTimer: null,
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
    currentUser() {
      try {
        return JSON.parse(localStorage.getItem("user")) || null;
      } catch {
        return null;
      }
    },
    isAdmin() {
      return this.currentUser?.role === "admin";
    },
    activeDocuments() {
      return this.activeTab === "recent" ? this.recentDocuments : this.favoriteDocuments;
    },
    visibleActivities() {
      return this.activities.slice(0, 10);
    },
    topAccessDocument() {
      return this.popularDocuments[0] || {};
    },
    maxPopularAccessCount() {
      return Math.max(...this.popularDocuments.map((document) => document.access_count || 0), 1);
    },
    totalPopularAccesses() {
      return this.popularDocuments.reduce((total, document) => total + (document.access_count || 0), 0);
    },
    sectionTitle() {
      if (this.activeTab === "favorites") return "Tài liệu đã đánh dấu";
      if (this.activeTab === "activity") return "Hoạt động gần đây";
      return "Tài liệu gần đây";
    },
  },
  async mounted() {
    await this.loadDashboard();
    if (this.isAdmin) {
      this.unsubscribeActivity = subscribeRealtimeActivity(this.handleRealtimeActivity);
    }
  },
  beforeUnmount() {
    this.unsubscribeActivity?.();
    window.clearTimeout(this.activitySearchTimer);
    window.clearTimeout(this.activityRealtimeTimer);
  },
  methods: {
    refreshRealtimeData() {
      return this.loadDashboard();
    },
    async loadDashboard() {
      this.loading = true;
      this.error = "";

      try {
        const data = await getDashboard();
        this.stats = data.stats || this.stats;
        this.recentDocuments = data.recent_documents || [];
        this.favoriteDocuments = data.favorite_documents || [];
        this.popularDocuments = data.popular_documents || [];
        this.activities = this.isAdmin ? (data.activities || []) : [];
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể tải dữ liệu dashboard.";
      } finally {
        this.loading = false;
      }
    },
    viewDocument(id) {
      this.$router.push(`/documents/${id}`);
    },
    openActivityModal() {
      this.showActivityModal = true;
      this.activityPage = 1;
      this.loadActivityLogs(1);
    },
    closeActivityModal() {
      this.showActivityModal = false;
      window.clearTimeout(this.activitySearchTimer);
      window.clearTimeout(this.activityRealtimeTimer);
    },
    async loadActivityLogs(page = this.activityPage) {
      if (!this.isAdmin) return;

      this.activityModalLoading = true;
      this.activityModalError = "";

      try {
        const data = await getDashboardActivities({
          page,
          search: this.activitySearch.trim() || undefined,
          date_from: this.activityDateFrom || undefined,
          date_to: this.activityDateTo || undefined,
        });
        this.activityModalLogs = data.data || [];
        this.activityPagination = { ...this.activityPagination, ...(data.pagination || {}) };
        this.activityPage = this.activityPagination.current_page || page;
      } catch (error) {
        this.activityModalError = error.response?.data?.message || "Không thể tải lịch sử hoạt động.";
      } finally {
        this.activityModalLoading = false;
      }
    },
    scheduleActivitySearch() {
      window.clearTimeout(this.activitySearchTimer);
      this.activitySearchTimer = window.setTimeout(() => {
        this.activityPage = 1;
        this.loadActivityLogs(1);
      }, 350);
    },
    applyActivityFilters() {
      window.clearTimeout(this.activitySearchTimer);
      this.activityPage = 1;
      this.loadActivityLogs(1);
    },
    resetActivityFilters() {
      this.activitySearch = "";
      this.activityDateFrom = "";
      this.activityDateTo = "";
      this.applyActivityFilters();
    },
    changeActivityPage(page) {
      this.activityPage = page;
      this.loadActivityLogs(page).then(() => {
        this.$refs.activityModalBody?.scrollTo({ top: 0, behavior: "smooth" });
      });
    },
    async downloadDocument(document) {
      if (document.status !== "approved") return;
      try {
        await downloadDocumentFile(document);
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể tải xuống tài liệu.";
      }
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
      if (!this.isAdmin) return;

      const activity = payload.activity;
      if (!activity?.id) return;

      this.activities = [activity, ...this.activities]
        .filter((item, index, items) => items.findIndex((candidate) => candidate.id === item.id) === index)
        .slice(0, 10);

      if (this.showActivityModal) {
        window.clearTimeout(this.activityRealtimeTimer);
        this.activityRealtimeTimer = window.setTimeout(() => this.loadActivityLogs(this.activityPage), 350);
      }

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
    accessPercent(document) {
      return Math.max(8, Math.round(((document.access_count || 0) / this.maxPopularAccessCount) * 100));
    },
    actionLabel(action) {
      if (action === "viewed") return "đã truy cập";

      return {
        login: "đã đăng nhập vào",
        register: "đã đăng ký tài khoản tại",
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
  padding: 18px;
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

.access-insights {
  display: grid;
  grid-template-columns: minmax(240px, 0.72fr) minmax(0, 1.55fr);
  gap: 14px;
}

.access-hero,
.access-rank-item {
  border: 1px solid #dededb;
  border-radius: 8px;
  cursor: pointer;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.access-hero:hover,
.access-hero:focus,
.access-rank-item:hover,
.access-rank-item:focus {
  border-color: #171717;
  box-shadow: 0 12px 28px rgba(23, 23, 23, 0.1);
  outline: 0;
  transform: translateY(-2px);
}

.access-hero {
  display: grid;
  align-content: space-between;
  min-height: 224px;
  padding: 16px;
  background:
    linear-gradient(135deg, rgba(23, 23, 23, 0.94), rgba(55, 55, 50, 0.92)),
    #171717;
  color: #fff;
}

.access-hero-top,
.access-hero-footer,
.rank-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.access-kicker {
  color: #d9d9d3;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.access-rank-badge {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 5px 9px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
  color: #fff;
  font-size: 0.78rem;
  font-weight: 800;
}

.access-rank-badge i {
  color: #f7c948;
}

.access-hero h3 {
  margin: 18px 0 6px;
  color: #ffffff;
  font-size: 1.12rem;
  font-weight: 850;
  line-height: 1.25;
}

.access-hero p {
  margin: 0;
  color: #e7e7e2;
}

.access-hero-stats {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  margin: 18px 0;
}

.access-hero-stats div {
  padding: 10px 12px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.08);
}

.access-hero-stats strong {
  display: block;
  color: #ffffff;
  font-size: 1.25rem;
  line-height: 1;
}

.access-hero-stats span,
.access-hero-footer {
  color: #e0e0dc;
  font-size: 0.82rem;
}

.access-hero-footer {
  justify-content: flex-start;
  padding-top: 12px;
  border-top: 1px solid rgba(255, 255, 255, 0.14);
}

.access-ranking {
  display: grid;
  gap: 8px;
}

.access-rank-item {
  display: grid;
  grid-template-columns: 34px minmax(0, 1fr);
  gap: 12px;
  padding: 11px 12px;
  background: #fff;
}

.rank-number {
  display: grid;
  width: 34px;
  height: 34px;
  place-items: center;
  border-radius: 8px;
  background: #f1f1ef;
  color: #171717;
  font-weight: 850;
}

.rank-content {
  min-width: 0;
}

.rank-main {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
}

.rank-main h3 {
  margin: 0 0 4px;
  color: #171717;
  font-size: 0.92rem;
  font-weight: 850;
  line-height: 1.25;
}

.rank-main p {
  margin: 0;
  color: #707070;
  font-size: 0.82rem;
}

.rank-main strong {
  display: inline-grid;
  min-width: 34px;
  height: 28px;
  place-items: center;
  border-radius: 999px;
  background: #171717;
  color: #fff;
  font-size: 0.95rem;
}

.rank-progress {
  height: 6px;
  overflow: hidden;
  margin: 9px 0 7px;
  border-radius: 999px;
  background: #ededeb;
}

.rank-progress span {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, #171717, #0f766e);
}

.rank-meta {
  justify-content: flex-start;
  color: #707070;
  font-size: 0.78rem;
}

:global(body.theme-dark) .popular-documents-section,
:global(body.theme-dark) .access-rank-item {
  border-color: #3d3d44;
  background: #232329;
}

:global(body.theme-dark) .access-hero,
:global(body.theme-dark) .access-rank-item:hover,
:global(body.theme-dark) .access-rank-item:focus {
  border-color: #6b6b73;
}

:global(body.theme-dark) .rank-number,
:global(body.theme-dark) .rank-progress {
  background: #323238;
}

:global(body.theme-dark) .rank-main h3,
:global(body.theme-dark) .rank-number {
  color: #f7f7f5;
}

:global(body.theme-dark) .rank-main p,
:global(body.theme-dark) .rank-meta {
  color: #b9b9bd;
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

.activity-modal-filters {
  display: grid;
  grid-template-columns: minmax(240px, 1fr) minmax(142px, 0.55fr) minmax(142px, 0.55fr) auto;
  gap: 12px;
  align-items: end;
  margin-bottom: 24px;
  padding-bottom: 20px;
  border-bottom: 1px solid #dededb;
}

.activity-modal-filters label {
  display: grid;
  gap: 7px;
  min-width: 0;
  color: #555;
  font-size: 0.78rem;
  font-weight: 700;
}

.activity-modal-filters input {
  width: 100%;
  min-width: 0;
  height: 40px;
  padding: 0 11px;
  border: 1px solid #d6d6d2;
  border-radius: 6px;
  background: #fff;
  color: #171717;
  outline: none;
}

.activity-modal-filters input:focus {
  border-color: #171717;
  box-shadow: 0 0 0 3px rgba(23, 23, 23, 0.08);
}

.activity-search-field > div {
  position: relative;
}

.activity-search-field i {
  position: absolute;
  top: 50%;
  left: 12px;
  color: #777;
  transform: translateY(-50%);
}

.activity-search-field input {
  padding-left: 36px;
}

.activity-filter-reset {
  display: inline-flex;
  height: 40px;
  align-items: center;
  justify-content: center;
  gap: 7px;
  padding: 0 13px;
  border: 1px solid #cfcfcb;
  border-radius: 6px;
  background: #fff;
  color: #292929;
  font-weight: 700;
  white-space: nowrap;
}

.activity-filter-reset:hover:not(:disabled) {
  border-color: #171717;
  background: #f4f4f2;
}

.activity-filter-reset:disabled {
  cursor: not-allowed;
  opacity: 0.45;
}

.activity-modal-loading,
.activity-modal-empty {
  display: flex;
  min-height: 180px;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: #707070;
  text-align: center;
}

:global(body.theme-dark) .activity-modal {
  border-color: #44444a;
  background: #222226;
  color: #f4f4f4;
}

:global(body.theme-dark) .activity-modal-header,
:global(body.theme-dark) .activity-modal-filters {
  border-color: #44444a;
}

:global(body.theme-dark) .activity-modal-header p,
:global(body.theme-dark) .activity-modal-filters label,
:global(body.theme-dark) .activity-modal-empty,
:global(body.theme-dark) .activity-modal-loading {
  color: #bcbcc2;
}

:global(body.theme-dark) .activity-modal-close,
:global(body.theme-dark) .activity-modal-filters input,
:global(body.theme-dark) .activity-filter-reset {
  border-color: #4c4c52;
  background: #2d2d32;
  color: #f4f4f4;
}

:global(body.theme-dark) .activity-modal-close:hover,
:global(body.theme-dark) .activity-filter-reset:hover:not(:disabled) {
  background: #38383e;
}

@media (max-width: 850px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .access-insights {
    grid-template-columns: 1fr;
  }

  .activity-modal-filters {
    grid-template-columns: 1fr 1fr;
  }

  .activity-search-field {
    grid-column: 1 / -1;
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

  .activity-modal-backdrop {
    padding: 10px;
  }

  .activity-modal-filters {
    grid-template-columns: 1fr;
  }

  .activity-search-field {
    grid-column: auto;
  }
}
</style>

