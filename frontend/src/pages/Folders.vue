<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="mb-1"><i class="fas fa-folder-tree me-2"></i>Quản Lý Thư Mục</h1>
        <p class="text-muted mb-0">Duyệt tài liệu theo cấu trúc cây thư mục.</p>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <Loading v-if="loading" type="folders" />

    <div v-else class="folder-layout" @click="closeFolderMenus">
      <aside class="tree-panel">
        <div class="tree-panel-header">
          <strong>Cây thư mục</strong>
          <button
            v-if="canManage"
            class="tree-add-root"
            type="button"
            aria-label="Thêm thư mục cha"
            @click.stop="openCreateModal(null)"
          >
            <i class="fas fa-plus"></i>
          </button>
        </div>
        <div class="tree-panel-body">
          <FolderTreeNode
            v-for="folder in folders"
            :key="folder.id"
            :folder="folder"
            :selected-id="selectedFolder?.id"
            :expanded-ids="expandedIds"
            :can-manage="canManage"
            :open-menu-folder-id="openMenuFolderId"
            :document-counts="documentCounts"
            @select="selectFolder"
            @toggle="toggleFolder"
            @remove="removeFolder"
            @edit="openEditModal"
            @add-child="openCreateModal"
            @toggle-menu="toggleFolderMenu"
          />
        </div>
      </aside>

      <section class="content-panel">
        <div class="content-header">
          <div>
            <div class="breadcrumb-line">
              <i class="fas fa-home me-1"></i>
              <span v-for="(item, index) in breadcrumbs" :key="item.id">
                <i v-if="index" class="fas fa-chevron-right mx-2"></i>{{ item.name }}
              </span>
            </div>
            <h3 class="mt-2 mb-0">{{ selectedFolder?.name || "Chọn một thư mục" }}</h3>
          </div>
        </div>

        <div v-if="!selectedFolder" class="empty-state">
          <i class="fas fa-folder-open"></i>
          <p>Chọn một thư mục trong cây để xem nội dung.</p>
        </div>

        <template v-else>
          <div v-if="selectedFolder.descendants?.length" class="subfolder-grid">
            <div
              v-for="child in selectedFolder.descendants"
              :key="child.id"
              class="subfolder-card"
            >
              <button class="subfolder-main" type="button" @click="selectFolder(child)">
                <i class="fas fa-folder"></i>
                <span>{{ child.name }}</span>
                <small class="document-count">{{ documentCount(child.id) }}</small>
              </button>

              <div v-if="canManage" class="subfolder-menu">
                <button
                  class="subfolder-action"
                  type="button"
                  aria-label="Tùy chọn thư mục"
                  @click.stop="toggleFolderMenu(child.id)"
                >
                  <i class="fas fa-ellipsis-h"></i>
                </button>

                <div v-if="openMenuFolderId === child.id" class="subfolder-popover" @click.stop>
                  <button v-if="canAddChild(child)" type="button" @click="openCreateModal(child)">
                    <i class="fas fa-folder-plus me-2"></i>Thêm thư mục con
                  </button>
                  <button type="button" @click="openEditModal(child)">
                    <i class="fas fa-pen me-2"></i>Sửa tên thư mục
                  </button>
                  <button type="button" class="danger" @click="removeFolder(child)">
                    <i class="fas fa-trash me-2"></i>Xóa thư mục
                  </button>
                </div>
              </div>
            </div>
          </div>

          <template v-if="visibleDocuments.length">
            <div class="row g-4 document-list">
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
                      <span><i class="fas fa-eye me-1"></i>Lượt truy cập: {{ document.access_count || 0 }}</span>
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
              :page="currentPage"
              :per-page="itemsPerPage"
              :total="visibleDocuments.length"
              @update:page="currentPage = $event"
            />
          </template>

          <div v-else class="empty-documents">
            <i class="far fa-folder-open"></i>
            <span>Chưa có tài liệu trong thư mục này.</span>
          </div>
        </template>
      </section>
    </div>

    <Teleport to="body">
      <div v-if="showCreateModal" class="create-modal-backdrop" @click.self="closeCreateModal">
        <div class="modal-dialog create-modal-dialog" @click.stop>
          <div class="modal-content create-folder-modal">
            <div class="modal-header create-folder-header">
              <div class="create-folder-title">
                <span class="create-folder-icon">
                  <i class="fas" :class="newFolder.parentId ? 'fa-folder-plus' : 'fa-folder-tree'"></i>
                </span>
                <div>
                  <h5 class="modal-title">{{ newFolder.parentId ? "Thêm thư mục con" : "Thêm thư mục cha" }}</h5>
                  <p class="mb-0 text-muted">
                    {{ newFolder.parentId ? "Tạo thư mục mới bên trong thư mục đã chọn." : "Tạo một thư mục cấp cao nhất trong cây." }}
                  </p>
                </div>
              </div>
              <button type="button" class="btn-close" aria-label="Đóng" @click="closeCreateModal"></button>
            </div>
            <div class="modal-body create-folder-body">
              <template v-if="newFolder.parentId">
                <label class="form-label">Thư mục cha</label>
                <div class="input-shell disabled mb-3">
                  <i class="fas fa-folder"></i>
                  <input :value="parentFolderName" disabled>
                </div>
              </template>

              <label class="form-label">{{ newFolder.parentId ? "Tên thư mục con" : "Tên thư mục cha" }}</label>
              <div class="input-shell">
                <i class="fas fa-pen"></i>
                <input v-model="newFolder.name" type="text" placeholder="Nhập tên thư mục">
              </div>
            </div>
            <div class="modal-footer create-folder-footer">
              <button class="btn btn-light" @click="closeCreateModal">Hủy</button>
              <button class="btn btn-primary" @click="submitFolder">Tạo</button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div v-if="showEditModal" class="create-modal-backdrop" @click.self="closeEditModal">
        <div class="modal-dialog create-modal-dialog" @click.stop>
          <div class="modal-content create-folder-modal">
            <div class="modal-header create-folder-header">
              <div class="create-folder-title">
                <span class="create-folder-icon">
                  <i class="fas fa-pen"></i>
                </span>
                <div>
                  <h5 class="modal-title">Sửa tên thư mục</h5>
                  <p class="mb-0 text-muted">Cập nhật tên thư mục cha hoặc thư mục con đã chọn.</p>
                </div>
              </div>
              <button type="button" class="btn-close" aria-label="Đóng" @click="closeEditModal"></button>
            </div>
            <div class="modal-body create-folder-body">
              <label class="form-label">Tên thư mục</label>
              <div class="input-shell">
                <i class="fas fa-folder"></i>
                <input v-model="editFolderForm.name" type="text" placeholder="Nhập tên thư mục">
              </div>
            </div>
            <div class="modal-footer create-folder-footer">
              <button class="btn btn-light" @click="closeEditModal">Hủy</button>
              <button class="btn btn-primary" @click="submitEditFolder">Lưu thay đổi</button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script>
import FolderTreeNode from "@/components/FolderTreeNode.vue";
import Loading from "@/components/common/Loading.vue";
import PaginationControls from "@/components/common/PaginationControls.vue";
import {
  createFolder,
  deleteFolder,
  downloadDocumentFile,
  getDocuments,
  getFolders,
  toggleFavoriteDocument,
  updateFolder,
} from "@/services/documentService";
import { confirmDialog, notify } from "@/services/notificationService";

export default {
  name: "Folders",
  components: { FolderTreeNode, Loading, PaginationControls },
  data() {
    return {
      folders: [],
      documents: [],
      selectedFolder: null,
      expandedIds: [],
      showCreateModal: false,
      newFolder: { name: "", parentId: "" },
      showEditModal: false,
      editFolderForm: { id: "", name: "" },
      openMenuFolderId: null,
      currentPage: 1,
      itemsPerPage: 15,
      loading: false,
      error: "",
    };
  },
  computed: {
    canManage() {
      try {
        return ["admin", "editor"].includes(JSON.parse(localStorage.getItem("user"))?.role);
      } catch {
        return false;
      }
    },
    flatFolders() {
      return this.flattenFolders(this.folders);
    },
    parentFolderName() {
      return this.findFolder(this.newFolder.parentId)?.name || "";
    },
    breadcrumbs() {
      if (!this.selectedFolder) return [];
      const path = [];
      let current = this.selectedFolder;
      while (current) {
        path.unshift(current);
        current = this.findFolder(current.parent_id);
      }
      return path;
    },
    visibleDocuments() {
      if (!this.selectedFolder) return [];
      return this.documents.filter((document) => document.folder_id === this.selectedFolder.id);
    },
    paginatedDocuments() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      return this.visibleDocuments.slice(start, start + this.itemsPerPage);
    },
    documentCounts() {
      return this.documents.reduce((counts, document) => {
        counts[document.folder_id] = (counts[document.folder_id] || 0) + 1;
        return counts;
      }, {});
    },
  },
  async mounted() {
    await this.loadData();
    this.expandAll();
  },
  methods: {
    async loadData() {
      this.loading = true;
      this.error = "";
      try {
        [this.folders, this.documents] = await Promise.all([getFolders(), getDocuments()]);
        if (this.selectedFolder) this.selectedFolder = this.findFolder(this.selectedFolder.id);
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể tải cây thư mục.";
      } finally {
        this.loading = false;
      }
    },
    flattenFolders(folders, depth = 0) {
      return folders.flatMap((folder) => [
        { ...folder, label: `${"— ".repeat(depth)}${folder.name}` },
        ...this.flattenFolders(folder.descendants || [], depth + 1),
      ]);
    },
    findFolder(id) {
      return this.flatFolders.find((folder) => folder.id === id) || null;
    },
    selectFolder(folder) {
      this.selectedFolder = folder;
      this.openMenuFolderId = null;
      this.currentPage = 1;
      if (!this.expandedIds.includes(folder.id)) this.expandedIds.push(folder.id);
    },
    viewDocument(id) {
      this.$router.push(`/documents/${id}`);
    },
    documentCount(folderId) {
      return this.documentCounts[folderId] || 0;
    },
    canAddChild(folder) {
      return !folder?.parent_id;
    },
    toggleFolder(id) {
      this.expandedIds = this.expandedIds.includes(id)
        ? this.expandedIds.filter((folderId) => folderId !== id)
        : [...this.expandedIds, id];
    },
    expandAll() {
      this.expandedIds = this.flatFolders.map((folder) => folder.id);
    },
    toggleFolderMenu(id) {
      this.openMenuFolderId = this.openMenuFolderId === id ? null : id;
    },
    closeFolderMenus() {
      this.openMenuFolderId = null;
    },
    openCreateModal(folder = null) {
      this.openMenuFolderId = null;
      this.newFolder = { name: "", parentId: folder?.id || "" };
      this.showCreateModal = true;
    },
    closeCreateModal() {
      this.showCreateModal = false;
    },
    openEditModal(folder) {
      this.openMenuFolderId = null;
      this.editFolderForm = { id: folder.id, name: folder.name };
      this.showEditModal = true;
    },
    closeEditModal() {
      this.showEditModal = false;
      this.editFolderForm = { id: "", name: "" };
    },
    async submitFolder() {
      if (!this.newFolder.name.trim()) return;
      try {
        await createFolder({ name: this.newFolder.name, parent_id: this.newFolder.parentId || null });
        this.showCreateModal = false;
        await this.loadData();
        this.expandAll();
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể tạo thư mục.";
      }
    },
    async submitEditFolder() {
      if (!this.editFolderForm.name.trim()) return;
      try {
        await updateFolder(this.editFolderForm.id, { name: this.editFolderForm.name.trim() });
        this.showEditModal = false;
        await this.loadData();
        this.expandAll();
        notify({ title: "Đã cập nhật thư mục", message: "Tên thư mục đã được thay đổi." });
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể cập nhật thư mục.";
      }
    },
    async removeFolder(folder) {
      this.openMenuFolderId = null;
      const confirmed = await confirmDialog({
        title: "Xóa thư mục",
        message: `Bạn chắc chắn muốn xóa thư mục "${folder.name}"?`,
        confirmText: "Xóa thư mục",
        tone: "danger",
      });
      if (!confirmed) return;

      try {
        await deleteFolder(folder.id);
        if (this.selectedFolder?.id === folder.id) this.selectedFolder = null;
        await this.loadData();
        notify({ title: "Đã xóa thư mục", message: `Thư mục "${folder.name}" đã được xóa.` });
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể xóa thư mục.";
      }
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
.folder-layout {
  display: grid;
  grid-template-columns: 260px minmax(0, 1fr);
  min-height: 560px;
  overflow: visible;
  border: 1px solid #dededb;
  border-radius: 8px;
  background: #fff;
}

.tree-panel {
  border-right: 1px solid #dededb;
  background: #fbfbfa;
}

.tree-panel-header,
.content-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 14px;
  border-bottom: 1px solid #dededb;
}

.tree-panel-body {
  padding: 10px 8px;
}

.tree-add-root {
  display: inline-grid;
  width: 30px;
  height: 30px;
  place-items: center;
  padding: 0;
  border: 0;
  border-radius: 6px;
  background: #f1f1ef;
  color: #292929;
}

.tree-add-root:hover,
.tree-add-root:focus {
  background: #171717;
  color: #fff;
  outline: 0;
}

.content-panel {
  min-width: 0;
}

.breadcrumb-line {
  color: #707070;
  font-size: 0.78rem;
}

.subfolder-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  padding: 14px;
  border-bottom: 1px solid #dededb;
}

.subfolder-card {
  position: relative;
  display: flex;
  min-width: 180px;
  max-width: 100%;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  border: 1px solid #dededb;
  border-radius: 6px;
  background: #fff;
  color: #292929;
}

.subfolder-card:hover {
  border-color: #171717;
}

.subfolder-main,
.subfolder-action {
  border: 0;
  background: transparent;
  color: #292929;
}

.subfolder-main {
  display: flex;
  flex: 1;
  min-width: 0;
  align-items: center;
  gap: 8px;
  padding: 9px 12px;
  text-align: left;
}

.subfolder-main span {
  display: block;
  flex: 1 1 auto;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.document-count {
  display: inline-grid;
  flex: 0 0 auto;
  min-width: 20px;
  height: 18px;
  place-items: center;
  margin-left: auto;
  padding: 0 6px;
  border-radius: 999px;
  background: #ededeb;
  color: #707070;
  font-size: 0.68rem;
  font-weight: 600;
}

.subfolder-menu {
  position: relative;
  flex: 0 0 auto;
}

.subfolder-action {
  padding: 9px 10px;
  color: #707070;
}

.subfolder-popover {
  position: absolute;
  right: 0;
  top: calc(100% + 4px);
  z-index: 20;
  display: grid;
  min-width: 170px;
  overflow: hidden;
  border: 1px solid #dededb;
  border-radius: 7px;
  background: #fff;
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
}

.subfolder-popover button {
  display: flex;
  align-items: center;
  padding: 9px 11px;
  border: 0;
  background: #fff;
  color: #292929;
  font-size: 0.82rem;
  text-align: left;
}

.subfolder-popover button:hover {
  background: #f1f1ef;
}

.subfolder-popover .danger {
  color: #b42318;
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

.empty-state,
.empty-documents {
  display: grid;
  min-height: 240px;
  place-content: center;
  justify-items: center;
  gap: 6px;
  color: #707070;
}

.empty-state {
  min-height: 360px;
}

.empty-state i,
.empty-documents i {
  margin-bottom: 12px;
  font-size: 2rem;
}

.create-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 1050;
  display: grid;
  place-items: start center;
  overflow-y: auto;
  padding: 48px 16px;
  background: rgba(0, 0, 0, 0.45);
}

.create-modal-dialog {
  width: min(500px, 100%);
  margin: 0;
}

.create-folder-modal {
  overflow: hidden;
  border: 1px solid #dededb;
  box-shadow: 0 18px 44px rgba(0, 0, 0, 0.18);
}

.create-folder-header {
  align-items: flex-start;
  padding: 18px;
  background: #fbfbfa;
}

.create-folder-title {
  display: flex;
  gap: 12px;
}

.create-folder-icon {
  display: inline-grid;
  width: 42px;
  height: 42px;
  flex: 0 0 42px;
  place-items: center;
  border: 1px solid #dededb;
  border-radius: 8px;
  background: #fff;
  color: #171717;
}

.create-folder-title .modal-title {
  font-size: 1rem;
  font-weight: 700;
}

.create-folder-title p {
  margin-top: 3px;
  font-size: 0.82rem;
}

.create-folder-body {
  padding: 18px;
}

.create-folder-body .form-label {
  color: #292929;
  font-size: 0.82rem;
  font-weight: 600;
}

.input-shell {
  display: flex;
  min-height: 40px;
  align-items: center;
  gap: 10px;
  padding: 0 12px;
  border: 1px solid #dededb;
  border-radius: 7px;
  background: #fff;
  color: #707070;
}

.input-shell:focus-within {
  border-color: #171717;
}

.input-shell input {
  width: 100%;
  border: 0;
  outline: 0;
  background: transparent;
  color: #292929;
}

.input-shell.disabled {
  background: #f7f7f5;
}

.input-shell.disabled input {
  color: #707070;
}

.create-folder-footer {
  padding: 14px 18px;
  background: #fbfbfa;
}

@media (max-width: 900px) {
  .folder-layout {
    grid-template-columns: 1fr;
  }

  .tree-panel {
    border-right: 0;
    border-bottom: 1px solid #dededb;
  }
}
</style>

