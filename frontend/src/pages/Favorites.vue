<template>
  <div class="container-fluid py-4">
    <h1 class="mb-4"><i class="fas fa-star"></i> Tài Liệu Đánh Dấu</h1>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <p v-if="loading" class="text-muted">Đang tải tài liệu đánh dấu...</p>

    <div v-else-if="favorites.length === 0" class="alert alert-info">
      Chưa có tài liệu đánh dấu nào.
    </div>

    <div v-else class="row g-4">
      <div class="col-md-6 col-lg-4" v-for="document in paginatedFavorites" :key="document.id">
        <div
          class="card favorite-document-card h-100"
          role="button"
          tabindex="0"
          @click="viewDocument(document.id)"
          @keydown.enter="viewDocument(document.id)"
          @keydown.space.prevent="viewDocument(document.id)"
        >
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div>
                <h5 class="card-title fw-bold">
                  {{ document.title }}
                </h5>

                <span class="badge bg-primary me-2">
                  {{ document.category }}
                </span>

                <span class="badge bg-success">
                  {{ document.folder }}
                </span>
              </div>

              <button
                class="favorite-star active"
                type="button"
                aria-label="Bỏ đánh dấu"
                @click.stop="removeFavorite(document)"
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

          <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <div class="favorite-document-meta">
              <span><i class="fas fa-clock me-1"></i>Thời gian: {{ formatDateTime(document.updated_at) }}</span>
              <span><i class="fas fa-weight-hanging me-1"></i>Kích thước: {{ formatFileSize(document.file_size) }}</span>
              <span><i class="fas fa-upload me-1"></i>Tải lên: {{ formatDate(document.created_at) }}</span>
            </div>

            <div>
              <button
                class="btn btn-outline-success btn-sm"
                @click.stop="downloadDocument(document)"
              >
                <i class="fas fa-download"></i>
                Tải xuống
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <PaginationControls
      v-if="!loading && favorites.length"
      :page="currentPage"
      :per-page="itemsPerPage"
      :total="favorites.length"
      @update:page="currentPage = $event"
    />
  </div>
</template>

<script>
import PaginationControls from "@/components/common/PaginationControls.vue";
import { getFavoriteDocuments, toggleFavoriteDocument } from "@/services/documentService";

export default {
  name: "Favorites",
  components: { PaginationControls },
  data() {
    return {
      favorites: [],
      currentPage: 1,
      itemsPerPage: 15,
      loading: false,
      error: "",
    };
  },
  computed: {
    paginatedFavorites() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      return this.favorites.slice(start, start + this.itemsPerPage);
    },
  },
  async mounted() {
    await this.loadFavorites();
  },
  methods: {
    async loadFavorites() {
      this.loading = true;
      this.error = "";
      try {
        this.favorites = await getFavoriteDocuments();
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể tải tài liệu đánh dấu.";
      } finally {
        this.loading = false;
      }
    },
    viewDocument(id) {
      this.$router.push(`/documents/${id}`);
    },
    downloadDocument(document) {
      window.open(document.file_path, "_blank");
    },
    async removeFavorite(document) {
      try {
        const result = await toggleFavoriteDocument(document.id);
        if (!result.is_favorite) {
          this.favorites = this.favorites.filter((item) => item.id !== document.id);
          this.currentPage = Math.min(this.currentPage, Math.max(1, Math.ceil(this.favorites.length / this.itemsPerPage)));
        }
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể cập nhật đánh dấu.";
      }
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString("vi-VN");
    },
    formatDateTime(date) {
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
.card {
  border-radius: 8px;
  box-shadow: none;
  transition: transform 0.2s;
}

.card:hover {
  transform: translateY(-5px);
}

.card-footer {
  background-color: #f8f9fa;
  border-top: 1px solid #dededb;
}

.favorite-document-card {
  border: 1px solid #dededb;
  border-radius: 8px;
  cursor: pointer;
  transition: all .25s ease;
}

.favorite-document-card:hover,
.favorite-document-card:focus {
  transform: translateY(-4px);
  border-color: #171717;
  box-shadow: 0 8px 18px rgba(0,0,0,.08);
  outline: 0;
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

.favorite-document-meta {
  display: grid;
  gap: 6px;
  color: #707070;
  font-size: 0.78rem;
}

.card-title {
  font-size: 1.1rem;
}

.badge {
  font-weight: 500;
}
</style>
