<template>
  <div class="container-fluid py-4">
    <h1 class="mb-4"><i class="fas fa-book"></i> Tất Cả Tài Liệu</h1>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="row mb-4">
      <div class="col-md-6">
        <input v-model="searchQuery" type="text" class="form-control" placeholder="Tìm kiếm theo tên hoặc tag...">
      </div>
      <div class="col-md-3">
        <select v-model="selectedCategory" class="form-select">
          <option value="">Tất cả danh mục</option>
          <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
        </select>
      </div>
      <div class="col-md-3">
        <select v-model="sortBy" class="form-select">
          <option value="recent">Gần đây</option>
          <option value="name">Theo tên</option>
        </select>
      </div>
    </div>

    <p v-if="loading" class="text-muted">Đang tải tài liệu...</p>
    <p v-else-if="!filteredDocuments.length" class="text-muted">Chưa có tài liệu phù hợp.</p>

    <div v-else class="row g-4">
      <div v-for="document in filteredDocuments" :key="document.id" class="col-md-6 col-lg-4">
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

          <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <div class="favorite-document-meta">
              <span><i class="fas fa-clock me-1"></i>Thời gian: {{ formatDateTime(document.updated_at) }}</span>
              <span><i class="fas fa-weight-hanging me-1"></i>Kích thước: {{ formatFileSize(document.file_size) }}</span>
              <span><i class="fas fa-upload me-1"></i>Tải lên: {{ formatDate(document.created_at) }}</span>
            </div>

            <div>
              <button class="btn btn-outline-success btn-sm" @click.stop="downloadDocument(document)">
                <i class="fas fa-download"></i>
                Tải xuống
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { getDocuments, toggleFavoriteDocument } from "@/services/documentService";

export default {
  name: "AllDocuments",
  data() {
    return {
      documents: [],
      searchQuery: "",
      selectedCategory: "",
      sortBy: "recent",
      loading: false,
      error: "",
    };
  },
  computed: {
    categories() {
      return [...new Set(this.documents.map((document) => document.category).filter(Boolean))];
    },
    filteredDocuments() {
      const query = this.searchQuery.toLowerCase();
      return [...this.documents]
        .filter((document) => {
          const matchesSearch =
            document.title.toLowerCase().includes(query) ||
            (document.tags || []).some((tag) => tag.toLowerCase().includes(query));
          const matchesCategory = !this.selectedCategory || document.category === this.selectedCategory;
          return matchesSearch && matchesCategory;
        })
        .sort((a, b) => {
          if (this.sortBy === "name") return a.title.localeCompare(b.title);
          return new Date(b.updated_at) - new Date(a.updated_at);
        });
    },
  },
  async mounted() {
    this.searchQuery = this.$route.query.search || "";
    await this.loadDocuments();
  },
  methods: {
    async loadDocuments() {
      this.loading = true;
      this.error = "";
      try {
        this.documents = await getDocuments();
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể tải danh sách tài liệu.";
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
    async toggleFavorite(document) {
      try {
        const result = await toggleFavoriteDocument(document.id);
        document.is_favorite = result.is_favorite;
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
