<template>
  <div class="container-fluid py-4">
    <h1 class="mb-4"><i class="fas fa-arrow-up"></i> Tải Lên Tài Liệu</h1>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="success" class="alert alert-success">{{ success }}</div>

    <div class="upload-form-shell">
      <div class="upload-form-column">
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
                <div class="upload-category-nav">
                  <button
                    v-for="parent in visibleParentFolders"
                    :key="parent.id"
                    type="button"
                    class="upload-category-tab"
                    :class="{ active: selectedParentId === parent.id }"
                    @click="selectParent(parent.id)"
                  >
                    <span class="upload-category-icon"><i class="fas fa-folder"></i></span>
                    <span>
                      <strong>{{ parent.name }}</strong>
                      <small>{{ childrenFor(parent.id).length }} Danh mục con</small>
                    </span>
                    <em>{{ childrenFor(parent.id).length }}</em>
                  </button>

                  <div v-if="hiddenParentFolders.length" class="upload-category-more">
                    <button
                      type="button"
                      class="upload-category-tab upload-more-button"
                      :class="{ active: showParentMenu }"
                      aria-label="Xem tất cả danh mục cha"
                      @click="showParentMenu = !showParentMenu"
                    >
                      <span class="upload-category-icon"><i class="fas fa-ellipsis-h"></i></span>
                      <span>
                        <strong>Thêm</strong>
                        <small>{{ parentFolders.length }} danh mục cha</small>
                      </span>
                      <em>{{ hiddenParentFolders.length }}</em>
                    </button>

                    <div v-if="showParentMenu" class="upload-category-panel">
                      <div class="upload-category-panel-header">
                        <strong>Tất cả danh mục cha</strong>
                        <button type="button" aria-label="Đóng" @click="showParentMenu = false">
                          <i class="fas fa-xmark"></i>
                        </button>
                      </div>
                      <div class="upload-category-panel-grid">
                        <button
                          v-for="parent in parentFolders"
                          :key="parent.id"
                          type="button"
                          class="upload-category-panel-item"
                          :class="{ active: selectedParentId === parent.id }"
                          @click="selectParent(parent.id)"
                        >
                          <i class="fas fa-folder"></i>
                          <span>
                            <strong>{{ parent.name }}</strong>
                            <small>{{ childrenFor(parent.id).length }} danh mục con</small>
                          </span>
                          <em>{{ childrenFor(parent.id).length }}</em>
                        </button>
                      </div>
                    </div>
                  </div>
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

                <div v-else class="upload-category-nav">
                  <button
                    v-for="folder in visibleChildFolders"
                    :key="folder.id"
                    type="button"
                    class="upload-category-tab"
                    :class="{ active: form.folderId === folder.id }"
                    @click="selectChild(folder.id)"
                  >
                    <span class="upload-category-icon"><i class="fas fa-folder-open"></i></span>
                    <span>
                      <strong>{{ folder.name }}</strong>
                      <small>Danh mục con</small>
                    </span>
                  </button>

                  <div v-if="hiddenChildFolders.length" class="upload-category-more">
                    <button
                      type="button"
                      class="upload-category-tab upload-more-button"
                      :class="{ active: showChildMenu }"
                      aria-label="Xem tất cả danh mục con"
                      @click="showChildMenu = !showChildMenu"
                    >
                      <span class="upload-category-icon"><i class="fas fa-ellipsis-h"></i></span>
                      <span>
                        <strong>Thêm</strong>
                        <small>{{ filteredChildFolders.length }} danh mục con</small>
                      </span>
                      <em>{{ hiddenChildFolders.length }}</em>
                    </button>

                    <div v-if="showChildMenu" class="upload-category-panel">
                      <div class="upload-category-panel-header">
                        <strong>Tất cả danh mục con</strong>
                        <button type="button" aria-label="Đóng" @click="showChildMenu = false">
                          <i class="fas fa-xmark"></i>
                        </button>
                      </div>
                      <div class="upload-category-panel-grid">
                        <button
                          v-for="folder in filteredChildFolders"
                          :key="folder.id"
                          type="button"
                          class="upload-category-panel-item"
                          :class="{ active: form.folderId === folder.id }"
                          @click="selectChild(folder.id)"
                        >
                          <i class="fas fa-folder-open"></i>
                          <span>
                            <strong>{{ folder.name }}</strong>
                            <small>{{ selectedParentName }}</small>
                          </span>
                        </button>
                      </div>
                    </div>
                  </div>
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
                {{ submitting ? "Đang tải lên ..." : "Tải lên" }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { getDocumentMetadata, uploadDocument } from "@/services/documentService";
import { APP_SETTINGS_EVENT, isAutoSaveEnabled } from "@/services/appSettingsService";
import realtimeRefresh from "@/mixins/realtimeRefresh";

const DRAFT_KEY = "docmanager_upload_draft";

export default {
  name: "Upload",
  mixins: [realtimeRefresh],
  realtimeScopes: ["folder"],
  data() {
    return {
      metadata: { folders: [] },
      selectedParentId: "",
      showParentMenu: false,
      showChildMenu: false,
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
    visibleParentFolders() {
      return this.parentFolders.slice(0, 5);
    },
    hiddenParentFolders() {
      return this.parentFolders.slice(5);
    },
    filteredChildFolders() {
      const query = this.childSearch.toLowerCase();
      return this.childFolders.filter((folder) => folder.name.toLowerCase().includes(query));
    },
    visibleChildFolders() {
      return this.filteredChildFolders.slice(0, 5);
    },
    hiddenChildFolders() {
      return this.filteredChildFolders.slice(5);
    },
    selectedParentName() {
      return this.parentFolders.find((folder) => folder.id === this.selectedParentId)?.name || "";
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
    childSearch() {
      this.showChildMenu = false;
    },
  },
  methods: {
    async refreshRealtimeData() {
      const selectedParentId = this.selectedParentId;
      const selectedFolderId = this.form.folderId;
      this.metadata = await getDocumentMetadata();
      this.selectedParentId = this.parentFolders.some((folder) => folder.id === selectedParentId)
        ? selectedParentId
        : this.parentFolders[0]?.id || "";
      this.form.folderId = this.metadata.folders.some((folder) => folder.id === selectedFolderId)
        ? selectedFolderId
        : "";
    },
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
      this.showParentMenu = false;
      this.showChildMenu = false;
    },
    selectChild(folderId) {
      this.form.folderId = folderId;
      this.showChildMenu = false;
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
      this.showParentMenu = false;
      this.showChildMenu = false;
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
.upload-form-shell {
  display: flex;
  justify-content: center;
}

.upload-form-column {
  width: min(100%, 980px);
}

.upload-category-nav {
  position: relative;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  max-width: 100%;
}

.upload-category-tab {
  display: grid;
  flex: 1 1 180px;
  grid-template-columns: auto minmax(0, 1fr) auto;
  align-items: center;
  gap: 8px;
  min-width: 0;
  max-width: 240px;
  min-height: 54px;
  padding: 8px 10px;
  border: 1px solid #dededb;
  border-radius: 7px;
  background: #fff;
  color: #292929;
  text-align: left;
}

.upload-category-tab:hover,
.upload-category-tab.active {
  border-color: #171717;
}

.upload-category-tab.active {
  background: #171717;
  color: #fff;
}

.upload-category-icon {
  display: grid;
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  place-items: center;
  border-radius: 7px;
  background: #f1f1ef;
  color: #292929;
}

.upload-category-tab.active .upload-category-icon {
  background: #fff;
}

.upload-category-tab span:not(.upload-category-icon),
.upload-category-panel-item span {
  display: grid;
  min-width: 0;
}

.upload-category-tab strong,
.upload-category-panel-item strong {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.upload-category-tab small,
.upload-category-tab em,
.upload-category-panel-item small,
.upload-category-panel-item em {
  color: #707070;
  font-size: 0.72rem;
  font-style: normal;
}

.upload-category-tab.active small,
.upload-category-tab.active em,
.upload-category-panel-item.active small,
.upload-category-panel-item.active em {
  color: #d7d7d2;
}

.upload-category-more {
  position: static;
  flex: 1 1 180px;
  max-width: 240px;
}

.upload-category-more .upload-category-tab {
  width: 100%;
  max-width: 100%;
}

.upload-more-button {
  border-style: dashed;
}

.upload-category-panel {
  position: absolute;
  top: calc(100% + 10px);
  left: 0;
  z-index: 30;
  width: min(620px, 100%);
  max-height: 380px;
  overflow: auto;
  padding: 12px;
  border: 1px solid #dededb;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 18px 42px rgba(23, 23, 23, 0.16);
}

.upload-category-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 10px;
}

.upload-category-panel-header button {
  display: inline-grid;
  width: 30px;
  height: 30px;
  place-items: center;
  border: 1px solid #dededb;
  border-radius: 7px;
  background: #fff;
  color: #292929;
}

.upload-category-panel-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 8px;
}

.upload-category-panel-item {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  align-items: center;
  gap: 10px;
  min-width: 0;
  padding: 10px;
  border: 1px solid #dededb;
  border-radius: 7px;
  background: #fff;
  color: #292929;
  text-align: left;
}

.upload-category-panel-item:hover,
.upload-category-panel-item.active {
  border-color: #171717;
}

.upload-category-panel-item.active {
  background: #171717;
  color: #fff;
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
  .upload-category-tab,
  .upload-category-more {
    flex-basis: 100%;
    max-width: 100%;
  }

  .upload-category-panel {
    left: 0;
    width: 100%;
  }
}
</style>
