<template>
  <div class="container-fluid py-4">
    <h1 class="mb-4"><i class="fas fa-cog"></i> Cài Đặt</h1>

    <div class="row">
      <div class="col-md-3">
        <div class="list-group">
          <button type="button" class="list-group-item list-group-item-action" 
                  :class="{ active: activeTab === 'profile' }"
                  @click="activeTab = 'profile'">
            <i class="fas fa-user"></i> Hồ sơ
          </button>
          <button type="button" class="list-group-item list-group-item-action"
                  :class="{ active: activeTab === 'general' }"
                  @click="activeTab = 'general'">
            <i class="fas fa-cog"></i> Chung
          </button>
          <button type="button" class="list-group-item list-group-item-action"
                  :class="{ active: activeTab === 'notification' }"
                  @click="activeTab = 'notification'">
            <i class="fas fa-bell"></i> Thông báo
          </button>
          <button type="button" class="list-group-item list-group-item-action"
                  :class="{ active: activeTab === 'security' }"
                  @click="activeTab = 'security'">
            <i class="fas fa-lock"></i> Bảo mật
          </button>
        </div>
      </div>

      <div class="col-md-9">
        <!-- Tab Hồ sơ -->
        <div v-if="activeTab === 'profile'" class="card">
          <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-user"></i> Thông Tin Hồ Sơ</h5>
          </div>
          <div class="card-body">
            <form @submit.prevent="saveProfile">
              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label">Tên đầy đủ</label>
                  <input type="text" class="form-control" v-model="profile.fullName">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Email</label>
                  <input type="email" class="form-control" v-model="profile.email" disabled>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label">Chức vụ</label>
                  <input type="text" class="form-control" v-model="profile.position">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Phòng ban</label>
                  <input type="text" class="form-control" v-model="profile.department">
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Vai trò</label>
                <input type="text" class="form-control" v-model="profile.role" disabled>
              </div>
              <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu thay đổi</button>
            </form>
          </div>
        </div>

        <!-- Tab Chung -->
        <div v-if="activeTab === 'general'" class="card">
          <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-cog"></i> Cài Đặt Chung</h5>
          </div>
          <div class="card-body">
            <form @submit.prevent="saveGeneral">
              <div class="mb-3">
                <label class="form-label"><i class="fas fa-globe"></i> Ngôn ngữ</label>
                <select class="form-select" v-model="settings.language">
                  <option value="vi">Tiếng Việt</option>
                  <option value="en">English</option>
                </select>
              </div>
              <div class="mb-3">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" v-model="settings.autoSave">
                  <label class="form-check-label"><i class="fas fa-save"></i> Tự động lưu</label>
                </div>
              </div>
              <div class="mb-3">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" v-model="settings.darkMode">
                  <label class="form-check-label"><i class="fas fa-moon"></i> Chế độ tối</label>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label"><i class="fas fa-clock"></i> Múi giờ</label>
                <select class="form-select" v-model="settings.timezone">
                  <option value="UTC+7">UTC+7 (Việt Nam)</option>
                  <option value="UTC+8">UTC+8</option>
                  <option value="UTC+9">UTC+9 (Tokyo)</option>
                </select>
              </div>
              <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu thay đổi</button>
            </form>
          </div>
        </div>

        <!-- Tab Thông báo -->
        <div v-if="activeTab === 'notification'" class="card">
          <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-bell"></i> Cài Đặt Thông Báo</h5>
          </div>
          <div class="card-body">
            <form @submit.prevent="saveNotification">
              <h6 class="mb-3"><i class="fas fa-envelope"></i> Email</h6>
              <div class="mb-3">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" v-model="notifications.emailEnabled">
                  <label class="form-check-label">Bật thông báo email</label>
                </div>
              </div>

              <h6 class="mb-3"><i class="fas fa-mobile"></i> Trong ứng dụng</h6>
              <div class="mb-3">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" v-model="notifications.inAppEnabled">
                  <label class="form-check-label">Bật thông báo trong ứng dụng</label>
                </div>
              </div>

              <h6 class="mb-3">Loại thông báo</h6>
              <div class="mb-2">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" v-model="notifications.newDocument" id="notif1">
                  <label class="form-check-label" for="notif1">Tài liệu mới được tải lên</label>
                </div>
              </div>
              <div class="mb-2">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" v-model="notifications.documentUpdated" id="notif2">
                  <label class="form-check-label" for="notif2">Tài liệu được cập nhật</label>
                </div>
              </div>
              <div class="mb-2">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" v-model="notifications.approvalNeeded" id="notif3">
                  <label class="form-check-label" for="notif3">Cần phê duyệt tài liệu</label>
                </div>
              </div>
              <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-save"></i> Lưu thay đổi</button>
            </form>
          </div>
        </div>

        <!-- Tab Bảo mật -->
        <div v-if="activeTab === 'security'" class="card">
          <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-lock"></i> Bảo Mật</h5>
          </div>
          <div class="card-body">
            <h6 class="mb-3"><i class="fas fa-key"></i> Đổi mật khẩu</h6>
            <form @submit.prevent="changePassword" class="mb-4">
              <div class="mb-3">
                <label class="form-label">Mật khẩu hiện tại</label>
                <input type="password" class="form-control" v-model="password.current">
              </div>
              <div class="mb-3">
                <label class="form-label">Mật khẩu mới</label>
                <input type="password" class="form-control" v-model="password.new">
              </div>
              <div class="mb-3">
                <label class="form-label">Xác nhận mật khẩu mới</label>
                <input type="password" class="form-control" v-model="password.confirm">
              </div>
              <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Đổi mật khẩu</button>
            </form>

            <hr>

            <h6 class="mb-3"><i class="fas fa-lock"></i> Xác thực hai yếu tố (2FA)</h6>
            <div class="mb-3">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" v-model="security.twoFAEnabled">
                <label class="form-check-label">Bật xác thực hai yếu tố</label>
              </div>
              <small class="text-muted">Tăng cường bảo mật tài khoản với mã xác thực</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Settings',
  data() {
    return {
      activeTab: 'profile',
      profile: {
        fullName: 'Nguyễn Văn A',
        email: 'admin@demo.com',
        position: 'Quản lý Hệ thống',
        department: 'CNTT',
        role: 'admin'
      },
      settings: {
        language: 'vi',
        autoSave: true,
        darkMode: false,
        timezone: 'UTC+7'
      },
      notifications: {
        emailEnabled: true,
        inAppEnabled: true,
        newDocument: true,
        documentUpdated: true,
        approvalNeeded: true
      },
      password: {
        current: '',
        new: '',
        confirm: ''
      },
      security: {
        twoFAEnabled: false
      }
    }
  },
  methods: {
    saveProfile() {
      alert('<i class="fas fa-check"></i> Đã lưu thông tin hồ sơ')
    },
    saveGeneral() {
      alert('<i class="fas fa-check"></i> Đã lưu cài đặt chung')
    },
    saveNotification() {
      alert('<i class="fas fa-check"></i> Đã lưu cài đặt thông báo')
    },
    changePassword() {
      if (this.password.new !== this.password.confirm) {
        alert('<i class="fas fa-times"></i> Mật khẩu xác nhận không khớp')
        return
      }
      alert('<i class="fas fa-check"></i> Đã đổi mật khẩu thành công')
      this.password = { current: '', new: '', confirm: '' }
    }
  }
}
</script>

<style scoped>
.list-group-item.active {
  background-color: #0d6efd;
  border-color: #0d6efd;
}

.card {
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}
</style>
