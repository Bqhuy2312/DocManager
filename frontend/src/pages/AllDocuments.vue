<template>
  <div class="container-fluid py-4">
    <h1 class="mb-4"><i class="fas fa-book"></i> Tất Cả Tài Liệu</h1>

    <!-- Bộ lọc và tìm kiếm -->
    <div class="row mb-4">
      <div class="col-md-6">
        <input
          type="text"
          class="form-control"
          v-model="searchQuery"
          placeholder="Tìm kiếm theo tên hoặc tag..."
        />
      </div>
      <div class="col-md-3">
        <select class="form-select" v-model="selectedCategory">
          <option value="">Tất cả danh mục</option>
          <option value="process">Quy trình</option>
          <option value="guide">Hướng dẫn</option>
          <option value="policy">Chính sách</option>
          <option value="workflow">Quy trình làm việc</option>
          <option value="handbook">Sổ tay</option>
          <option value="training">Đào tạo</option>
        </select>
      </div>
      <div class="col-md-3">
        <select class="form-select" v-model="sortBy">
          <option value="recent">Gần đây</option>
          <option value="name">Theo tên</option>
        </select>
      </div>
    </div>

    <!-- Danh sách tài liệu -->
    <div class="row g-4">
      <div class="col-md-6 col-lg-4" v-for="doc in filteredDocuments" :key="doc.id">
        <div class="card document-card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div>
                <h5 class="card-title">{{ doc.name }}</h5>

                <span class="badge bg-primary">
                  {{ doc.category }}
                </span>
              </div>

              <button class="btn btn-link text-warning p-0">
                <i class="fas fa-star"></i>
              </button>
            </div>

            <p class="text-muted small">Phòng: {{ doc.department }}</p>

            <div class="mb-3">
              <span
                v-for="tag in doc.tags"
                :key="tag"
                class="badge bg-light text-dark border me-1"
              >
                {{ tag }}
              </span>
            </div>

            <div class="text-muted small">
              {{ formatDate(doc.updatedAt) }}
            </div>
          </div>

          <div class="card-footer bg-white d-flex justify-content-between">
            <button
              class="btn btn-outline-primary btn-sm"
              @click="viewDocument(doc.id)"
            >
              <i class="fas fa-eye"></i>
              Xem
            </button>

            <button
              class="btn btn-outline-success btn-sm"
              @click="downloadDocument(doc.id)"
            >
              <i class="fas fa-download"></i>
              Tải xuống
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "AllDocuments",
  data() {
    return {
      searchQuery: "",
      selectedCategory: "",
      sortBy: "recent",
      documents: [
        {
          id: 1,
          name: "Quy trình tuyển dụng",
          category: "Quy trình",
          department: "Nhân sự",
          tags: ["HR", "Tuyển"],
          updatedAt: new Date("2026-05-30"),
        },
        {
          id: 2,
          name: "Hướng dẫn sử dụng HRM",
          category: "Hướng dẫn",
          department: "Nhân sự",
          tags: ["System", "Training"],
          updatedAt: new Date("2026-05-28"),
        },
        {
          id: 3,
          name: "Chính sách làm việc",
          category: "Chính sách",
          department: "Tài chính",
          tags: ["Policy"],
          updatedAt: new Date("2026-05-25"),
        },
        {
          id: 4,
          name: "Quy trình mua hàng",
          category: "Quy trình làm việc",
          department: "Vận hành",
          tags: ["Procurement"],
          updatedAt: new Date("2026-05-20"),
        },
        {
          id: 5,
          name: "Sổ tay nhân viên mới",
          category: "Sổ tay",
          department: "Nhân sự",
          tags: ["Onboarding"],
          updatedAt: new Date("2026-05-15"),
        },
      ],
    };
  },
  computed: {
    canEdit() {
      const user = JSON.parse(localStorage.getItem("user") || "{}");
      return user.role === "admin" || user.role === "editor";
    },
    canDelete() {
      const user = JSON.parse(localStorage.getItem("user") || "{}");
      return user.role === "admin";
    },
    filteredDocuments() {
      return this.documents
        .filter((doc) => {
          const matchSearch =
            doc.name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
            doc.tags.some((tag) =>
              tag.toLowerCase().includes(this.searchQuery.toLowerCase()),
            );
          const matchCategory =
            !this.selectedCategory || doc.category === this.selectedCategory;
          return matchSearch && matchCategory;
        })
        .sort((a, b) => {
          if (this.sortBy === "recent")
            return new Date(b.updatedAt) - new Date(a.updatedAt);
          if (this.sortBy === "name") return a.name.localeCompare(b.name);
        });
    },
  },
  methods: {
    viewDocument(id) {
      this.$router.push(`/documents/${id}`);
    },
    downloadDocument(id) {
      alert(`Tải xuống tài liệu ${id}`);
    },
    editDocument(id) {
      alert(`Chỉnh sửa tài liệu ${id}`);
    },
    deleteDocument(id) {
      if (confirm("Bạn chắc chắn muốn xóa?")) {
        this.documents = this.documents.filter((doc) => doc.id !== id);
      }
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString("vi-VN");
    },
  },
};
</script>

<style scoped>
.table-hover tbody tr:hover {
  background-color: #f5f5f5;
}

.badge {
  font-size: 0.75rem;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}

.document-card {
  border-radius: 8px;
  transition: all 0.3s ease;
  border: 1px solid #dededb;
}

.document-card:hover {
  transform: translateY(-4px);
  border-color: #171717;
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.08);
}

.card-title {
  font-size: 1.1rem;
  font-weight: 600;
}

.card-footer {
  border-top: 1px solid #dededb;
}
</style>
