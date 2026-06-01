<template>
  <div class="container-fluid py-4">
    <h1 class="mb-4"><i class="fas fa-folder"></i> Thư Mục</h1>

    <button class="btn btn-primary mb-3" @click="showCreateFolderModal = true"><i class="fas fa-plus"></i> Tạo thư mục mới</button>

    <!-- Modal tạo thư mục -->
    <div v-if="showCreateFolderModal" class="modal d-block" style="background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Tạo thư mục mới</h5>
            <button type="button" class="btn-close" @click="showCreateFolderModal = false"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Tên thư mục</label>
              <input type="text" class="form-control" v-model="newFolderName" placeholder="Nhập tên thư mục">
            </div>
            <div class="mb-3">
              <label class="form-label">Phòng ban</label>
              <select class="form-select" v-model="newFolderDepartment">
                <option value="">-- Chọn phòng ban --</option>
                <option value="HR">Nhân sự</option>
                <option value="IT">CNTT</option>
                <option value="Finance">Tài chính</option>
                <option value="Ops">Vận hành</option>
                <option value="Marketing">Marketing</option>
                <option value="Legal">Pháp chế</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showCreateFolderModal = false">Hủy</button>
            <button type="button" class="btn btn-primary" @click="createFolder">Tạo</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Danh sách thư mục -->
    <div class="row">
      <div class="col-md-3" v-for="folder in folders" :key="folder.id">
        <div class="card folder-card" @click="expandFolder(folder.id)">
          <div class="card-body text-center">
            <h3><i class="fas fa-folder"></i></h3>
            <h6 class="card-title">{{ folder.name }}</h6>
            <small class="text-muted">{{ folder.department }}</small>
            <div class="mt-2 small text-muted">{{ folder.documentCount }} tài liệu</div>
          </div>
          <div class="card-footer bg-light">
            <button class="btn btn-sm btn-danger" @click="deleteFolder(folder.id)"><i class="fas fa-trash"></i></button>
          </div>
        </div>
      </div>
    </div>

    <!-- Chi tiết thư mục -->
    <div v-if="selectedFolder" class="mt-5">
      <h3><i class="fas fa-list"></i> {{ selectedFolder.name }}</h3>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th><i class="fas fa-file"></i> Tài liệu</th>
              <th><i class="fas fa-calendar"></i> Cập nhật</th>
              <th><i class="fas fa-cog"></i> Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="doc in selectedFolder.documents" :key="doc.id">
              <td>{{ doc.name }}</td>
              <td>{{ formatDate(doc.updatedAt) }}</td>
              <td>
                <button class="btn btn-sm btn-primary" @click="viewDocument(doc.id)"><i class="fas fa-eye"></i></button>
                <button class="btn btn-sm btn-success" @click="downloadDocument(doc.id)"><i class="fas fa-download"></i></button>
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
  name: 'Folders',
  data() {
    return {
      showCreateFolderModal: false,
      newFolderName: '',
      newFolderDepartment: '',
      selectedFolder: null,
      folders: [
        {
          id: 1,
          name: 'Nhân sự',
          department: 'HR',
          documentCount: 25,
          documents: [
            { id: 101, name: 'Quy trình tuyển dụng', updatedAt: new Date('2026-05-30') },
            { id: 102, name: 'Hướng dẫn HRM', updatedAt: new Date('2026-05-28') }
          ]
        },
        {
          id: 2,
          name: 'CNTT',
          department: 'IT',
          documentCount: 18,
          documents: [
            { id: 201, name: 'Chính sách bảo mật', updatedAt: new Date('2026-05-25') }
          ]
        },
        {
          id: 3,
          name: 'Tài chính',
          department: 'Finance',
          documentCount: 12,
          documents: [
            { id: 301, name: 'Quy trình thanh toán', updatedAt: new Date('2026-05-20') }
          ]
        },
        {
          id: 4,
          name: 'Vận hành',
          department: 'Ops',
          documentCount: 20,
          documents: [
            { id: 401, name: 'Quy trình mua hàng', updatedAt: new Date('2026-05-15') }
          ]
        },
        {
          id: 5,
          name: 'Marketing',
          department: 'Marketing',
          documentCount: 15,
          documents: [
            { id: 501, name: 'Chiến lược marketing', updatedAt: new Date('2026-05-10') }
          ]
        },
        {
          id: 6,
          name: 'Pháp chế',
          department: 'Legal',
          documentCount: 8,
          documents: [
            { id: 601, name: 'Các quy định pháp lý', updatedAt: new Date('2026-05-05') }
          ]
        }
      ]
    }
  },
  methods: {
    expandFolder(id) {
      const folder = this.folders.find(f => f.id === id)
      this.selectedFolder = this.selectedFolder?.id === id ? null : folder
    },
    createFolder() {
      if (this.newFolderName && this.newFolderDepartment) {
        const newFolder = {
          id: Math.max(...this.folders.map(f => f.id), 0) + 1,
          name: this.newFolderName,
          department: this.newFolderDepartment,
          documentCount: 0,
          documents: []
        }
        this.folders.push(newFolder)
        this.newFolderName = ''
        this.newFolderDepartment = ''
        this.showCreateFolderModal = false
      }
    },
    deleteFolder(id) {
      if (confirm('Bạn chắc chắn muốn xóa thư mục này?')) {
        this.folders = this.folders.filter(f => f.id !== id)
        if (this.selectedFolder?.id === id) this.selectedFolder = null
      }
    },
    viewDocument(id) {
      this.$router.push(`/documents/${id}`)
    },
    downloadDocument(id) {
      alert(`Tải xuống tài liệu ${id}`)
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString('vi-VN')
    }
  }
}
</script>

<style scoped>
.folder-card {
  cursor: pointer;
  border: 1px solid #dededb;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.folder-card:hover {
  transform: translateY(-4px);
  border-color: #171717;
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.08);
}

.modal.d-block {
  display: block;
}
</style>
