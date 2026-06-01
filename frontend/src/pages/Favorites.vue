<template>
  <div class="container-fluid py-4">
    <h1 class="mb-4"><i class="fas fa-star"></i> Tài Liệu Yêu Thích</h1>

    <div v-if="favorites.length === 0" class="alert alert-info">
      Chưa có tài liệu yêu thích nào. Hãy bổ sung thêm!
    </div>

    <div class="row g-4">
      <div class="col-md-6 col-lg-4" v-for="doc in favorites" :key="doc.id">
        <div class="card favorite-document-card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div>
                <h5 class="card-title fw-bold">
                  {{ doc.name }}
                </h5>

                <span class="badge bg-primary me-2">
                  {{ doc.category }}
                </span>

                <span class="badge bg-success">
                  {{ doc.department }}
                </span>
              </div>

              <button
                class="btn btn-link text-warning p-0"
                @click="removeFavorite(doc.id)"
              >
                <i class="fas fa-star fs-5"></i>
              </button>
            </div>

            <p class="text-muted">
              {{ doc.description }}
            </p>

            <div class="mb-3">
              <span
                v-for="tag in doc.tags"
                :key="tag"
                class="badge rounded-pill bg-light text-dark border me-1"
              >
                {{ tag }}
              </span>
            </div>
          </div>

          <div
            class="card-footer bg-white d-flex justify-content-between align-items-center"
          >
            <div class="small text-muted">
              {{ formatDate(doc.updatedAt) }}
            </div>

            <div>
              <button
                class="btn btn-outline-primary btn-sm me-2"
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
  </div>
</template>

<script>
export default {
  name: "Favorites",
  data() {
    return {
      favorites: [
        {
          id: 1,
          name: "Quy trình tuyển dụng",
          description: "Quy trình chi tiết cho việc tuyển dụng nhân sự",
          category: "Quy trình",
          department: "Nhân sự",
          updatedAt: new Date("2026-05-30"),
        },
        {
          id: 3,
          name: "Chính sách làm việc",
          description: "Các chính sách làm việc của công ty",
          category: "Chính sách",
          department: "Tài chính",
          updatedAt: new Date("2026-05-25"),
        },
      ],
    };
  },
  methods: {
    viewDocument(id) {
      this.$router.push(`/documents/${id}`);
    },
    downloadDocument(id) {
      alert(`Tải xuống tài liệu ${id}`);
    },
    removeFavorite(id) {
      this.favorites = this.favorites.filter((doc) => doc.id !== id);
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString("vi-VN");
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
}

.favorite-document-card {
  border: 1px solid #dededb;
  border-radius: 8px;
  transition: all .25s ease;
}

.favorite-document-card:hover {
  transform: translateY(-4px);
  border-color: #171717;
  box-shadow: 0 8px 18px rgba(0,0,0,.08);
}

.card-title {
  font-size: 1.1rem;
}

.card-footer {
  border-top: 1px solid #dededb;
}

.badge {
  font-weight: 500;
}
</style>
