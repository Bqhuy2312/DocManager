<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="mb-1"><i class="fas fa-folder-tree me-2"></i>Quản Lý Thư Mục</h1>
        <p class="text-muted mb-0">Duyệt tài liệu theo cấu trúc cây thư mục.</p>
      </div>
      <button v-if="canManage" class="btn btn-primary" @click="openCreateModal">
        <i class="fas fa-plus me-1"></i>Thư mục mới
      </button>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <p v-if="loading" class="text-muted">Đang tải cây thư mục...</p>

    <div v-else class="folder-layout">
      <aside class="tree-panel">
        <div class="tree-panel-header">
          <strong>Cây thư mục</strong>
          <button class="btn btn-sm btn-link text-dark p-0" type="button" @click="expandAll">
            Mở tất cả
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
            @select="selectFolder"
            @toggle="toggleFolder"
            @remove="removeFolder"
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
          <button v-if="canManage && selectedFolder" class="btn btn-outline-primary btn-sm" @click="openCreateModal">
            <i class="fas fa-folder-plus me-1"></i>Tạo thư mục con
          </button>
        </div>

        <div v-if="!selectedFolder" class="empty-state">
          <i class="fas fa-folder-open"></i>
          <p>Chọn một thư mục trong cây để xem nội dung.</p>
        </div>

        <template v-else>
          <div v-if="selectedFolder.descendants?.length" class="subfolder-grid">
            <button
              v-for="child in selectedFolder.descendants"
              :key="child.id"
              class="subfolder-card"
              type="button"
              @click="selectFolder(child)"
            >
              <i class="fas fa-folder"></i>
              <span>{{ child.name }}</span>
            </button>
          </div>

          <div class="table-responsive">
            <table class="table table-hover">
              <thead class="table-light">
                <tr><th>Tài liệu</th><th>Danh mục</th><th>Cập nhật</th><th>Thao tác</th></tr>
              </thead>
              <tbody>
                <tr v-for="document in visibleDocuments" :key="document.id">
                  <td><i class="fas fa-file me-2"></i>{{ document.title }}</td>
                  <td>{{ document.category }}</td>
                  <td>{{ formatDate(document.updated_at) }}</td>
                  <td>
                    <button class="btn btn-sm btn-primary" @click="$router.push(`/documents/${document.id}`)">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-primary ms-1" @click="download(document)">
                      <i class="fas fa-download"></i>
                    </button>
                  </td>
                </tr>
                <tr v-if="!visibleDocuments.length">
                  <td colspan="4" class="text-muted">Chưa có tài liệu trong thư mục này.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>
      </section>
    </div>

    <div v-if="showCreateModal" class="modal d-block modal-backdrop-custom">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Tạo thư mục mới</h5>
            <button type="button" class="btn-close" @click="showCreateModal = false"></button>
          </div>
          <div class="modal-body">
            <label class="form-label">Tên thư mục</label>
            <input v-model="newFolder.name" class="form-control mb-3">
            <label class="form-label">Thư mục cha</label>
            <select v-model="newFolder.parentId" class="form-select">
              <option value="">Không có - tạo thư mục gốc</option>
              <option v-for="folder in flatFolders" :key="folder.id" :value="folder.id">
                {{ folder.label }}
              </option>
            </select>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" @click="showCreateModal = false">Hủy</button>
            <button class="btn btn-primary" @click="submitFolder">Tạo</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import FolderTreeNode from "@/components/FolderTreeNode.vue";
import { createFolder, deleteFolder, getDocuments, getFolders } from "@/services/documentService";

export default {
  name: "Folders",
  components: { FolderTreeNode },
  data() {
    return {
      folders: [],
      documents: [],
      selectedFolder: null,
      expandedIds: [],
      showCreateModal: false,
      newFolder: { name: "", parentId: "" },
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
      if (!this.expandedIds.includes(folder.id)) this.expandedIds.push(folder.id);
    },
    toggleFolder(id) {
      this.expandedIds = this.expandedIds.includes(id)
        ? this.expandedIds.filter((folderId) => folderId !== id)
        : [...this.expandedIds, id];
    },
    expandAll() {
      this.expandedIds = this.flatFolders.map((folder) => folder.id);
    },
    openCreateModal() {
      this.newFolder = { name: "", parentId: this.selectedFolder?.id || "" };
      this.showCreateModal = true;
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
    async removeFolder(folder) {
      if (!confirm(`Xóa thư mục "${folder.name}"?`)) return;
      try {
        await deleteFolder(folder.id);
        if (this.selectedFolder?.id === folder.id) this.selectedFolder = null;
        await this.loadData();
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể xóa thư mục.";
      }
    },
    download(document) {
      window.open(document.file_path, "_blank");
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString("vi-VN");
    },
  },
};
</script>

<style scoped>
.folder-layout {
  display: grid;
  grid-template-columns: 260px minmax(0, 1fr);
  min-height: 560px;
  overflow: hidden;
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
  display: flex;
  min-width: 150px;
  align-items: center;
  gap: 8px;
  padding: 9px 12px;
  border: 1px solid #dededb;
  border-radius: 6px;
  background: #fff;
  color: #292929;
}

.subfolder-card:hover {
  border-color: #171717;
}

.empty-state {
  display: grid;
  min-height: 360px;
  place-content: center;
  justify-items: center;
  color: #707070;
}

.empty-state i {
  margin-bottom: 12px;
  font-size: 2rem;
}

.modal-backdrop-custom {
  background: rgba(0, 0, 0, 0.45);
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
