<template>
  <div class="container-fluid py-4">
    <h1 class="mb-4"><i class="fas fa-users"></i> Quản Lý Thành Viên</h1>

    <button class="btn btn-primary mb-3" @click="showAddMemberModal = true">
      <i class="fas fa-plus"></i> Thêm thành viên
    </button>

    <div v-if="showAddMemberModal" class="modal d-block" style="background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Thêm thành viên mới</h5>
            <button type="button" class="btn-close" @click="showAddMemberModal = false"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Tên</label>
              <input type="text" class="form-control" v-model="newMember.name" placeholder="Nhập tên">
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" v-model="newMember.email" placeholder="Nhập email">
            </div>
            <div class="mb-3">
              <label class="form-label">Phòng ban</label>
              <select class="form-select" v-model="newMember.department">
                <option value="HR">Nhân sự</option>
                <option value="IT">CNTT</option>
                <option value="Finance">Tài chính</option>
                <option value="Ops">Vận hành</option>
                <option value="Marketing">Marketing</option>
                <option value="Legal">Pháp chế</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Vai trò</label>
              <select class="form-select" v-model="newMember.role">
                <option value="viewer">Người xem</option>
                <option value="editor">Biên tập viên</option>
                <option value="admin">Quản trị viên</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showAddMemberModal = false">Hủy</button>
            <button type="button" class="btn btn-primary" @click="addMember">Thêm</button>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-lock"></i> Ma Trận Phân Quyền</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered text-center">
            <thead class="table-light">
              <tr>
                <th>Quyền</th>
                <th>Người xem</th>
                <th>Biên tập viên</th>
                <th>Quản trị viên</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="permission in permissions" :key="permission.name">
                <td class="text-start"><strong>{{ permission.name }}</strong></td>
                <td><i class="fas" :class="permission.viewer ? 'fa-check' : 'fa-times'"></i></td>
                <td><i class="fas" :class="permission.editor ? 'fa-check' : 'fa-times'"></i></td>
                <td><i class="fas" :class="permission.admin ? 'fa-check' : 'fa-times'"></i></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-list"></i> Danh Sách Thành Viên</h5>
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th><i class="fas fa-user"></i> Tên</th>
              <th><i class="fas fa-envelope"></i> Email</th>
              <th><i class="fas fa-building"></i> Phòng ban</th>
              <th><i class="fas fa-ticket"></i> Vai trò</th>
              <th><i class="fas fa-calendar"></i> Ngày tham gia</th>
              <th><i class="fas fa-cog"></i> Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="member in members" :key="member.id">
              <td>{{ member.name }}</td>
              <td>{{ member.email }}</td>
              <td>{{ member.department }}</td>
              <td>
                <span v-if="member.role === 'admin'" class="badge bg-danger">Admin</span>
                <span v-else-if="member.role === 'editor'" class="badge bg-warning">Editor</span>
                <span v-else class="badge bg-secondary">Viewer</span>
              </td>
              <td>{{ formatDate(member.joinedAt) }}</td>
              <td>
                <button class="btn btn-sm btn-warning" @click="editMember(member.id)">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger ms-1" @click="deleteMember(member.id)">
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import { confirmDialog, notify } from "@/services/notificationService";

export default {
  name: 'Members',
  data() {
    return {
      showAddMemberModal: false,
      newMember: {
        name: '',
        email: '',
        department: 'HR',
        role: 'viewer'
      },
      permissions: [
        { name: 'Xem tài liệu', viewer: true, editor: true, admin: true },
        { name: 'Tạo tài liệu', viewer: false, editor: true, admin: true },
        { name: 'Chỉnh sửa tài liệu', viewer: false, editor: true, admin: true },
        { name: 'Xóa tài liệu', viewer: false, editor: false, admin: true },
        { name: 'Phê duyệt tài liệu', viewer: false, editor: false, admin: true },
        { name: 'Tải xuống tài liệu', viewer: true, editor: true, admin: true },
        { name: 'Quản lý thư mục', viewer: false, editor: true, admin: true },
        { name: 'Quản lý người dùng', viewer: false, editor: false, admin: true }
      ],
      members: [
        { id: 1, name: 'Nguyễn Văn A', email: 'admin@demo.com', department: 'Quản lý', role: 'admin', joinedAt: new Date('2026-01-01') },
        { id: 2, name: 'Trần Thị B', email: 'editor@demo.com', department: 'Nhân sự', role: 'editor', joinedAt: new Date('2026-02-15') },
        { id: 3, name: 'Lê Văn C', email: 'viewer@demo.com', department: 'CNTT', role: 'viewer', joinedAt: new Date('2026-03-20') },
        { id: 4, name: 'Phạm Thị D', email: 'user1@company.com', department: 'Tài chính', role: 'editor', joinedAt: new Date('2026-04-10') },
        { id: 5, name: 'Đặng Văn E', email: 'user2@company.com', department: 'Vận hành', role: 'viewer', joinedAt: new Date('2026-05-05') }
      ]
    }
  },
  methods: {
    addMember() {
      if (this.newMember.name && this.newMember.email) {
        this.members.push({
          id: Math.max(...this.members.map(m => m.id), 0) + 1,
          ...this.newMember,
          joinedAt: new Date()
        })
        this.newMember = { name: '', email: '', department: 'HR', role: 'viewer' }
        this.showAddMemberModal = false
        notify({ title: 'Đã thêm thành viên', message: 'Thành viên mới đã được thêm vào danh sách.' })
      }
    },
    editMember(id) {
      notify({ title: 'Chỉnh sửa thành viên', message: `Đang mở chỉnh sửa thành viên ${id}.`, type: 'info' })
    },
    async deleteMember(id) {
      const confirmed = await confirmDialog({
        title: 'Xóa thành viên',
        message: 'Bạn chắc chắn muốn xóa thành viên này?',
        confirmText: 'Xóa thành viên',
        tone: 'danger'
      })

      if (confirmed) {
        this.members = this.members.filter(m => m.id !== id)
        notify({ title: 'Đã xóa thành viên', message: 'Thành viên đã được xóa khỏi danh sách.' })
      }
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString('vi-VN')
    }
  }
}
</script>

<style scoped>
.modal.d-block {
  display: block;
}

.badge {
  padding: 0.35rem 0.65rem;
}
</style>
