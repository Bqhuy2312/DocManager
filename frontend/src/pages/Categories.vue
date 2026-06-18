<template>
  <div class="container-fluid py-4">
    <div class="page-heading">
      <div>
        <span class="eyebrow">Thư viện nội bộ</span>
        <h1 class="mb-1">Danh Mục Tài Liệu</h1>
        <p class="text-muted mb-0">Chọn danh mục cha, sau đó lọc tiếp theo danh mục con nếu cần.</p>
      </div>
      <div class="summary-box">
        <strong>{{ parentCategories.length }}</strong>
        <span>Danh mục cha</span>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <p v-if="loading" class="text-muted">Đang tải danh mục...</p>

    <template v-else>
      <section class="category-nav">
        <button
          v-for="category in parentCategories"
          :key="category.id"
          class="category-tab"
          :class="{ active: selectedParent?.id === category.id }"
          type="button"
          @click="selectParent(category)"
        >
          <span class="category-tab-icon"><i class="fas fa-folder"></i></span>
          <span>
            <strong>{{ category.name }}</strong>
            <small>{{ childCategoriesFor(category).length }} danh mục con</small>
          </span>
          <em>{{ documentsForTree(category).length }}</em>
        </button>
      </section>

      <section v-if="selectedParent" class="category-content">
        <header class="content-heading">
          <div>
            <div class="breadcrumb-line">
              <i class="fas fa-home me-1"></i>Danh mục
              <i class="fas fa-chevron-right mx-2"></i>{{ selectedParent.name }}
              <template v-if="selectedChild">
                <i class="fas fa-chevron-right mx-2"></i>{{ selectedChild.name }}
              </template>
            </div>
            <h2 class="mb-1">{{ selectedChild?.name || selectedParent.name }}</h2>
            <p class="text-muted mb-0">
              {{ filteredDocuments.length }} tài liệu
            </p>
          </div>
          <label class="category-search">
            <i class="fas fa-search"></i>
            <input v-model="searchQuery" type="search" placeholder="Tìm trong danh mục...">
          </label>
        </header>

        <div v-if="childCategories.length" class="subcategory-nav">
          <button
            class="subcategory-tab"
            :class="{ active: !selectedChild }"
            type="button"
            @click="selectChild(null)"
          >
            <i class="fas fa-layer-group me-1"></i>Tất cả
            <span>{{ documentsForTree(selectedParent).length }}</span>
          </button>
          <button
            v-for="category in childCategories"
            :key="category.id"
            class="subcategory-tab"
            :class="{ active: selectedChild?.id === category.id }"
            type="button"
            @click="selectChild(category)"
          >
            <i class="fas fa-folder-open me-1"></i>{{ category.label }}
            <span>{{ documentsForTree(category).length }}</span>
          </button>
        </div>

        <div v-if="!filteredDocuments.length" class="empty-state">
          <i class="far fa-folder-open"></i>
          <strong>Chưa có tài liệu phù hợp</strong>
          <span>Tài liệu thuộc danh mục này sẽ hiển thị tại đây.</span>
        </div>

        <div v-else class="row g-4 document-list">
          <div v-for="document in paginatedDocuments" :key="document.id" class="col-md-6 col-lg-4">
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
                  <span><i class="fas fa-user me-1"></i>Người đăng: {{ document.author || "Không rõ" }}</span>
                  <span><i class="fas fa-building me-1"></i>Phòng ban: {{ document.department || "Chưa có phòng ban" }}</span>
                  <span><i class="fas fa-clock me-1"></i>Cập nhật: {{ formatDateTime(document.updated_at) }}</span>
                  <span><i class="fas fa-weight-hanging me-1"></i>Kích thước: {{ formatFileSize(document.file_size) }}</span>
                  <span><i class="fas fa-upload me-1"></i>Tải lên: {{ formatDate(document.created_at) }}</span>
                </div>

                <div>
                  <button v-if="document.status === 'approved'" class="btn btn-outline-success btn-sm" @click.stop="download(document)">
                    <i class="fas fa-download"></i>
                    Tải xuống
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <PaginationControls
          v-if="filteredDocuments.length"
          :page="currentPage"
          :per-page="itemsPerPage"
          :total="filteredDocuments.length"
          @update:page="currentPage = $event"
        />
      </section>

      <div v-else class="empty-state standalone">
        <i class="far fa-folder-open"></i>
        <strong>Chưa có danh mục</strong>
        <span>Tạo thư mục cha trong trang quản lý thư mục để bắt đầu.</span>
      </div>
    </template>
  </div>
</template>

<script>
import PaginationControls from "@/components/common/PaginationControls.vue";
import { getDocuments, getFolders, toggleFavoriteDocument } from "@/services/documentService";

export default {
  name: "Categories",
  components: { PaginationControls },
  data() {
    return {
      parentCategories: [],
      documents: [],
      selectedParent: null,
      selectedChild: null,
      searchQuery: "",
      currentPage: 1,
      itemsPerPage: 15,
      loading: false,
      error: "",
    };
  },
  computed: {
    childCategories() {
      return this.selectedParent ? this.childCategoriesFor(this.selectedParent) : [];
    },
    selectedScope() {
      return this.selectedChild || this.selectedParent;
    },
    filteredDocuments() {
      if (!this.selectedScope) return [];
      const query = this.searchQuery.trim().toLowerCase();
      return this.documentsForTree(this.selectedScope).filter((document) => {
        return !query ||
          document.title.toLowerCase().includes(query) ||
          (document.tags || []).some((tag) => tag.toLowerCase().includes(query));
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
  },
  async mounted() {
    this.loading = true;
    try {
      [this.parentCategories, this.documents] = await Promise.all([getFolders(), getDocuments()]);
      this.selectedParent = this.parentCategories[0] || null;
    } catch (error) {
      this.error = error.response?.data?.message || "Không thể tải danh mục.";
    } finally {
      this.loading = false;
    }
  },
  methods: {
    selectParent(category) {
      this.selectedParent = category;
      this.selectedChild = null;
      this.searchQuery = "";
      this.currentPage = 1;
    },
    selectChild(category) {
      this.selectedChild = category;
      this.searchQuery = "";
      this.currentPage = 1;
    },
    childCategoriesFor(category) {
      return this.flattenFolders(category.descendants || []);
    },
    flattenFolders(folders, depth = 0) {
      return folders.flatMap((folder) => [
        { ...folder, label: `${"— ".repeat(depth)}${folder.name}` },
        ...this.flattenFolders(folder.descendants || [], depth + 1),
      ]);
    },
    folderIdsForTree(folder) {
      return [
        folder.id,
        ...this.flattenFolders(folder.descendants || []).map((child) => child.id),
      ];
    },
    documentsForTree(folder) {
      if (!folder) return [];
      const folderIds = this.folderIdsForTree(folder);
      return this.documents.filter((document) => folderIds.includes(document.folder_id));
    },
    viewDocument(id) {
      this.$router.push(`/documents/${id}`);
    },
    download(document) {
      if (document.status !== "approved") return;
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
.page-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 18px;
}

.eyebrow {
  color: #707070;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.summary-box {
  display: grid;
  min-width: 90px;
  padding: 10px 14px;
  border: 1px solid #dededb;
  border-radius: 7px;
  background: #fff;
  text-align: right;
}

.summary-box strong {
  font-size: 1.25rem;
}

.summary-box span {
  color: #707070;
  font-size: 0.72rem;
}

.category-nav,
.subcategory-nav {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  padding: 4px 0 12px;
}

.subcategory-nav {
  padding: 14px 16px 8px;
  border-bottom: 1px solid #dededb;
}

.category-tab,
.subcategory-tab {
  display: grid;
  flex: 0 0 auto;
  align-items: center;
  gap: 8px;
  border: 1px solid #dededb;
  border-radius: 7px;
  background: #fff;
  color: #292929;
  text-align: left;
}

.category-tab {
  grid-template-columns: auto minmax(105px, auto) auto;
  padding: 8px 10px;
}

.subcategory-tab {
  grid-template-columns: auto auto;
  padding: 7px 10px;
  font-size: 0.86rem;
}

.subcategory-tab span {
  color: #707070;
  font-size: 0.72rem;
}

.category-tab:hover,
.category-tab.active,
.subcategory-tab:hover,
.subcategory-tab.active {
  border-color: #171717;
}

.category-tab.active,
.subcategory-tab.active {
  background: #171717;
  color: #fff;
}

.category-tab span:not(.category-tab-icon) {
  display: grid;
}

.category-tab small,
.category-tab em {
  color: #707070;
  font-size: 0.68rem;
  font-style: normal;
}

.category-tab.active small,
.category-tab.active em,
.subcategory-tab.active span {
  color: #cfcfcb;
}

.category-content {
  overflow: hidden;
  border: 1px solid #dededb;
  border-radius: 8px;
  background: #fff;
}

.content-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 16px;
  border-bottom: 1px solid #dededb;
}

.breadcrumb-line {
  margin-bottom: 8px;
  color: #707070;
  font-size: 0.72rem;
}

.category-search {
  display: flex;
  width: min(300px, 100%);
  height: 34px;
  align-items: center;
  gap: 8px;
  padding: 0 10px;
  border: 1px solid #dededb;
  border-radius: 6px;
  color: #707070;
}

.category-search input {
  width: 100%;
  border: 0;
  outline: 0;
  background: transparent;
  font-size: 0.82rem;
}

.document-list {
  padding: 16px;
}

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

.empty-state {
  display: grid;
  min-height: 240px;
  place-content: center;
  justify-items: center;
  gap: 6px;
  color: #707070;
}

.empty-state i {
  margin-bottom: 4px;
  font-size: 2rem;
}

.standalone {
  border: 1px solid #dededb;
  border-radius: 8px;
  background: #fff;
}

@media (max-width: 700px) {
  .content-heading {
    align-items: stretch;
    flex-direction: column;
  }
}
</style>
