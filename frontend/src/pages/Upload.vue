<template>
  <div class="container-fluid py-4">
    <h1 class="mb-4"><i class="fas fa-arrow-up"></i> Tải Lên Tài Liệu</h1>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="success" class="alert alert-success">{{ success }}</div>

    <div class="row">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-body">
            <form @submit.prevent="submitForm">
              <div class="mb-3">
                <label class="form-label"><strong>Tiêu đề tài liệu</strong></label>
                <input v-model="form.title" type="text" class="form-control" required>
              </div>

              <div class="mb-3">
                <label class="form-label"><strong>Mô tả</strong></label>
                <textarea v-model="form.description" class="form-control" rows="4"></textarea>
              </div>

              <div class="mb-3">
                <label class="form-label"><strong>Danh mục cha</strong></label>
                <div class="parent-category-grid">
                  <button
                    v-for="parent in parentFolders"
                    :key="parent.id"
                    type="button"
                    class="parent-category-card"
                    :class="{ active: selectedParentId === parent.id }"
                    @click="selectParent(parent.id)"
                  >
                    <span class="folder-icon"><i class="fas fa-folder"></i></span>
                    <span>
                      <strong>{{ parent.name }}</strong>
                      <small>{{ childrenFor(parent.id).length }} Danh mục con</small>
                    </span>
                  </button>
                </div>
              </div>

              <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <label class="form-label mb-0"><strong>Danh mục con</strong></label>
                  <label v-if="selectedParentId" class="child-search">
                    <i class="fas fa-search"></i>
                    <input v-model.trim="childSearch" type="search" placeholder="Tìm danh mục con">
                  </label>
                </div>

                <p v-if="!selectedParentId" class="text-muted mb-0">
                  Chọn danh mục cha trước để xem các danh mục con.
                </p>

                <div v-else-if="!filteredChildFolders.length" class="empty-child-list">
                  Không tìm thấy danh mục con phù hợp.
                </div>

                <div v-else class="child-folder-grid">
                  <button
                    v-for="folder in filteredChildFolders"
                    :key="folder.id"
                    type="button"
                    class="child-folder-card"
                    :class="{ active: form.folderId === folder.id }"
                    @click="form.folderId = folder.id"
                  >
                    <i class="fas fa-folder-open"></i>
                    <span>{{ folder.name }}</span>
                  </button>
                </div>

                <div v-if="selectedFolder" class="selected-path">
                  <i class="fas fa-check-circle"></i>
                  Tài liệu sẽ được tải lên vào:
                  <strong>{{ selectedFolder.parent.name }} / {{ selectedFolder.name }}</strong>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label"><strong>Tag, phân cách bằng dấu phẩy</strong></label>
                <input v-model="form.tags" type="text" class="form-control" placeholder="VD: tuyển dụng, onboarding">
              </div>

              <div class="mb-3">
                <label class="form-label"><strong>Chọn file, tối đa 100MB</strong></label>
                <input ref="fileInput" type="file" class="form-control" @change="handleFileUpload" required>
                <div v-if="form.file" class="mt-2 text-muted">
                  {{ form.file.name }} ({{ formatFileSize(form.file.size) }})
                </div>
              </div>

              <button type="submit" class="btn btn-primary btn-lg w-100" :disabled="submitting || !form.folderId">
                {{ submitting ? "Đang tải lên Cloudinary..." : "Tải lên" }}
              </button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-4 mt-3 mt-lg-0">
        <div class="card">
          <div class="card-header"><strong>Quy trình</strong></div>
          <div class="card-body">
            <p>Tài liệu được lưu trên Cloudinary và URL được ghi vào <code>file_path</code>.</p>
            <p class="mb-0">Sau khi tải lên, tài liệu có trạng thái chờ phê duyệt.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { getDocumentMetadata, uploadDocument } from "@/services/documentService";
import { APP_SETTINGS_EVENT, isAutoSaveEnabled } from "@/services/appSettingsService";

const DRAFT_KEY = "docmanager_upload_draft";

export default {
  name: "Upload",
  data() {
    return {
      metadata: { folders: [] },
      selectedParentId: "",
      childSearch: "",
      form: {
        title: "",
        description: "",
        folderId: "",
        tags: "",
        file: null,
      },
      submitting: false,
      error: "",
      success: "",
    };
  },
  computed: {
    parentFolders() {
      const parents = new Map();

      this.metadata.folders.forEach((folder) => {
        if (folder.parent) {
          parents.set(folder.parent.id, folder.parent);
        }
      });

      return [...parents.values()].sort((a, b) => a.name.localeCompare(b.name));
    },
    childFolders() {
      return this.selectedParentId ? this.childrenFor(this.selectedParentId) : [];
    },
    filteredChildFolders() {
      const query = this.childSearch.toLowerCase();
      return this.childFolders.filter((folder) => folder.name.toLowerCase().includes(query));
    },
    selectedFolder() {
      return this.metadata.folders.find((folder) => folder.id === this.form.folderId) || null;
    },
  },
  async mounted() {
    try {
      this.metadata = await getDocumentMetadata();
      this.selectedParentId = this.parentFolders[0]?.id || "";
      this.restoreDraft();
    } catch (error) {
      this.error = error.response?.data?.message || "Không thể tải danh mục và thư mục.";
    }
    window.addEventListener(APP_SETTINGS_EVENT, this.handleSettingsChange);
  },
  beforeUnmount() {
    window.removeEventListener(APP_SETTINGS_EVENT, this.handleSettingsChange);
  },
  watch: {
    form: {
      deep: true,
      handler() {
        this.saveDraft();
      },
    },
    selectedParentId() {
      this.saveDraft();
    },
  },
  methods: {
    handleSettingsChange(event) {
      if (!event.detail?.auto_save) {
        localStorage.removeItem(DRAFT_KEY);
      }
    },
    restoreDraft() {
      if (!isAutoSaveEnabled()) return;

      try {
        const draft = JSON.parse(localStorage.getItem(DRAFT_KEY));
        if (!draft) return;

        this.form = {
          ...this.form,
          title: draft.title || "",
          description: draft.description || "",
          folderId: draft.folderId || "",
          tags: draft.tags || "",
        };
        this.selectedParentId = draft.selectedParentId || this.selectedParentId;
      } catch {
        localStorage.removeItem(DRAFT_KEY);
      }
    },
    saveDraft() {
      if (!isAutoSaveEnabled()) return;

      localStorage.setItem(DRAFT_KEY, JSON.stringify({
        title: this.form.title,
        description: this.form.description,
        folderId: this.form.folderId,
        tags: this.form.tags,
        selectedParentId: this.selectedParentId,
      }));
    },
    childrenFor(parentId) {
      return this.metadata.folders
        .filter((folder) => folder.parent?.id === parentId)
        .sort((a, b) => a.name.localeCompare(b.name));
    },
    selectParent(parentId) {
      this.selectedParentId = parentId;
      this.form.folderId = "";
      this.childSearch = "";
    },
    handleFileUpload(event) {
      this.form.file = event.target.files[0] || null;
    },
    async submitForm() {
      this.submitting = true;
      this.error = "";
      this.success = "";

      const formData = new FormData();
      formData.append("title", this.form.title);
      formData.append("description", this.form.description);
      formData.append("folder_id", this.form.folderId);
      formData.append("tags", this.form.tags);
      formData.append("file", this.form.file);

      try {
        const document = await uploadDocument(formData);
        this.success = "Tải tài liệu lên thành công. Tài liệu đang chờ phê duyệt.";
        localStorage.removeItem(DRAFT_KEY);
        this.resetForm();
        this.$router.push(`/documents/${document.id}`);
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể tải tài liệu lên.";
      } finally {
        this.submitting = false;
      }
    },
    resetForm() {
      this.form = { title: "", description: "", folderId: "", tags: "", file: null };
      this.childSearch = "";
      if (this.$refs.fileInput) this.$refs.fileInput.value = "";
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
.parent-category-grid,
.child-folder-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.parent-category-card,
.child-folder-card {
  display: flex;
  align-items: center;
  gap: 10px;
  min-height: 54px;
  border: 1px solid #dededb;
  border-radius: 8px;
  background: #fff;
  color: #292929;
  text-align: left;
}

.parent-category-card {
  padding: 10px;
}

.parent-category-card:hover,
.parent-category-card.active,
.child-folder-card:hover,
.child-folder-card.active {
  border-color: #171717;
}

.parent-category-card.active,
.child-folder-card.active {
  background: #171717;
  color: #fff;
}

.folder-icon {
  display: grid;
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  place-items: center;
  border-radius: 7px;
  background: #f1f1ef;
  color: #292929;
}

.parent-category-card.active .folder-icon {
  background: #fff;
}

.parent-category-card span:not(.folder-icon) {
  display: grid;
}

.parent-category-card small {
  color: #707070;
  font-size: 0.75rem;
}

.parent-category-card.active small {
  color: #d7d7d2;
}

.child-search {
  display: flex;
  width: min(260px, 100%);
  min-height: 34px;
  align-items: center;
  gap: 8px;
  padding: 0 10px;
  border: 1px solid #dededb;
  border-radius: 6px;
  color: #707070;
}

.child-search input {
  width: 100%;
  border: 0;
  outline: 0;
  background: transparent;
}

.child-folder-card {
  padding: 9px 11px;
}

.empty-child-list {
  padding: 14px;
  border: 1px dashed #dededb;
  border-radius: 8px;
  color: #707070;
}

.selected-path {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 12px;
  padding: 10px 12px;
  border-radius: 8px;
  background: #f0fdf4;
  color: #166534;
}

@media (max-width: 900px) {
  .parent-category-grid,
  .child-folder-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 560px) {
  .parent-category-grid,
  .child-folder-grid {
    grid-template-columns: 1fr;
  }
}
</style>
