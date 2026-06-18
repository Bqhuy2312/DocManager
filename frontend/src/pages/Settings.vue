<template>
  <div class="container-fluid py-4">
    <h1 class="mb-4"><i class="fas fa-cog"></i> Cài Đặt</h1>

    <div v-if="loading" class="card">
      <div class="card-body text-muted">Đang tải cài đặt...</div>
    </div>

    <div v-else class="row">
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
            <div class="d-flex align-items-center gap-3 mb-4">
              <div class="profile-avatar">
                <img v-if="currentUser?.avatar" :src="currentUser.avatar" alt="Avatar">
                <i v-else class="fas fa-user"></i>
              </div>
              <div>
                <label class="form-label"><strong>Ảnh đại diện</strong></label>
                <input type="file" class="form-control" accept="image/*" :disabled="avatarUploading" @change="changeAvatar">
                <small class="text-muted">Ảnh được lưu riêng trong thư mục Cloudinary avatars.</small>
              </div>
            </div>
            <div v-if="avatarError" class="alert alert-danger">{{ avatarError }}</div>
            <form @submit.prevent="saveProfile">
              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label">Tên đầy đủ</label>
                  <input type="text" class="form-control" v-model.trim="profile.fullName" :disabled="savingProfile">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Email</label>
                  <input type="email" class="form-control" v-model="profile.email" disabled>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label">Chức vụ</label>
                  <input type="text" class="form-control" v-model.trim="profile.position" :disabled="savingProfile">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Phòng ban</label>
                  <input type="text" class="form-control" v-model="profile.department" disabled>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Vai trò</label>
                <input type="text" class="form-control" v-model="profile.role" disabled>
              </div>
              <button type="submit" class="btn btn-primary" :disabled="savingProfile">
                <i class="fas fa-save"></i> {{ savingProfile ? 'Đang lưu...' : 'Lưu thay đổi' }}
              </button>
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
                <select class="form-select" v-model="settings.language" :disabled="savingGeneral">
                  <option value="vi">Tiếng Việt</option>
                  <option value="en">English</option>
                </select>
              </div>
              <div class="mb-3">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" v-model="settings.autoSave" :disabled="savingGeneral">
                  <label class="form-check-label"><i class="fas fa-save"></i> Tự động lưu</label>
                </div>
              </div>
              <div class="mb-3">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" v-model="settings.darkMode" :disabled="savingGeneral">
                  <label class="form-check-label"><i class="fas fa-moon"></i> Chế độ tối</label>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label"><i class="fas fa-clock"></i> Múi giờ</label>
                <select class="form-select" v-model="settings.timezone" :disabled="savingGeneral">
                  <option value="UTC+7">UTC+7 (Việt Nam)</option>
                  <option value="UTC+8">UTC+8</option>
                  <option value="UTC+9">UTC+9 (Tokyo)</option>
                </select>
              </div>
              <button type="submit" class="btn btn-primary" :disabled="savingGeneral">
                <i class="fas fa-save"></i> {{ savingGeneral ? 'Đang lưu...' : 'Lưu thay đổi' }}
              </button>
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
              <template v-if="false">
                <h6 class="mb-3"><i class="fas fa-envelope"></i> Email</h6>
                <div class="mb-3">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" v-model="notifications.emailEnabled" :disabled="savingNotification">
                    <label class="form-check-label">Bật thông báo email</label>
                  </div>
                </div>
              </template>

              <h6 class="mb-3"><i class="fas fa-mobile"></i> Trong ứng dụng</h6>
              <div class="mb-3">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" v-model="notifications.inAppEnabled" :disabled="savingNotification">
                  <label class="form-check-label">Bật thông báo trong ứng dụng</label>
                </div>
              </div>

              <h6 class="mb-3">Loại thông báo</h6>
              <div class="mb-2">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" v-model="notifications.newDocument" id="notif1" :disabled="savingNotification">
                  <label class="form-check-label" for="notif1">Tài liệu mới được tải lên</label>
                </div>
              </div>
              <div class="mb-2">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" v-model="notifications.documentUpdated" id="notif2" :disabled="savingNotification">
                  <label class="form-check-label" for="notif2">Tài liệu được cập nhật</label>
                </div>
              </div>
              <div class="mb-2">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" v-model="notifications.approvalNeeded" id="notif3" :disabled="savingNotification">
                  <label class="form-check-label" for="notif3">Cần phê duyệt tài liệu</label>
                </div>
              </div>
              <button type="submit" class="btn btn-primary mt-3" :disabled="savingNotification">
                <i class="fas fa-save"></i> {{ savingNotification ? 'Đang lưu...' : 'Lưu thay đổi' }}
              </button>
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
                <input type="password" class="form-control" v-model="password.current" :disabled="savingPassword">
              </div>
              <div class="mb-3">
                <label class="form-label">Mật khẩu mới</label>
                <input type="password" class="form-control" v-model="password.new" :disabled="savingPassword">
              </div>
              <div class="mb-3">
                <label class="form-label">Xác nhận mật khẩu mới</label>
                <input type="password" class="form-control" v-model="password.confirm" :disabled="savingPassword">
              </div>
              <button type="submit" class="btn btn-primary" :disabled="savingPassword">
                <i class="fas fa-check"></i> {{ savingPassword ? 'Đang đổi...' : 'Đổi mật khẩu' }}
              </button>
            </form>

            <hr>

            <h6 class="mb-3"><i class="fas fa-lock"></i> Xác thực hai yếu tố (2FA)</h6>
            <div class="mb-3">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" v-model="security.twoFAEnabled" :disabled="savingSecurity">
                <label class="form-check-label">Bật xác thực hai yếu tố</label>
              </div>
              <small class="text-muted">Tăng cường bảo mật tài khoản với mã xác thực</small>
              <div v-if="security.twoFAEnabled" class="mt-3">
                <label class="form-label">Mã bảo mật 6 số</label>
                <div class="two-factor-entry" @click="$refs.securityTwoFactorInput?.focus()">
                  <span
                    v-for="index in 6"
                    :key="index"
                    class="two-factor-dot"
                    :class="{ filled: security.twoFactorPin.length >= index }"
                  ></span>
                <input
                  ref="securityTwoFactorInput"
                  type="password"
                  inputmode="numeric"
                  maxlength="6"
                  class="two-factor-hidden"
                  v-model.trim="security.twoFactorPin"
                  :disabled="savingSecurity"
                  placeholder="Nhập 6 số để đăng nhập lần sau"
                  @input="updateSecurityTwoFactorPin"
                >
                </div>
                <small class="text-muted">Bạn sẽ cần nhập mã này sau khi nhập đúng email và mật khẩu.</small>
              </div>
              <button type="button" class="btn btn-primary mt-3" :disabled="savingSecurity" @click="saveSecurity">
                <i class="fas fa-save"></i> {{ savingSecurity ? 'Đang lưu...' : 'Lưu bảo mật' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { uploadAvatar } from "@/services/authService";
import { notify } from "@/services/notificationService";
import { applyAppSettings } from "@/services/appSettingsService";
import {
  getSettings,
  updatePassword,
  updateProfile,
  updateSettings,
} from "@/services/settingsService";

export default {
  name: "Settings",
  data() {
    return {
      activeTab: "profile",
      currentUser: this.getStoredUser(),
      loading: true,
      avatarUploading: false,
      avatarError: "",
      savingProfile: false,
      savingGeneral: false,
      savingNotification: false,
      savingPassword: false,
      savingSecurity: false,
      profile: {
        fullName: "",
        email: "",
        position: "",
        department: "",
        role: "",
      },
      settings: {
        language: "vi",
        autoSave: true,
        darkMode: false,
        timezone: "UTC+7",
      },
      notifications: {
        emailEnabled: true,
        inAppEnabled: true,
        newDocument: true,
        documentUpdated: true,
        approvalNeeded: true,
      },
      password: {
        current: "",
        new: "",
        confirm: "",
      },
      security: {
        twoFAEnabled: false,
        twoFactorPin: "",
      },
    };
  },
  mounted() {
    this.loadSettings();
  },
  methods: {
    getStoredUser() {
      try {
        return JSON.parse(localStorage.getItem("user"));
      } catch {
        return null;
      }
    },
    roleLabel(role) {
      const labels = {
        admin: "Admin - Quản trị viên",
        editor: "Editor - Biên tập viên",
        viewer: "Viewer - Người xem",
      };
      return labels[role] || role || "";
    },
    applyUser(user) {
      this.currentUser = user;
      this.profile = {
        fullName: user.full_name || "",
        email: user.email || "",
        position: user.position || this.roleLabel(user.role),
        department: user.department?.name || "Chưa có phòng ban",
        role: this.roleLabel(user.role),
      };
      localStorage.setItem("user", JSON.stringify(user));
      window.dispatchEvent(new Event("user-updated"));
    },
    applySettings(settings) {
      const appSettings = applyAppSettings(settings);
      this.settings = {
        language: appSettings.language || "vi",
        autoSave: Boolean(appSettings.auto_save),
        darkMode: Boolean(appSettings.dark_mode),
        timezone: appSettings.timezone || "UTC+7",
      };
      this.notifications = {
        emailEnabled: Boolean(appSettings.email_enabled),
        inAppEnabled: Boolean(appSettings.in_app_enabled),
        newDocument: Boolean(appSettings.notify_upload),
        documentUpdated: Boolean(appSettings.notify_edit),
        approvalNeeded: Boolean(appSettings.notify_approve),
      };
      this.security.twoFAEnabled = Boolean(appSettings.two_factor_enabled);
      this.security.twoFactorPin = "";
    },
    async loadSettings() {
      this.loading = true;
      try {
        const data = await getSettings();
        this.applyUser(data.user);
        this.applySettings(data.settings);
      } catch (error) {
        notify({
          title: "Không thể tải cài đặt",
          message: error.response?.data?.message || "Vui lòng thử lại sau.",
          type: "danger",
        });
      } finally {
        this.loading = false;
      }
    },
    async changeAvatar(event) {
      const file = event.target.files[0];
      if (!file) return;

      this.avatarUploading = true;
      this.avatarError = "";
      try {
        const user = await uploadAvatar(file);
        this.applyUser({
          ...this.currentUser,
          ...user,
          department: this.currentUser?.department,
        });
        notify({ title: "Đã cập nhật ảnh", message: "Ảnh đại diện đã được thay đổi." });
      } catch (error) {
        this.avatarError = error.response?.data?.message || "Không thể tải ảnh đại diện.";
      } finally {
        this.avatarUploading = false;
        event.target.value = "";
      }
    },
    async saveProfile() {
      this.savingProfile = true;
      try {
        const user = await updateProfile({
          full_name: this.profile.fullName,
          position: this.profile.position,
        });
        this.applyUser(user);
        notify({ title: "Đã lưu hồ sơ", message: "Thông tin hồ sơ đã được cập nhật." });
      } catch (error) {
        notify({
          title: "Không thể lưu hồ sơ",
          message: error.response?.data?.message || "Vui lòng kiểm tra lại thông tin.",
          type: "danger",
        });
      } finally {
        this.savingProfile = false;
      }
    },
    settingsPayload(extra = {}) {
      return {
        language: this.settings.language,
        auto_save: this.settings.autoSave,
        dark_mode: this.settings.darkMode,
        timezone: this.settings.timezone,
        email_enabled: this.notifications.emailEnabled,
        in_app_enabled: this.notifications.inAppEnabled,
        notify_upload: this.notifications.newDocument,
        notify_edit: this.notifications.documentUpdated,
        notify_approve: this.notifications.approvalNeeded,
        notify_system: this.notifications.inAppEnabled,
        two_factor_enabled: this.security.twoFAEnabled,
        two_factor_pin: this.security.twoFactorPin,
        ...extra,
      };
    },
    async saveGeneral() {
      this.savingGeneral = true;
      try {
        const settings = await updateSettings(this.settingsPayload());
        this.applySettings(settings);
        notify({ title: "Đã lưu cài đặt", message: "Cài đặt chung đã được cập nhật." });
      } catch (error) {
        notify({
          title: "Không thể lưu cài đặt",
          message: error.response?.data?.message || "Vui lòng thử lại sau.",
          type: "danger",
        });
      } finally {
        this.savingGeneral = false;
      }
    },
    async saveNotification() {
      this.savingNotification = true;
      try {
        const settings = await updateSettings(this.settingsPayload());
        this.applySettings(settings);
        notify({ title: "Đã lưu thông báo", message: "Cài đặt thông báo đã được cập nhật." });
      } catch (error) {
        notify({
          title: "Không thể lưu thông báo",
          message: error.response?.data?.message || "Vui lòng thử lại sau.",
          type: "danger",
        });
      } finally {
        this.savingNotification = false;
      }
    },
    updateSecurityTwoFactorPin(event) {
      this.security.twoFactorPin = event.target.value.replace(/\D/g, "").slice(0, 6);
      event.target.value = this.security.twoFactorPin;
    },
    async saveSecurity() {
      if (this.security.twoFAEnabled && this.security.twoFactorPin && !/^\d{6}$/.test(this.security.twoFactorPin)) {
        notify({
          title: "Mã 2FA không hợp lệ",
          message: "Mã bảo mật phải gồm đúng 6 chữ số.",
          type: "danger",
        });
        return;
      }

      this.savingSecurity = true;
      try {
        const settings = await updateSettings(this.settingsPayload());
        this.applySettings(settings);
        notify({
          title: "Đã lưu bảo mật",
          message: this.security.twoFAEnabled
            ? "Xác thực hai yếu tố đã được bật cho tài khoản."
            : "Xác thực hai yếu tố đã được tắt.",
        });
      } catch (error) {
        this.security.twoFAEnabled = !this.security.twoFAEnabled;
        notify({
          title: "Không thể lưu bảo mật",
          message: error.response?.data?.message || "Vui lòng thử lại sau.",
          type: "danger",
        });
      } finally {
        this.savingSecurity = false;
      }
    },
    async changePassword() {
      if (this.password.new !== this.password.confirm) {
        notify({
          title: "Mật khẩu không khớp",
          message: "Vui lòng kiểm tra lại mật khẩu xác nhận.",
          type: "danger",
        });
        return;
      }

      this.savingPassword = true;
      try {
        await updatePassword({
          current_password: this.password.current,
          password: this.password.new,
          password_confirmation: this.password.confirm,
        });
        notify({ title: "Đã đổi mật khẩu", message: "Mật khẩu của bạn đã được cập nhật." });
        this.password = { current: "", new: "", confirm: "" };
      } catch (error) {
        notify({
          title: "Không thể đổi mật khẩu",
          message: error.response?.data?.message || "Vui lòng kiểm tra lại mật khẩu.",
          type: "danger",
        });
      } finally {
        this.savingPassword = false;
      }
    },
  },
};
</script>

<style scoped>
.list-group-item.active {
  background-color: #171717;
  border-color: #171717;
}

.card {
  border-radius: 8px;
  box-shadow: none;
}

.profile-avatar {
  display: flex;
  width: 60px;
  height: 60px;
  flex: 0 0 60px;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border-radius: 50%;
  background: #171717;
  color: #fff;
  font-size: 1.25rem;
}

.profile-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.two-factor-entry {
  position: relative;
  display: inline-flex;
  gap: 10px;
  padding: 8px 0;
  cursor: text;
}

.two-factor-dot {
  width: 22px;
  height: 22px;
  border: 2px solid #171717;
  border-radius: 50%;
  background: #ffffff;
  transition: background-color 0.15s ease, transform 0.15s ease;
}

.two-factor-dot.filled {
  background: #171717;
  transform: scale(0.94);
}

.two-factor-hidden {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  border: 0;
  outline: 0;
  opacity: 0;
  cursor: text;
}
</style>
