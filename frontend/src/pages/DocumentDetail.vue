<template>
  <div class="container-fluid py-4">
    <button class="back-button" type="button" @click="goBack">
      <i class="fas fa-arrow-left me-2"></i>Quay lại
    </button>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <p v-if="loading" class="text-muted">Đang tải tài liệu...</p>

    <div v-else-if="document" class="row">
      <div class="col-lg-8">
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0"><i class="fas fa-file me-2"></i>{{ document.title }}</h3>
            <button v-if="document.status === 'approved'" class="btn btn-sm btn-outline-primary" @click="downloadDocument">
              <i class="fas fa-download me-1"></i>Tải xuống
            </button>
          </div>
          <div class="card-body">
            <section class="document-preview">
              <div class="preview-header">
                <strong><i class="fas fa-eye me-2"></i>Xem trước tài liệu</strong>
                <div class="preview-actions">
                  <span>{{ document.mime_type || fileExtensionLabel }}</span>
                  <button type="button" class="btn btn-sm btn-outline-secondary" @click="previewVisible = !previewVisible">
                    <i class="fas me-1" :class="previewVisible ? 'fa-eye-slash' : 'fa-eye'"></i>
                    {{ previewVisible ? 'Ẩn xem trước' : 'Hiện xem trước' }}
                  </button>
                </div>
              </div>

              <div v-if="previewVisible" class="preview-surface">
                <img v-if="previewType === 'image'" :src="document.file_path" :alt="document.title" class="preview-image">
                <iframe
                  v-else-if="previewType === 'pdf' || previewType === 'office' || previewType === 'text'"
                  :src="previewSource"
                  class="preview-frame"
                  title="Xem trước tài liệu"
                ></iframe>
                <video v-else-if="previewType === 'video'" :src="document.file_path" class="preview-media" controls></video>
                <audio v-else-if="previewType === 'audio'" :src="document.file_path" class="preview-audio" controls></audio>
                <div v-else class="preview-empty">
                  <i class="fas fa-file-circle-question"></i>
                  <strong>Không hỗ trợ xem trước loại tài liệu này</strong>
                  <span>Bạn vẫn có thể xem thông tin tài liệu ở bên dưới.</span>
                </div>
              </div>
              <div v-else class="preview-collapsed">
                <i class="fas fa-eye-slash"></i>
                <span>Đã ẩn xem trước tài liệu.</span>
              </div>
            </section>

            <p class="text-muted mt-4">{{ document.description || "Không có mô tả." }}</p>
            <div class="row mt-4">
              <div class="col-md-6">
                <p><strong>Danh mục:</strong> {{ document.category }}</p>
                <p><strong>Thư mục:</strong> {{ document.folder }}</p>
                <p><strong>Tên file:</strong> {{ document.file_name }}</p>
                <p><strong>Phiên bản:</strong> {{ versionLabel(document.version) }}</p>
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
              <span v-if="!document.tags?.length" class="text-muted ms-2">Chưa có tag</span>
            </div>
          </div>
        </div>

        <div class="card mb-4">
          <div class="card-header"><strong><i class="fas fa-code-branch me-2"></i>Lịch sử phiên bản</strong></div>
          <div class="card-body">
            <div class="version-current">
              <span class="version-badge">{{ versionLabel(document.version) }}</span>
              <div>
                <strong>Phiên bản hiện tại</strong>
                <p class="mb-0 text-muted">{{ document.file_name }} - {{ formatDate(document.updated_at) }}</p>
              </div>
            </div>

            <div v-if="document.versions?.length" class="version-list">
              <div v-for="version in document.versions" :key="version.id" class="version-item">
                <span class="version-badge">{{ versionLabel(version.version) }}</span>
                <div>
                  <strong>{{ version.title }}</strong>
                  <p class="mb-0 text-muted">
                    {{ version.file_name }} - {{ formatFileSize(version.file_size) }} - {{ version.updated_by || "Không rõ" }} - {{ formatDate(version.created_at) }}
                  </p>
                </div>
              </div>
            </div>
            <p v-else class="text-muted mb-0">Chưa có lịch sử cập nhật phiên bản.</p>
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

        <div v-if="canUpdateDocument" class="card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Cập nhật tài liệu</strong>
            <button class="btn btn-sm btn-outline-secondary" type="button" @click="updateFormVisible = !updateFormVisible">
              {{ updateFormVisible ? "Ẩn" : "Mở" }}
            </button>
          </div>
          <div v-if="updateFormVisible" class="card-body">
            <form @submit.prevent="submitUpdate" class="d-grid gap-3">
              <div>
                <label class="form-label">Tên tài liệu</label>
                <input v-model.trim="updateForm.title" class="form-control" type="text" required>
              </div>
              <div>
                <label class="form-label">Mô tả</label>
                <textarea v-model.trim="updateForm.description" class="form-control" rows="3"></textarea>
              </div>
              <div>
                <label class="form-label">Tag, phân cách bằng dấu phẩy</label>
                <input v-model.trim="updateForm.tags" class="form-control" type="text">
              </div>
              <div>
                <label class="form-label">File phiên bản mới</label>
                <input ref="updateFileInput" class="form-control" type="file" required @change="handleUpdateFile">
              </div>
              <small class="text-muted">Sau khi cập nhật, tài liệu sẽ chuyển về trạng thái chờ phê duyệt.</small>
              <button class="btn btn-primary" type="submit" :disabled="updating">
                <i class="fas fa-upload me-1"></i>{{ updating ? "Đang cập nhật..." : "Cập nhật phiên bản" }}
              </button>
            </form>
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
  downloadDocumentFile,
  getDocument,
  updateDocumentFile,
} from "@/services/documentService";
import { confirmDialog, notify } from "@/services/notificationService";

export default {
  name: "DocumentDetail",
  data() {
    return {
      document: null,
      loading: false,
      error: "",
      previewVisible: true,
      updateFormVisible: false,
      updating: false,
      updateForm: {
        title: "",
        description: "",
        tags: "",
        file: null,
      },
    };
  },
  computed: {
    currentUser() {
      try {
        return JSON.parse(localStorage.getItem("user"));
      } catch {
        return null;
      }
    },
    isAdmin() {
      return this.currentUser?.role === "admin";
    },
    canUpdateDocument() {
      return this.isAdmin
        || (this.currentUser?.role === "editor" && this.document?.created_by === this.currentUser?.id);
    },
    fileExtension() {
      const name = this.document?.file_name || "";
      return name.includes(".") ? name.split(".").pop().toLowerCase() : "";
    },
    fileExtensionLabel() {
      return this.fileExtension ? `.${this.fileExtension}` : "Không rõ định dạng";
    },
    previewType() {
      const mimeType = this.document?.mime_type || "";
      const extension = this.fileExtension;

      if (mimeType.startsWith("image/")) return "image";
      if (mimeType === "application/pdf" || extension === "pdf") return "pdf";
      if (mimeType.startsWith("video/")) return "video";
      if (mimeType.startsWith("audio/")) return "audio";
      if (mimeType.startsWith("text/") || ["txt", "csv", "json", "xml"].includes(extension)) return "text";
      if (["doc", "docx", "xls", "xlsx", "ppt", "pptx"].includes(extension)) return "office";

      return "unsupported";
    },
    previewSource() {
      if (!this.document?.file_path) return "";

      if (this.previewType === "office") {
        return `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(this.document.file_path)}`;
      }

      return this.document.file_path;
    },
  },
  async mounted() {
    await this.loadDocument();
  },
  methods: {
    goBack() {
      if (window.history.length > 1) {
        this.$router.back();
      } else {
        this.$router.push("/documents");
      }
    },
    fillUpdateForm() {
      this.updateForm = {
        title: this.document?.title || "",
        description: this.document?.description || "",
        tags: (this.document?.tags || []).join(", "),
        file: null,
      };
      if (this.$refs.updateFileInput) this.$refs.updateFileInput.value = "";
    },
    async loadDocument() {
      this.loading = true;
      this.error = "";
      try {
        this.document = await getDocument(this.$route.params.id);
        this.fillUpdateForm();
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể tải chi tiết tài liệu.";
      } finally {
        this.loading = false;
      }
    },
    handleUpdateFile(event) {
      this.updateForm.file = event.target.files[0] || null;
    },
    async submitUpdate() {
      if (!this.updateForm.file) {
        notify({ title: "Chưa chọn file", message: "Vui lòng chọn file phiên bản mới.", type: "danger" });
        return;
      }

      this.updating = true;
      this.error = "";

      const formData = new FormData();
      formData.append("title", this.updateForm.title);
      formData.append("description", this.updateForm.description);
      formData.append("tags", this.updateForm.tags);
      formData.append("file", this.updateForm.file);

      try {
        this.document = await updateDocumentFile(this.document.id, formData);
        this.fillUpdateForm();
        this.updateFormVisible = false;
        notify({
          title: "Đã cập nhật phiên bản",
          message: "Tài liệu đã chuyển về trạng thái chờ phê duyệt.",
        });
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể cập nhật tài liệu.";
      } finally {
        this.updating = false;
      }
    },
    async downloadDocument() {
      if (this.document.status !== "approved") return;
      try {
        await downloadDocumentFile(this.document);
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể tải xuống tài liệu.";
      }
    },
    async setApproval(status) {
      try {
        this.document = await approveDocument(this.document.id, status);
        this.fillUpdateForm();
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể cập nhật trạng thái.";
      }
    },
    async removeDocument() {
      const confirmed = await confirmDialog({
        title: "Xóa tài liệu",
        message: "Bạn chắc chắn muốn xóa tài liệu này? Thao tác này không thể hoàn tác.",
        confirmText: "Xóa tài liệu",
        tone: "danger",
      });
      if (!confirmed) return;

      try {
        await deleteDocument(this.document.id);
        notify({ title: "Đã xóa tài liệu", message: "Tài liệu đã được xóa khỏi hệ thống." });
        this.$router.push("/documents");
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể xóa tài liệu.";
      }
    },
    formatDate(date) {
      if (!date) return "Chưa có";
      return new Date(date).toLocaleString("vi-VN");
    },
    formatFileSize(bytes) {
      if (!bytes) return "0 Bytes";
      const units = ["Bytes", "KB", "MB", "GB"];
      const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
      return `${Math.round((bytes / Math.pow(1024, index)) * 100) / 100} ${units[index]}`;
    },
    versionLabel(version) {
      const value = String(version || "1.0");
      return value.toLowerCase().startsWith("v") ? value : `v${value}`;
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
.back-button {
  display: inline-flex;
  align-items: center;
  margin-bottom: 20px;
  border: 0;
  background: transparent;
  color: #292929;
  font-weight: 700;
}

.back-button:hover {
  color: #171717;
}

.document-preview {
  overflow: hidden;
  border: 1px solid #dededb;
  border-radius: 8px;
  background: #f8f8f7;
}

.preview-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 12px;
  border-bottom: 1px solid #dededb;
  background: #fff;
}

.preview-header span {
  color: #707070;
  font-size: 0.78rem;
}

.preview-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.preview-surface {
  display: grid;
  min-height: 420px;
  place-items: center;
  background: #f6f6f4;
}

.preview-frame {
  width: 100%;
  min-height: 560px;
  border: 0;
  background: #fff;
}

.preview-image {
  display: block;
  max-width: 100%;
  max-height: 620px;
  object-fit: contain;
}

.preview-media {
  width: 100%;
  max-height: 560px;
  background: #171717;
}

.preview-audio {
  width: min(520px, calc(100% - 32px));
}

.preview-empty {
  display: grid;
  justify-items: center;
  gap: 8px;
  padding: 32px;
  color: #707070;
  text-align: center;
}

.preview-empty i {
  color: #171717;
  font-size: 2.4rem;
}

.preview-collapsed {
  display: flex;
  min-height: 96px;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: #707070;
}

.version-current,
.version-item {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr);
  gap: 12px;
  align-items: start;
}

.version-list {
  display: grid;
  gap: 14px;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #dededb;
}

.version-badge {
  display: inline-grid;
  min-width: 46px;
  min-height: 30px;
  place-items: center;
  border-radius: 999px;
  background: #171717;
  color: #fff;
  font-size: 0.78rem;
  font-weight: 800;
}

@media (max-width: 640px) {
  .preview-header,
  .preview-actions {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
