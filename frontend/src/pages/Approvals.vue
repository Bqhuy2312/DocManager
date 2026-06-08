<template>
  <div class="container-fluid py-4">
    <div class="page-heading">
      <div>
        <h1 class="mb-1"><i class="fas fa-clipboard-check me-2"></i>Phê Duyệt Tài Liệu</h1>
        <p class="text-muted mb-0">Kiểm tra tài liệu đã phê duyệt và tài liệu đang chờ phê duyệt.</p>
      </div>
      <div class="summary-box">
        <strong>{{ documents.length }}</strong>
        <span>{{ statusLabel(selectedStatus) }}</span>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="approval-toolbar">
      <button
        v-for="status in statuses"
        :key="status.value"
        type="button"
        class="btn btn-sm"
        :class="selectedStatus === status.value ? 'btn-dark' : 'btn-outline-dark'"
        @click="changeStatus(status.value)"
      >
        <i :class="status.icon" class="me-1"></i>{{ status.label }}
      </button>
    </div>

    <p v-if="loading" class="text-muted">Đang tải tài liệu...</p>
    <div v-else-if="!documents.length" class="empty-state">
      <i class="far fa-folder-open"></i>
      <strong>Không có tài liệu</strong>
      <span>Danh sách {{ statusLabel(selectedStatus).toLowerCase() }} hiện đang trống.</span>
    </div>

    <div v-else class="row g-4">
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

              <span class="badge bg-light text-dark border">
                {{ statusLabel(document.status) }}
              </span>
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
              <div v-if="document.status === 'pending'" class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm" @click.stop="setApproval(document, 'rejected')">
                  <i class="fas fa-xmark me-1"></i>Từ chối
                </button>
                <button class="btn btn-success btn-sm" @click.stop="setApproval(document, 'approved')">
                  <i class="fas fa-check me-1"></i>Phê duyệt
                </button>
              </div>

              <button v-else class="btn btn-outline-success btn-sm" @click.stop="download(document)">
                <i class="fas fa-download"></i>
                Tải xuống
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <PaginationControls
      v-if="!loading && documents.length"
      :page="currentPage"
      :per-page="itemsPerPage"
      :total="documents.length"
      @update:page="currentPage = $event"
    />
  </div>
</template>

<script>
import PaginationControls from "@/components/common/PaginationControls.vue";
import { approveDocument, getDocuments } from "@/services/documentService";

export default {
  name: "Approvals",
  components: { PaginationControls },
  data() {
    return {
      documents: [],
      selectedStatus: "pending",
      currentPage: 1,
      itemsPerPage: 15,
      loading: false,
      error: "",
      statuses: [
        { value: "pending", label: "Chờ phê duyệt", icon: "fas fa-clock" },
        { value: "approved", label: "Đã phê duyệt", icon: "fas fa-check-circle" },
      ],
    };
  },
  computed: {
    paginatedDocuments() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      return this.documents.slice(start, start + this.itemsPerPage);
    },
  },
  async mounted() {
    await this.loadDocuments();
  },
  methods: {
    async loadDocuments() {
      this.loading = true;
      this.error = "";
      try {
        this.documents = await getDocuments({ status: this.selectedStatus });
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể tải danh sách phê duyệt.";
      } finally {
        this.loading = false;
      }
    },
    async changeStatus(status) {
      this.selectedStatus = status;
      this.currentPage = 1;
      await this.loadDocuments();
    },
    viewDocument(id) {
      this.$router.push(`/documents/${id}`);
    },
    download(document) {
      window.open(document.file_path, "_blank");
    },
    async setApproval(document, status) {
      try {
        await approveDocument(document.id, status);
        await this.loadDocuments();
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể cập nhật trạng thái.";
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

<style scoped>
.page-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 18px;
}

.summary-box {
  display: grid;
  min-width: 120px;
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

.approval-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 18px;
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
  min-height: 300px;
  place-content: center;
  justify-items: center;
  gap: 6px;
  border: 1px solid #dededb;
  border-radius: 8px;
  background: #fff;
  color: #707070;
}

.empty-state i {
  margin-bottom: 4px;
  font-size: 2rem;
}
</style>
