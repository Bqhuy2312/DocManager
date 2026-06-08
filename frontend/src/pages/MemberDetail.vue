<template>
  <div class="container-fluid py-4">
    <button class="back-button" type="button" @click="$router.push('/members')">
      <i class="fas fa-arrow-left me-2"></i>Quay lại thành viên
    </button>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <p v-if="loading" class="text-muted">Đang tải thông tin thành viên...</p>

    <div v-else-if="member" class="member-detail-layout">
      <section class="member-profile">
        <div class="profile-avatar">
          <img v-if="member.avatar" :src="member.avatar" alt="Avatar">
          <span v-else>{{ initials(member.full_name) }}</span>
        </div>

        <h1>{{ member.full_name }}</h1>
        <p>{{ member.email }}</p>

        <span class="role-badge" :class="`role-${member.role}`">
          {{ roleLabel(member.role) }}
        </span>

        <div class="profile-stats">
          <div>
            <strong>{{ member.department?.name || "Chưa có" }}</strong>
            <span>Phòng ban</span>
          </div>
          <div>
            <strong>{{ member.documents_count || 0 }}</strong>
            <span>Tài liệu</span>
          </div>
          <div>
            <strong>{{ formatDate(member.created_at) }}</strong>
            <span>Ngày tham gia</span>
          </div>
        </div>
      </section>

      <form class="edit-panel" @submit.prevent="saveMember">
        <div class="edit-heading">
          <div>
            <h2>Thông tin người dùng</h2>
            <p>Admin có thể cập nhật hồ sơ và phân quyền của thành viên.</p>
          </div>
        </div>

        <div class="form-grid">
          <label>
            Họ tên
            <input v-model.trim="form.full_name" type="text" required>
          </label>

          <label>
            Email
            <input v-model.trim="form.email" type="email" required>
          </label>

          <label>
            Phòng ban
            <select v-model="form.department_id">
              <option value="">Chưa chọn</option>
              <option v-for="department in departments" :key="department.id" :value="department.id">
                {{ department.name }}
              </option>
            </select>
          </label>

          <label>
            Vai trò
            <select v-model="form.role">
              <option value="viewer">Viewer - Người xem</option>
              <option value="editor">Editor - Biên tập viên</option>
              <option value="admin">Admin - Quản trị viên</option>
            </select>
          </label>

          <label class="full-row">
            Mật khẩu mới
            <input v-model="form.password" type="password" minlength="6" placeholder="Bỏ trống nếu không đổi">
          </label>
        </div>

        <div class="role-note">
          <strong>Phân quyền hiện tại:</strong>
          <span>{{ permissionDescription(form.role) }}</span>
        </div>

        <div class="actions">
          <button
            v-if="member.id !== currentUserId"
            class="btn btn-outline-danger me-auto"
            type="button"
            @click="removeMember"
          >
            <i class="fas fa-trash me-1"></i>Xóa thành viên
          </button>
          <button class="btn btn-outline-secondary" type="button" @click="$router.push('/members')">Hủy</button>
          <button class="btn btn-dark" type="submit" :disabled="saving">
            {{ saving ? "Đang lưu..." : "Lưu thay đổi" }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { deleteMember, getMember, getMembers, updateMember } from "@/services/memberService";
import { confirmDialog, notify } from "@/services/notificationService";

export default {
  name: "MemberDetail",
  data() {
    return {
      member: null,
      departments: [],
      form: {
        full_name: "",
        email: "",
        password: "",
        department_id: "",
        role: "viewer",
      },
      loading: false,
      saving: false,
      error: "",
    };
  },
  async mounted() {
    await this.loadData();
  },
  computed: {
    currentUserId() {
      try {
        return JSON.parse(localStorage.getItem("user"))?.id || "";
      } catch {
        return "";
      }
    },
  },
  methods: {
    async loadData() {
      this.loading = true;
      this.error = "";

      try {
        const [member, membersData] = await Promise.all([
          getMember(this.$route.params.id),
          getMembers(),
        ]);
        this.member = member;
        this.departments = membersData.departments || [];
        this.form = {
          full_name: member.full_name || "",
          email: member.email || "",
          password: "",
          department_id: member.department_id || "",
          role: member.role || "viewer",
        };
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể tải thông tin thành viên.";
      } finally {
        this.loading = false;
      }
    },
    async saveMember() {
      this.saving = true;

      try {
        const payload = {
          full_name: this.form.full_name,
          email: this.form.email,
          department_id: this.form.department_id || null,
          role: this.form.role,
        };

        if (this.form.password) {
          payload.password = this.form.password;
        }

        const updated = await updateMember(this.member.id, payload);
        this.member = updated;
        this.form.password = "";
        this.syncCurrentUser(updated);
        notify({
          title: "Đã cập nhật thành viên",
          message: "Thông tin và phân quyền đã được lưu.",
        });
      } catch (error) {
        notify({
          title: "Không thể lưu thay đổi",
          message: error.response?.data?.message || "Vui lòng kiểm tra lại thông tin.",
          type: "error",
        });
      } finally {
        this.saving = false;
      }
    },
    syncCurrentUser(updated) {
      const stored = localStorage.getItem("user");
      if (!stored) return;

      try {
        const current = JSON.parse(stored);
        if (current?.id === updated.id) {
          localStorage.setItem("user", JSON.stringify({ ...current, ...updated }));
          window.dispatchEvent(new Event("user-updated"));
        }
      } catch {
        // Ignore invalid local storage content.
      }
    },
    async removeMember() {
      const confirmed = await confirmDialog({
        title: "Xóa thành viên",
        message: `Bạn chắc chắn muốn xóa ${this.member.full_name}? Hành động này không thể hoàn tác.`,
        confirmText: "Xóa thành viên",
        tone: "danger",
      });

      if (!confirmed) return;

      try {
        await deleteMember(this.member.id);
        notify({
          title: "Đã xóa thành viên",
          message: `${this.member.full_name} đã được xóa khỏi hệ thống.`,
        });
        this.$router.push("/members");
      } catch (error) {
        notify({
          title: "Không thể xóa thành viên",
          message: error.response?.data?.message || "Vui lòng thử lại sau.",
          type: "error",
        });
      }
    },
    initials(name = "") {
      return name
        .split(" ")
        .filter(Boolean)
        .slice(-2)
        .map((part) => part[0])
        .join("")
        .toUpperCase() || "U";
    },
    roleLabel(role) {
      return {
        admin: "Admin",
        editor: "Editor",
        viewer: "Viewer",
      }[role] || role;
    },
    permissionDescription(role) {
      return {
        admin: "Toàn quyền quản lý tài liệu, thư mục, phê duyệt và thành viên.",
        editor: "Có thể tạo tài liệu và quản lý thư mục, không quản lý thành viên.",
        viewer: "Chỉ xem, tải xuống và đánh dấu tài liệu đã được phê duyệt.",
      }[role] || "";
    },
    formatDate(date) {
      if (!date) return "Chưa có";
      return new Date(date).toLocaleDateString("vi-VN");
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

.member-detail-layout {
  display: grid;
  grid-template-columns: minmax(260px, 340px) minmax(0, 1fr);
  gap: 24px;
}

.member-profile,
.edit-panel {
  border: 1px solid #dededb;
  border-radius: 8px;
  background: #fff;
}

.member-profile {
  align-self: start;
  padding: 24px;
  text-align: center;
}

.profile-avatar {
  display: grid;
  width: 86px;
  height: 86px;
  place-items: center;
  margin: 0 auto 16px;
  overflow: hidden;
  border-radius: 50%;
  background: #171717;
  color: #fff;
  font-size: 1.4rem;
  font-weight: 800;
}

.profile-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.member-profile h1 {
  margin: 0;
  font-size: 1.45rem;
  font-weight: 800;
}

.member-profile p {
  margin: 6px 0 14px;
  color: #707070;
}

.role-badge {
  display: inline-flex;
  padding: 5px 12px;
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 800;
}

.role-admin {
  background: #fee2e2;
  color: #b91c1c;
}

.role-editor {
  background: #fef3c7;
  color: #92400e;
}

.role-viewer {
  background: #e0f2fe;
  color: #0369a1;
}

.profile-stats {
  display: grid;
  gap: 12px;
  margin-top: 24px;
  text-align: left;
}

.profile-stats div {
  display: grid;
  gap: 2px;
  padding-top: 12px;
  border-top: 1px solid #f1f1ef;
}

.profile-stats span {
  color: #707070;
  font-size: 0.82rem;
}

.edit-panel {
  padding: 24px;
}

.edit-heading {
  margin-bottom: 20px;
}

.edit-heading h2 {
  margin: 0;
  font-size: 1.3rem;
  font-weight: 800;
}

.edit-heading p {
  margin: 5px 0 0;
  color: #707070;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.form-grid label {
  display: grid;
  gap: 6px;
  color: #292929;
  font-weight: 700;
}

.full-row {
  grid-column: 1 / -1;
}

.form-grid input,
.form-grid select {
  width: 100%;
  min-height: 40px;
  border: 1px solid #dededb;
  border-radius: 6px;
  padding: 8px 10px;
  font-weight: 400;
}

.form-grid input:focus,
.form-grid select:focus {
  border-color: #171717;
  outline: 0;
}

.role-note {
  display: grid;
  gap: 4px;
  margin-top: 18px;
  padding: 14px;
  border-radius: 8px;
  background: #f8f9fa;
  color: #555;
}

.role-note strong {
  color: #171717;
}

.actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 22px;
}

@media (max-width: 900px) {
  .member-detail-layout {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 600px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
