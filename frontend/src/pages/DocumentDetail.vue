<template>
  <div class="container-fluid py-4">
    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <p v-if="loading" class="text-muted">Đang tải tài liệu...</p>

    <div v-else-if="document" class="row">
      <div class="col-lg-8">
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0"><i class="fas fa-file me-2"></i>{{ document.title }}</h3>
            <button class="btn btn-sm btn-outline-primary" @click="downloadDocument">
              <i class="fas fa-download me-1"></i>Tải xuống
            </button>
          </div>
          <div class="card-body">
            <p class="text-muted">{{ document.description || "Không có mô tả." }}</p>
            <div class="row mt-4">
              <div class="col-md-6">
                <p><strong>Danh mục:</strong> {{ document.category }}</p>
                <p><strong>Thư mục:</strong> {{ document.folder }}</p>
                <p><strong>Tên file:</strong> {{ document.file_name }}</p>
              </div>
              <div class="col-md-6">
                <p><strong>Người tải lên:</strong> {{ document.author }}</p>
                <p><strong>Kích thước:</strong> {{ formatFileSize(document.file_size) }}</p>
                <p><strong>Cập nhật:</strong> {{ formatDate(document.updated_at) }}</p>
              </div>
            </div>
            <div class="mt-3">
              <strong>Tag:</strong>
              <span v-for="tag in document.tags" :key="tag" class="badge bg-secondary ms-2">{{ tag }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card mb-3">
          <div class="card-header"><strong>Trạng thái phê duyệt</strong></div>
          <div class="card-body">
            <span class="badge bg-secondary p-2">{{ statusLabel(document.status) }}</span>
            <p class="small text-muted mt-3 mb-0">
              Phê duyệt bởi: {{ document.approved_by || "Chưa có" }}
            </p>
          </div>
        </div>

        <div v-if="isAdmin" class="card">
          <div class="card-header"><strong>Quản trị tài liệu</strong></div>
          <div class="card-body d-grid gap-2">
            <template v-if="document.status === 'pending'">
              <button class="btn btn-primary" @click="setApproval('approved')">Phê duyệt</button>
              <button class="btn btn-outline-secondary" @click="setApproval('rejected')">Từ chối</button>
            </template>
            <button class="btn btn-outline-danger" @click="removeDocument">Xóa tài liệu</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {
  approveDocument,
  deleteDocument,
  getDocument,
} from "@/services/documentService";

export default {
  name: "DocumentDetail",
  data() {
    return {
      document: null,
      loading: false,
      error: "",
    };
  },
  computed: {
    isAdmin() {
      try {
        return JSON.parse(localStorage.getItem("user"))?.role === "admin";
      } catch {
        return false;
      }
    },
  },
  async mounted() {
    await this.loadDocument();
  },
  methods: {
    async loadDocument() {
      this.loading = true;
      this.error = "";
      try {
        this.document = await getDocument(this.$route.params.id);
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể tải chi tiết tài liệu.";
      } finally {
        this.loading = false;
      }
    },
    downloadDocument() {
      window.open(this.document.file_path, "_blank");
    },
    async setApproval(status) {
      try {
        this.document = await approveDocument(this.document.id, status);
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể cập nhật trạng thái.";
      }
    },
    async removeDocument() {
      if (!confirm("Bạn chắc chắn muốn xóa tài liệu này?")) return;
      try {
        await deleteDocument(this.document.id);
        this.$router.push("/documents");
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể xóa tài liệu.";
      }
    },
    formatDate(date) {
      return new Date(date).toLocaleString("vi-VN");
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
        rejected: "Từ chối",
        draft: "Bản nháp",
      }[status] || status;
    },
  },
};
</script>
