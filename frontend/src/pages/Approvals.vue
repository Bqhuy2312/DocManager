<template>
  <div class="container-fluid py-4">
    <div class="page-heading">
      <div>
        <h1 class="mb-1"><i class="fas fa-clipboard-check me-2"></i>Phê Duyệt Tài Liệu</h1>
        <p class="text-muted mb-0">Kiểm tra tài liệu đã phê duyệt, đã từ chối và tài liệu đang chờ phê duyệt.</p>
      </div>
      <div class="summary-box">
        <strong>{{ documents.length }}</strong>
        <span>{{ statusLabel(selectedStatus) }}</span>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="approval-toolbar">
      <div class="status-actions">
        <button
          v-for="status in statuses"
          :key="status.value"
          type="button"
          class="btn btn-sm"
          :class="selectedStatus === status.value ? 'btn-dark' : 'btn-outline-dark'"
          :disabled="bulkProcessing"
          @click="changeStatus(status.value)"
        >
          <i :class="status.icon" class="me-1"></i>{{ status.label }}
        </button>
      </div>

      <div v-if="selectedStatus === 'pending' && documents.length" class="bulk-actions">
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary"
          :disabled="bulkProcessing"
          @click="bulkApproval('rejected')"
        >
          <i class="fas fa-xmark me-1"></i>{{ bulkProcessing ? "Đang xử lý..." : "Từ chối tất cả" }}
        </button>
        <button
          type="button"
          class="btn btn-sm btn-success"
          :disabled="bulkProcessing"
          @click="bulkApproval('approved')"
        >
          <i class="fas fa-check me-1"></i>{{ bulkProcessing ? "Đang xử lý..." : "Duyệt tất cả" }}
        </button>
      </div>
    </div>

    <Loading v-if="loading" type="cards" :count="6" />
    <div v-else-if="!documents.length" class="empty-state">
      <i class="far fa-folder-open"></i>
      <strong>Không có tài liệu</strong>
      <span>Danh sách {{ statusLabel(selectedStatus).toLowerCase() }} hiện đang trống.</span>
    </div>

    <div v-else class="row g-4">
      <div v-for="document in paginatedDocuments" :key="document.id" class="col-md-6 col-lg-4">
        <DocumentCard
          :document="document"
          :show-status="true"
          :show-approval-actions="selectedStatus === 'pending'"
          @view="viewDocument"
          @toggle-favorite="toggleFavorite"
          @approve="setApproval"
          @download="download"
        />
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
import DocumentCard from "@/components/common/DocumentCard.vue";
import Loading from "@/components/common/Loading.vue";
import { approveDocument, downloadDocumentFile, getDocuments, toggleFavoriteDocument } from "@/services/documentService";
import { confirmDialog, notify } from "@/services/notificationService";
import realtimeRefresh from "@/mixins/realtimeRefresh";

export default {
  name: "Approvals",
  mixins: [realtimeRefresh],
  realtimeScopes: ["document"],
  components: { PaginationControls, DocumentCard, Loading },
  data() {
    return {
      documents: [],
      selectedStatus: "pending",
      currentPage: 1,
      itemsPerPage: 15,
      loading: false,
      bulkProcessing: false,
      error: "",
      statuses: [
        { value: "pending", label: "Chờ phê duyệt", icon: "fas fa-clock" },
        { value: "approved", label: "Đã phê duyệt", icon: "fas fa-check-circle" },
        { value: "rejected", label: "Đã từ chối", icon: "fas fa-circle-xmark" },
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
    refreshRealtimeData() {
      return this.loadDocuments();
    },
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
    async download(document) {
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
    async setApproval(document, status) {
      try {
        await approveDocument(document.id, status);
        await this.loadDocuments();
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể cập nhật trạng thái.";
      }
    },
    async bulkApproval(status) {
      if (this.selectedStatus !== "pending" || !this.documents.length) return;

      const approved = status === "approved";
      const total = this.documents.length;
      const confirmed = await confirmDialog({
        title: approved ? "Duyệt tất cả tài liệu" : "Từ chối tất cả tài liệu",
        message: `Bạn chắc chắn muốn ${approved ? "duyệt" : "từ chối"} tất cả ${total} tài liệu đang chờ phê duyệt?`,
        confirmText: approved ? "Duyệt tất cả" : "Từ chối tất cả",
        tone: approved ? "primary" : "danger",
      });

      if (!confirmed) return;

      this.bulkProcessing = true;
      this.error = "";

      try {
        await Promise.all(this.documents.map((document) => approveDocument(document.id, status)));
        notify({
          title: approved ? "Đã duyệt tất cả" : "Đã từ chối tất cả",
          message: `${total} tài liệu đã được cập nhật trạng thái.`,
        });
        await this.loadDocuments();
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể cập nhật tất cả tài liệu.";
      } finally {
        this.bulkProcessing = false;
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
        rejected: "Đã từ chối",
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
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 18px;
}

.status-actions,
.bulk-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.bulk-actions {
  margin-left: auto;
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

