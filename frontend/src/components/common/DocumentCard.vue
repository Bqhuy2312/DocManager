<template>
  <div
    class="card document-card h-100"
    role="button"
    tabindex="0"
    @click="$emit('view', document.id)"
    @keydown.enter="$emit('view', document.id)"
    @keydown.space.prevent="$emit('view', document.id)"
  >
    <div class="card-body document-card-body">
      <div class="document-card-heading">
        <div class="document-card-heading-content">
          <div class="document-title-line">
            <h5 class="card-title fw-bold">{{ document.title }}</h5>
            <span class="document-version">{{ versionLabel(document.version) }}</span>
          </div>

          <div class="document-card-categories">
            <span v-if="document.category" class="badge bg-primary">{{ document.category }}</span>
            <span v-if="document.folder" class="badge bg-success">{{ document.folder }}</span>
          </div>
        </div>

        <div class="document-card-tools">
          <span v-if="showStatus" class="document-status-badge">
            {{ statusLabel(document.status) }}
          </span>

          <div class="document-info-wrap">
            <button
              type="button"
              class="document-info-button"
              aria-label="Xem thông tin tài liệu"
              @click.stop
            >
              <i class="fas fa-exclamation"></i>
            </button>
            <div class="document-info-tooltip" role="tooltip">
              <strong>Thông tin tài liệu</strong>
              <span><i class="fas fa-user"></i>{{ document.author || "Không rõ" }}</span>
              <span><i class="fas fa-building"></i>{{ document.department || "Chưa có phòng ban" }}</span>
              <span><i class="fas fa-eye"></i>{{ document.access_count || 0 }} lượt truy cập</span>
            </div>
          </div>

          <button
            v-if="showFavorite"
            class="favorite-star"
            type="button"
            :class="{ active: document.is_favorite }"
            :aria-label="document.is_favorite ? 'Bỏ đánh dấu' : 'Thêm đánh dấu'"
            @click.stop="$emit('toggle-favorite', document)"
          >
            <i class="fas fa-star"></i>
          </button>
        </div>
      </div>

      <p class="text-muted document-description">
        {{ document.description || "Không có mô tả." }}
      </p>

      <div v-if="document.tags?.length" class="document-tags">
        <span v-for="tag in document.tags" :key="tag" class="badge rounded-pill bg-light text-dark border">
          {{ tag }}
        </span>
      </div>
    </div>

    <div class="card-footer document-card-footer">
      <div class="document-card-meta">
        <span><i class="fas fa-clock"></i>Cập nhật: {{ formatDateTime(document.updated_at) }}</span>
        <span><i class="fas fa-weight-hanging"></i>Kích thước: {{ formatFileSize(document.file_size) }}</span>
        <span><i class="fas fa-upload"></i>Tải lên: {{ formatDate(document.created_at) }}</span>
      </div>

      <div class="document-card-actions">
        <template v-if="showApprovalActions && document.status === 'pending'">
          <button class="btn btn-outline-secondary btn-sm" type="button" @click.stop="$emit('approve', document, 'rejected')">
            <i class="fas fa-xmark me-1"></i>Từ chối
          </button>
          <button class="btn btn-success btn-sm" type="button" @click.stop="$emit('approve', document, 'approved')">
            <i class="fas fa-check me-1"></i>Phê duyệt
          </button>
        </template>

        <button
          v-else-if="showDownload && document.status === 'approved'"
          class="btn btn-outline-success btn-sm"
          type="button"
          @click.stop="$emit('download', document)"
        >
          <i class="fas fa-download me-1"></i>Tải xuống
        </button>
        <span
          v-else-if="document.status === 'pending'"
          class="document-status-badge-2">Chờ phê duyệt</span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "DocumentCard",
  props: {
    document: { type: Object, required: true },
    showFavorite: { type: Boolean, default: true },
    showDownload: { type: Boolean, default: true },
    showStatus: { type: Boolean, default: false },
    showApprovalActions: { type: Boolean, default: false },
  },
  emits: ["view", "toggle-favorite", "download", "approve"],
  methods: {
    parseDate(date) {
      if (!date) return new Date(0);
      if (date instanceof Date) return date;
      const value = typeof date === "string" && !date.includes("T") ? date.replace(" ", "T") : date;
      return new Date(typeof value === "string" && !/(?:Z|[+-]\d{2}:?\d{2})$/i.test(value) ? `${value}Z` : value);
    },
    formatDate(date) {
      return this.parseDate(date).toLocaleDateString("vi-VN", { timeZone: "Asia/Ho_Chi_Minh" });
    },
    formatDateTime(date) {
      return this.parseDate(date).toLocaleString("vi-VN", {
        timeZone: "Asia/Ho_Chi_Minh",
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
    statusLabel(status) {
      return {
        approved: "Đã phê duyệt",
        pending: "Chờ phê duyệt",
        rejected: "Đã từ chối",
        draft: "Bản nháp",
      }[status] || status;
    },
    versionLabel(version) {
      const value = String(version || "1.0");
      return value.toLowerCase().startsWith("v") ? value : `v${value}`;
    },
  },
};
</script>

<style scoped>
.document-card {
  width: 100%;
  margin: 0;
  position: relative;
  display: flex;
  flex-direction: column;
  overflow: visible;
  border: 1px solid #deded9;
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
  box-shadow: 0 3px 10px rgba(23, 23, 23, 0.045);
  transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
}

.document-card:hover,
.document-card:focus {
  transform: translateY(-4px);
  border-color: #bdbdb6;
  box-shadow: 0 14px 28px rgba(23, 23, 23, 0.12);
  outline: 0;
}

.document-status-badge {
  display: inline-flex;
  max-width: 100px;
  align-items: center;
  overflow: hidden;
  padding: 4px 8px;
  border: 1px solid #dededb;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.94);
  color: #171717;
  font-size: 0.72rem;
  font-weight: 700;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.document-status-badge-2{
  display: inline-flex;
  max-width: 100px;
  align-items: center;
  overflow: hidden;
  padding: 4px 8px;
  border: 1px solid #dededb;
  border-radius: 999px;
  background: #f8f8f6;
  color: #171717;
  font-size: 0.72rem;
  font-weight: 700;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.document-card-body {
  flex: 1 1 auto;
  min-height: 154px;
  padding: 16px 16px 14px !important;
}

.document-card-heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 11px;
}

.document-card-heading-content {
  min-width: 0;
}

.card-title {
  margin-bottom: 7px;
  overflow: hidden;
  font-size: 0.98rem;
  line-height: 1.25;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.document-title-line {
  display: flex;
  min-width: 0;
  align-items: baseline;
  gap: 7px;
}

.document-title-line .card-title {
  min-width: 0;
  margin-bottom: 7px;
}

.document-version {
  flex: 0 0 auto;
  padding: 2px 6px;
  border: 1px solid #e7e7e2;
  border-radius: 999px;
  background: #f6f6f3;
  color: #a1a1a1;
  font-size: 0.68rem;
  font-weight: 600;
  white-space: nowrap;
}

.document-card-categories,
.document-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.document-card-categories .badge,
.document-tags .badge {
  padding: 0.28em 0.52em;
  font-size: 0.68rem;
  font-weight: 500;
}

.document-card-categories .badge.bg-primary {
  border: 1px solid #dfe5ff;
  background: #f0f3ff !important;
  color: #52618e !important;
}

.document-card-categories .badge.bg-success {
  border: 1px solid #dceee4;
  background: #f0f8f3 !important;
  color: #4d7b60 !important;
}

.document-card-tools {
  display: flex;
  flex: 0 0 auto;
  align-items: center;
  gap: 5px;
}

.favorite-star,
.document-info-button {
  display: inline-grid;
  width: 28px;
  height: 28px;
  flex: 0 0 28px;
  place-items: center;
  border: 0;
  border-radius: 50%;
  background: #f4f4f1;
  color: #858585;
  transition: background-color 0.18s ease, color 0.18s ease, transform 0.18s ease;
}

.favorite-star:hover,
.favorite-star.active {
  background: #fff7d6;
  color: #d99800;
}

.favorite-star:hover,
.document-info-button:hover {
  transform: translateY(-1px);
}

.document-info-button {
  border: 1px solid #e2e2de;
  background: transparent;
  color: #707070;
  font-size: 0.75rem;
}

.document-info-button:hover,
.document-info-button:focus {
  background: #171717;
  color: #fff;
  outline: 0;
}

.document-info-wrap {
  position: relative;
}

.document-info-tooltip {
  position: absolute;
  z-index: 20;
  top: calc(100% + 8px);
  right: 0;
  display: grid;
  width: 220px;
  gap: 7px;
  padding: 11px 12px;
  border: 1px solid #dededb;
  border-radius: 7px;
  background: #fff;
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.14);
  color: #555;
  font-size: 0.76rem;
  opacity: 0;
  pointer-events: none;
  transform: translateY(-4px);
  transition: opacity 0.16s ease, transform 0.16s ease;
}

.document-info-tooltip strong {
  color: #171717;
  font-size: 0.8rem;
}

.document-info-tooltip span {
  display: flex;
  gap: 7px;
  align-items: flex-start;
}

.document-info-tooltip i {
  width: 13px;
  margin-top: 2px;
  color: #707070;
  text-align: center;
}

.document-info-wrap:hover .document-info-tooltip,
.document-info-wrap:focus-within .document-info-tooltip {
  opacity: 1;
  pointer-events: auto;
  transform: translateY(0);
}

.document-description {
  display: -webkit-box;
  overflow: hidden;
  margin-bottom: 12px;
  font-size: 0.82rem;
  line-height: 1.35;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

.document-card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 12px 16px 13px;
  border-top: 1px solid #e3e3de;
  border-radius: 0 0 8px 8px;
  background: #f8f8f6;
}

.document-card-meta {
  display: grid;
  min-width: 0;
  gap: 5px;
  color: #707070;
  font-size: 0.72rem;
  line-height: 1.25;
}

.document-card-meta span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.document-card-meta i {
  width: 14px;
  margin-right: 4px;
  text-align: center;
}

.document-card-actions {
  display: flex;
  flex: 0 0 auto;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 5px;
}

.document-card-actions .btn-sm {
  padding: 4px 8px;
  font-size: 0.75rem;
  line-height: 1.2;
  border-radius: 6px;
  font-weight: 700;
}

@media (max-width: 575px) {
  .document-card-footer {
    align-items: flex-start;
    flex-direction: column;
  }

  .document-card-actions {
    width: 100%;
    justify-content: flex-start;
  }
}
</style>
