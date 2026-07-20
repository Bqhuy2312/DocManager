<template>
  <div class="container-fluid py-4">
    <h1 class="mb-4"><i class="fas fa-book"></i> Tất Cả Tài Liệu</h1>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="row mb-4 g-3 align-items-center">
      <div :class="canFilterMine ? 'col-md-5' : 'col-md-6'">
        <input v-model="searchQuery" type="text" class="form-control" placeholder="Tìm kiếm theo tên hoặc tag...">
      </div>
      <div class="col-md-3">
        <select v-model="selectedCategory" class="form-select">
          <option value="">Tất cả danh mục</option>
          <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
        </select>
      </div>
      <div :class="canFilterMine ? 'col-md-2' : 'col-md-3'">
        <select v-model="sortBy" class="form-select">
          <option value="recent">Gần đây</option>
          <option value="name">A - Z</option>
        </select>
      </div>
      <div v-if="canFilterMine" class="col-md-2">
        <button
          type="button"
          class="btn mine-filter-btn w-100"
          :class="{ active: mineOnly }"
          @click="toggleMineOnly"
        >
          <i class="fas fa-user-edit me-1"></i>
          {{ mineOnly ? "Tất cả" : "Của tôi" }}
        </button>
      </div>
    </div>

    <Loading v-if="loading" type="cards" :count="6" />
    <p v-else-if="!filteredDocuments.length" class="text-muted">Chưa có tài liệu phù hợp.</p>

    <div v-else class="row g-4">
      <div v-for="document in paginatedDocuments" :key="document.id" class="col-md-6 col-lg-4">
        <DocumentCard
          :document="document"
          @view="viewDocument"
          @toggle-favorite="toggleFavorite"
          @download="downloadDocument"
        />
      </div>
    </div>

    <PaginationControls
      v-if="!loading && filteredDocuments.length"
      :page="currentPage"
      :per-page="itemsPerPage"
      :total="filteredDocuments.length"
      @update:page="currentPage = $event"
    />
  </div>
</template>

<script>
import PaginationControls from "@/components/common/PaginationControls.vue";
import DocumentCard from "@/components/common/DocumentCard.vue";
import Loading from "@/components/common/Loading.vue";
import { downloadDocumentFile, getDocuments, toggleFavoriteDocument } from "@/services/documentService";
import realtimeRefresh from "@/mixins/realtimeRefresh";

export default {
  name: "AllDocuments",
  mixins: [realtimeRefresh],
  realtimeScopes: ["document"],
  components: { PaginationControls, DocumentCard, Loading },
  data() {
    return {
      documents: [],
      searchQuery: "",
      selectedCategory: "",
      sortBy: "recent",
      mineOnly: false,
      currentPage: 1,
      itemsPerPage: 15,
      loading: false,
      error: "",
    };
  },
  computed: {
    currentUser() {
      try {
        return JSON.parse(localStorage.getItem("user")) || null;
      } catch {
        return null;
      }
    },
    canFilterMine() {
      return ["admin", "editor"].includes(this.currentUser?.role);
    },
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
    paginatedDocuments() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      return this.filteredDocuments.slice(start, start + this.itemsPerPage);
    },
  },
  watch: {
    searchQuery() {
      this.currentPage = 1;
    },
    selectedCategory() {
      this.currentPage = 1;
    },
    sortBy() {
      this.currentPage = 1;
    },
  },
  async mounted() {
    this.searchQuery = this.$route.query.search || "";
    await this.loadDocuments();
  },
  methods: {
    refreshRealtimeData() {
      return this.loadDocuments();
    },
    async loadDocuments() {
      this.loading = true;
      this.error = "";
      try {
        this.documents = await getDocuments(this.mineOnly ? { mine: 1 } : {});
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể tải danh sách tài liệu.";
      } finally {
        this.loading = false;
      }
    },
    async toggleMineOnly() {
      this.mineOnly = !this.mineOnly;
      this.currentPage = 1;
      await this.loadDocuments();
    },
    viewDocument(id) {
      this.$router.push(`/documents/${id}`);
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

.mine-filter-btn {
  border: 1px solid #171717;
  background: #fff;
  color: #171717;
  font-weight: 700;
}

.mine-filter-btn:hover,
.mine-filter-btn.active {
  background: #171717;
  color: #fff;
}

.card-title {
  font-size: 1.1rem;
}

.badge {
  font-weight: 500;
}
</style>

