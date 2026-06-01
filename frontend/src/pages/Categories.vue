<template>
  <div class="container-fluid py-4">
    <h1 class="mb-4"><i class="fas fa-tag"></i> Danh Mục Tài Liệu</h1>

    <div class="row">
      <div class="col-md-4" v-for="category in categories" :key="category.id">
        <div
          class="card text-center category-card"
          @click="selectCategory(category)"
        >
          <div class="card-body">
            <h2 class="category-icon">
              <i class="fas" :class="category.icon"></i>
            </h2>
            <h5 class="card-title">{{ category.name }}</h5>
            <p class="card-text">
              <strong>{{ category.count }}</strong> tài liệu
            </p>
            <p class="text-muted small">{{ category.description }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Danh sách tài liệu của category đã chọn -->
    <div v-if="selectedCategoryData" class="mt-5">
      <h3 class="mb-4"><i class="fas" :class="selectedCategoryData.icon"></i> {{ selectedCategoryData.name }}</h3>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th><i class="fas fa-file"></i> Tên tài liệu</th>
              <th><i class="fas fa-building"></i> Phòng ban</th>
              <th><i class="fas fa-calendar"></i> Ngày cập nhật</th>
              <th><i class="fas fa-cog"></i> Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="doc in selectedCategoryData.documents" :key="doc.id">
              <td>{{ doc.name }}</td>
              <td>{{ doc.department }}</td>
              <td>{{ formatDate(doc.updatedAt) }}</td>
              <td>
                <button
                  class="btn btn-sm btn-primary"
                  @click="viewDocument(doc.id)"
                >
                  <i class="fas fa-eye"></i>
                </button>
                <button
                  class="btn btn-sm btn-success"
                  @click="downloadDocument(doc.id)"
                >
                  <i class="fas fa-download"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "Categories",
  data() {
    return {
      selectedCategoryData: null,
      categories: [
        {
          id: 1,
          name: "Quy trình",
          icon: "fa-list",
          description: "Các quy trình hoạt động chính",
          count: 15,
          documents: [
            {
              id: 101,
              name: "Quy trình tuyển dụng",
              department: "Nhân sự",
              updatedAt: new Date("2026-05-30"),
            },
            {
              id: 102,
              name: "Quy trình thanh toán",
              department: "Tài chính",
              updatedAt: new Date("2026-05-28"),
            },
          ],
        },
        {
          id: 2,
          name: "Hướng dẫn",
          icon: "fa-book",
          description: "Các hướng dẫn sử dụng",
          count: 12,
          documents: [
            {
              id: 201,
              name: "Hướng dẫn sử dụng HRM",
              department: "Nhân sự",
              updatedAt: new Date("2026-05-25"),
            },
            {
              id: 202,
              name: "Hướng dẫn thiết bị công ty",
              department: "CNTT",
              updatedAt: new Date("2026-05-20"),
            },
          ],
        },
        {
          id: 3,
          name: "Chính sách",
          icon: "fa-scroll",
          description: "Các chính sách công ty",
          count: 8,
          documents: [
            {
              id: 301,
              name: "Chính sách làm việc",
              department: "Nhân sự",
              updatedAt: new Date("2026-05-30"),
            },
            {
              id: 302,
              name: "Chính sách bảo mật",
              department: "CNTT",
              updatedAt: new Date("2026-05-28"),
            },
          ],
        },
        {
          id: 4,
          name: "Quy trình làm việc",
          icon: "fa-cog",
          description: "Các quy trình công việc hàng ngày",
          count: 20,
          documents: [
            {
              id: 401,
              name: "Quy trình mua hàng",
              department: "Vận hành",
              updatedAt: new Date("2026-05-15"),
            },
          ],
        },
        {
          id: 5,
          name: "Sổ tay",
          icon: "fa-book",
          description: "Các sổ tay tham khảo",
          count: 5,
          documents: [
            {
              id: 501,
              name: "Sổ tay nhân viên mới",
              department: "Nhân sự",
              updatedAt: new Date("2026-05-10"),
            },
          ],
        },
        {
          id: 6,
          name: "Đào tạo",
          icon: "fa-graduation-cap",
          description: "Các tài liệu đào tạo",
          count: 18,
          documents: [
            {
              id: 601,
              name: "Khóa đào tạo năng lực",
              department: "Nhân sự",
              updatedAt: new Date("2026-05-05"),
            },
          ],
        },
      ],
    };
  },
  methods: {
    selectCategory(category) {
      this.selectedCategoryData = category;
    },
    viewDocument(id) {
      this.$router.push(`/documents/${id}`);
    },
    downloadDocument(id) {
      alert(`Tải xuống tài liệu ${id}`);
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString("vi-VN");
    },
  },
};
</script>

<style scoped>
.category-card {
  cursor: pointer;
  border-radius: 10px;
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.category-card:hover {
  transform: translateY(-10px);
  border-color: #0d6efd;
  box-shadow: 0 5px 20px rgba(13, 110, 253, 0.2);
}

.category-icon {
  font-size: 3rem;
  margin: 10px 0;
}
</style>
