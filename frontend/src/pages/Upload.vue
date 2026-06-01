<template>
  <div class="container-fluid py-4">
    <h1 class="mb-4"><i class="fas fa-arrow-up"></i> Tải Lên Tài Liệu</h1>

    <div class="row">
      <div class="col-md-8">
        <div class="card">
          <div class="card-body">
            <form @submit.prevent="submitForm">
              <!-- Tiêu đề -->
              <div class="mb-3">
                <label class="form-label"><strong><i class="fas fa-pen"></i> Tiêu đề tài liệu</strong></label>
                <input type="text" class="form-control" v-model="form.title" placeholder="Nhập tiêu đề" required>
              </div>

              <!-- Mô tả -->
              <div class="mb-3">
                <label class="form-label"><strong><i class="fas fa-file"></i> Mô tả</strong></label>
                <textarea class="form-control" v-model="form.description" rows="4" placeholder="Nhập mô tả tài liệu"></textarea>
              </div>

              <!-- Danh mục -->
              <div class="mb-3">
                <label class="form-label"><strong><i class="fas fa-tag"></i> Danh mục</strong></label>
                <select class="form-select" v-model="form.category" required>
                  <option value="">-- Chọn danh mục --</option>
                  <option value="process">Quy trình</option>
                  <option value="guide">Hướng dẫn</option>
                  <option value="policy">Chính sách</option>
                  <option value="workflow">Quy trình làm việc</option>
                  <option value="handbook">Sổ tay</option>
                  <option value="training">Đào tạo</option>
                </select>
              </div>

              <!-- Phòng ban -->
              <div class="mb-3">
                <label class="form-label"><strong><i class="fas fa-building"></i> Phòng ban</strong></label>
                <select class="form-select" v-model="form.department" required>
                  <option value="">-- Chọn phòng ban --</option>
                  <option value="HR">Nhân sự</option>
                  <option value="IT">CNTT</option>
                  <option value="Finance">Tài chính</option>
                  <option value="Ops">Vận hành</option>
                  <option value="Marketing">Marketing</option>
                  <option value="Legal">Pháp chế</option>
                </select>
              </div>

              <!-- Tag -->
              <div class="mb-3">
                <label class="form-label"><strong><i class="fas fa-bookmark"></i> Thẻ tag (phân cách bằng dấu phẩy)</strong></label>
                <input type="text" class="form-control" v-model="form.tags" placeholder="VD: HR, Tuyển dụng, Quản lý">
              </div>

              <!-- Upload file -->
              <div class="mb-3">
                <label class="form-label"><strong><i class="fas fa-cloud-upload-alt"></i> Chọn file (tối đa 100MB)</strong></label>
                <input type="file" class="form-control" @change="handleFileUpload" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
                <div v-if="form.file" class="mt-2 alert alert-success">
                  <i class="fas fa-check"></i> {{ form.file.name }} ({{ formatFileSize(form.file.size) }})
                </div>
              </div>

              <!-- Nút submit -->
              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-check"></i> Tải lên</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Hướng dẫn -->
      <div class="col-md-4">
        <div class="card bg-light">
          <div class="card-header bg-info text-white">
            <h5><i class="fas fa-info-circle"></i> Hướng dẫn tải lên</h5>
          </div>
          <div class="card-body">
            <ul>
              <li>Hỗ trợ các định dạng: PDF, Word, Excel, PowerPoint</li>
              <li>Dung lượng tối đa: 100MB</li>
              <li>Điền đầy đủ thông tin</li>
              <li>Có thể thêm nhiều tag để dễ tìm kiếm</li>
              <li>Tài liệu sẽ được phê duyệt trước khi công bố</li>
            </ul>
          </div>
        </div>

        <div class="card mt-3">
          <div class="card-header bg-success text-white">
            <h5><i class="fas fa-chart-bar"></i> Thống kê</h5>
          </div>
          <div class="card-body">
            <p><strong>Tài liệu đã tải:</strong> {{ uploadedDocuments }}</p>
            <p><strong>Dung lượng sử dụng:</strong> 2.5 GB / 10 GB</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Danh sách tài liệu đã tải -->
    <div class="mt-5">
      <h3><i class="fas fa-list"></i> Tài liệu đã tải gần đây</h3>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th><i class="fas fa-file"></i> Tên tài liệu</th>
              <th><i class="fas fa-calendar"></i> Ngày tải</th>
              <th><i class="fas fa-check"></i> Trạng thái</th>
              <th><i class="fas fa-cog"></i> Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="doc in recentUploads" :key="doc.id">
              <td>{{ doc.title }}</td>
              <td>{{ formatDate(doc.uploadedAt) }}</td>
              <td>
                <span v-if="doc.status === 'approved'" class="badge bg-success">Đã phê duyệt</span>
                <span v-else-if="doc.status === 'pending'" class="badge bg-warning">Chờ phê duyệt</span>
                <span v-else class="badge bg-danger">Từ chối</span>
              </td>
              <td>
                <button class="btn btn-sm btn-danger" @click="deleteUpload(doc.id)"><i class="fas fa-trash"></i></button>
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
  name: 'Upload',
  data() {
    return {
      form: {
        title: '',
        description: '',
        category: '',
        department: '',
        tags: '',
        file: null
      },
      uploadedDocuments: 25,
      recentUploads: [
        { id: 1, title: 'Hướng dẫn mới', uploadedAt: new Date('2026-06-01'), status: 'pending' },
        { id: 2, title: 'Quy trình cập nhật', uploadedAt: new Date('2026-05-31'), status: 'approved' },
        { id: 3, title: 'Tài liệu test', uploadedAt: new Date('2026-05-30'), status: 'rejected' }
      ]
    }
  },
  methods: {
    handleFileUpload(event) {
      this.form.file = event.target.files[0]
    },
    submitForm() {
      if (!this.form.title || !this.form.category || !this.form.department || !this.form.file) {
        alert('Vui lòng điền đầy đủ thông tin và chọn file')
        return
      }

      alert(`<i class="fas fa-check"></i> Tải lên thành công!\n\nTiêu đề: ${this.form.title}\nFile: ${this.form.file.name}`)
      
      // Thêm vào danh sách
      this.recentUploads.unshift({
        id: Math.max(...this.recentUploads.map(d => d.id), 0) + 1,
        title: this.form.title,
        uploadedAt: new Date(),
        status: 'pending'
      })

      // Reset form
      this.form = {
        title: '',
        description: '',
        category: '',
        department: '',
        tags: '',
        file: null
      }
    },
    deleteUpload(id) {
      if (confirm('Bạn chắc chắn muốn xóa?')) {
        this.recentUploads = this.recentUploads.filter(d => d.id !== id)
      }
    },
    formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes'
      const k = 1024
      const sizes = ['Bytes', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString('vi-VN')
    }
  }
}
</script>

<style scoped>
.card {
  border-radius: 8px;
  box-shadow: none;
}

.form-label {
  margin-bottom: 0.5rem;
}
</style>
