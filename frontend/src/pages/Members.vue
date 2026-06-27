<template>
  <div class="container-fluid py-4">
    <div class="members-header">
      <div>
        <h1><i class="fas fa-users"></i> Thành viên</h1>
        <p>Quản lý tài khoản và phân quyền người dùng trong hệ thống.</p>
      </div>

      <button class="btn btn-dark" type="button" @click="openCreateModal">
        <i class="fas fa-plus me-2"></i>Thêm thành viên
      </button>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="permission-panel">
      <div class="permission-heading">
        <i class="fas fa-lock"></i>
        <span>Ma trận phân quyền</span>
      </div>

      <div class="permission-grid">
        <div v-for="permission in permissions" :key="permission.name" class="permission-row">
          <strong>{{ permission.name }}</strong>
          <span :class="{ allowed: permission.viewer }">Viewer</span>
          <span :class="{ allowed: permission.editor }">Editor</span>
          <span :class="{ allowed: permission.admin }">Admin</span>
        </div>
      </div>
    </div>

    <div class="member-toolbar">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input v-model.trim="searchQuery" type="search" placeholder="Tìm theo tên hoặc email">
      </div>

      <select v-model="selectedDepartment">
        <option value="">Tất cả phòng ban</option>
        <option value="none">Chưa có phòng ban</option>
        <option v-for="department in departments" :key="department.id" :value="department.id">
          {{ department.name }}
        </option>
      </select>

      <select v-model="sortBy">
        <option value="name-asc">Tên A-Z</option>
        <option value="name-desc">Tên Z-A</option>
        <option value="newest">Mới tham gia</option>
        <option value="oldest">Cũ nhất</option>
        <option value="documents-desc">Nhiều tài liệu nhất</option>
      </select>
    </div>

    <Loading v-if="loading" type="cards" :count="6" />
    <p v-else-if="!filteredMembers.length" class="text-muted">Không tìm thấy thành viên phù hợp.</p>

    <div v-else class="row g-4">
      <div v-for="member in paginatedMembers" :key="member.id" class="col-md-6 col-xl-4">
        <div
          class="card member-card h-100"
          role="button"
          tabindex="0"
          @click="viewMember(member.id)"
          @keydown.enter="viewMember(member.id)"
          @keydown.space.prevent="viewMember(member.id)"
        >
          <div class="card-body">
            <div class="member-top">
              <div class="member-avatar">
                <img v-if="member.avatar" :src="member.avatar" alt="Avatar">
                <span v-else>{{ initials(member.full_name) }}</span>
              </div>

              <span class="role-badge" :class="`role-${member.role}`">
                {{ roleLabel(member.role) }}
              </span>
            </div>

            <h5 class="card-title">{{ member.full_name }}</h5>
            <p class="member-email">{{ member.email }}</p>

            <div class="member-meta">
              <span><i class="fas fa-building me-1"></i>{{ member.department?.name || "Chưa có phòng ban" }}</span>
              <span><i class="fas fa-file-alt me-1"></i>{{ member.documents_count || 0 }} tài liệu</span>
              <span><i class="fas fa-calendar me-1"></i>Tham gia: {{ formatDate(member.created_at) }}</span>
            </div>
          </div>

          <div class="card-footer member-card-actions">
            <button
              v-if="member.id !== currentUserId"
              class="btn btn-outline-danger btn-sm"
              type="button"
              @click.stop="removeMember(member)"
            >
              <i class="fas fa-trash me-1"></i>Xóa
            </button>
          </div>
        </div>
      </div>
    </div>

    <PaginationControls
      v-if="!loading && filteredMembers.length"
      :page="currentPage"
      :per-page="itemsPerPage"
      :total="filteredMembers.length"
      @update:page="currentPage = $event"
    />

    <div v-if="showCreateModal" class="modal-backdrop-panel" @click.self="closeCreateModal">
      <form class="member-modal" @submit.prevent="createNewMember">
        <div class="modal-title-row">
          <div>
            <h5>Thêm thành viên mới</h5>
            <p>Tạo tài khoản và phân quyền truy cập ban đầu.</p>
          </div>
          <button class="icon-button" type="button" aria-label="Đóng" @click="closeCreateModal">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <label>
          Họ tên
          <input v-model.trim="form.full_name" type="text" required placeholder="Nhập họ tên">
        </label>

        <label>
          Email
          <input v-model.trim="form.email" type="email" required placeholder="name@company.com">
        </label>

        <label>
          Mật khẩu
          <input v-model="form.password" type="password" required minlength="6" placeholder="Tối thiểu 6 ký tự">
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

        <div class="modal-actions">
          <button class="btn btn-outline-secondary" type="button" @click="closeCreateModal">Hủy</button>
          <button class="btn btn-dark" type="submit" :disabled="saving">
            {{ saving ? "Đang thêm..." : "Thêm thành viên" }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import PaginationControls from "@/components/common/PaginationControls.vue";
import Loading from "@/components/common/Loading.vue";
import { createMember, deleteMember, getMembers } from "@/services/memberService";
import { confirmDialog, notify } from "@/services/notificationService";

const emptyMemberForm = () => ({
  full_name: "",
  email: "",
  password: "",
  department_id: "",
  role: "viewer",
});

export default {
  name: "Members",
  components: { PaginationControls, Loading },
  data() {
    return {
      members: [],
      departments: [],
      loading: false,
      saving: false,
      error: "",
      searchQuery: "",
      selectedDepartment: "",
      sortBy: "name-asc",
      currentPage: 1,
      itemsPerPage: 15,
      showCreateModal: false,
      form: emptyMemberForm(),
      permissions: [
        { name: "Xem tài liệu", viewer: true, editor: true, admin: true },
        { name: "Tạo tài liệu", viewer: false, editor: true, admin: true },
        { name: "Chỉnh sửa tài liệu", viewer: false, editor: true, admin: true },
        { name: "Xóa tài liệu", viewer: false, editor: false, admin: true },
        { name: "Phê duyệt tài liệu", viewer: false, editor: false, admin: true },
        { name: "Quản lý thư mục", viewer: false, editor: true, admin: true },
        { name: "Quản lý thành viên", viewer: false, editor: false, admin: true },
      ],
    };
  },
  computed: {
    currentUserId() {
      try {
        return JSON.parse(localStorage.getItem("user"))?.id || "";
      } catch {
        return "";
      }
    },
    filteredMembers() {
      const query = this.searchQuery.toLowerCase();

      return [...this.members]
        .filter((member) => {
          const matchesSearch =
            member.full_name?.toLowerCase().includes(query) ||
            member.email?.toLowerCase().includes(query);
          const matchesDepartment =
            !this.selectedDepartment ||
            (this.selectedDepartment === "none" && !member.department_id) ||
            member.department_id === this.selectedDepartment;

          return matchesSearch && matchesDepartment;
        })
        .sort((a, b) => {
          if (this.sortBy === "name-desc") return b.full_name.localeCompare(a.full_name);
          if (this.sortBy === "newest") return new Date(b.created_at) - new Date(a.created_at);
          if (this.sortBy === "oldest") return new Date(a.created_at) - new Date(b.created_at);
          if (this.sortBy === "documents-desc") return (b.documents_count || 0) - (a.documents_count || 0);
          return a.full_name.localeCompare(b.full_name);
        });
    },
    paginatedMembers() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      return this.filteredMembers.slice(start, start + this.itemsPerPage);
    },
  },
  watch: {
    searchQuery() {
      this.currentPage = 1;
    },
    selectedDepartment() {
      this.currentPage = 1;
    },
    sortBy() {
      this.currentPage = 1;
    },
  },
  async mounted() {
    await this.loadMembers();
  },
  methods: {
    async loadMembers() {
      this.loading = true;
      this.error = "";

      try {
        const data = await getMembers();
        this.members = data.users || [];
        this.departments = data.departments || [];
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể tải danh sách thành viên.";
      } finally {
        this.loading = false;
      }
    },
    openCreateModal() {
      this.form = emptyMemberForm();
      this.showCreateModal = true;
    },
    closeCreateModal() {
      if (!this.saving) this.showCreateModal = false;
    },
    async createNewMember() {
      this.saving = true;

      try {
        const created = await createMember({
          ...this.form,
          department_id: this.form.department_id || null,
        });
        this.members = [...this.members, created];
        this.showCreateModal = false;
        notify({
          title: "Đã thêm thành viên",
          message: `${created.full_name} đã có tài khoản trong hệ thống.`,
        });
      } catch (error) {
        notify({
          title: "Không thể thêm thành viên",
          message: error.response?.data?.message || "Vui lòng kiểm tra lại thông tin.",
          type: "error",
        });
      } finally {
        this.saving = false;
      }
    },
    viewMember(id) {
      this.$router.push(`/members/${id}`);
    },
    async removeMember(member) {
      const confirmed = await confirmDialog({
        title: "Xóa thành viên",
        message: `Bạn chắc chắn muốn xóa ${member.full_name}? Hành động này không thể hoàn tác.`,
        confirmText: "Xóa thành viên",
        tone: "danger",
      });

      if (!confirmed) return;

      try {
        await deleteMember(member.id);
        this.members = this.members.filter((item) => item.id !== member.id);
        notify({
          title: "Đã xóa thành viên",
          message: `${member.full_name} đã được xóa khỏi hệ thống.`,
        });
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
    formatDate(date) {
      if (!date) return "Chưa có";
      return new Date(date).toLocaleDateString("vi-VN");
    },
  },
};
</script>

<style scoped>
.members-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 22px;
}

.members-header h1 {
  margin: 0;
  font-size: 1.8rem;
  font-weight: 700;
}

.members-header p {
  margin: 6px 0 0;
  color: #707070;
}

.permission-panel {
  margin-bottom: 24px;
  padding: 16px;
  border: 1px solid #dededb;
  border-radius: 8px;
  background: #fff;
}

.permission-heading {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  font-weight: 700;
}

.permission-grid {
  display: grid;
  gap: 8px;
}

.permission-row {
  display: grid;
  grid-template-columns: minmax(180px, 1fr) repeat(3, 90px);
  align-items: center;
  gap: 8px;
  padding: 8px 0;
  border-top: 1px solid #f1f1ef;
}

.permission-row span {
  display: inline-flex;
  min-height: 28px;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: #f1f1ef;
  color: #9a9a93;
  font-size: 0.78rem;
  font-weight: 700;
}

.permission-row span.allowed {
  background: #dcfce7;
  color: #15803d;
}

.member-toolbar {
  display: grid;
  grid-template-columns: minmax(240px, 1fr) minmax(190px, 240px) minmax(190px, 240px);
  gap: 12px;
  margin-bottom: 18px;
}

.search-box {
  display: flex;
  min-height: 40px;
  align-items: center;
  gap: 8px;
  padding: 0 12px;
  border: 1px solid #dededb;
  border-radius: 6px;
  background: #fff;
  color: #707070;
}

.search-box input,
.member-toolbar select {
  width: 100%;
  min-height: 40px;
  border: 1px solid #dededb;
  border-radius: 6px;
  padding: 8px 10px;
  background: #fff;
  color: #171717;
}

.search-box input {
  min-height: auto;
  border: 0;
  padding: 0;
  outline: 0;
}

.member-card {
  border: 1px solid #dededb;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.25s ease;
}

.member-card:hover,
.member-card:focus {
  transform: translateY(-4px);
  border-color: #171717;
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.08);
  outline: 0;
}

.member-top {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
}

.member-avatar {
  display: grid;
  width: 48px;
  height: 48px;
  place-items: center;
  overflow: hidden;
  border-radius: 50%;
  background: #171717;
  color: #fff;
  font-weight: 800;
}

.member-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.role-badge {
  height: fit-content;
  padding: 5px 10px;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 700;
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

.member-email {
  margin-bottom: 16px;
  color: #707070;
}

.member-meta {
  display: grid;
  gap: 7px;
  color: #707070;
  font-size: 0.82rem;
}

.member-card-actions {
  display: flex;
  min-height: 64px;
  align-items: center;
  justify-content: flex-end;
  border-top: 1px solid #dededb;
  background: #fff;
}

.modal-backdrop-panel {
  position: fixed;
  inset: 0;
  z-index: 1050;
  display: grid;
  place-items: center;
  padding: 20px;
  background: rgba(23, 23, 23, 0.38);
}

.member-modal {
  width: min(520px, 100%);
  padding: 22px;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.18);
}

.modal-title-row {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  margin-bottom: 18px;
}

.modal-title-row h5 {
  margin: 0;
  font-weight: 700;
}

.modal-title-row p {
  margin: 4px 0 0;
  color: #707070;
  font-size: 0.9rem;
}

.member-modal label {
  display: grid;
  gap: 6px;
  margin-bottom: 14px;
  color: #292929;
  font-weight: 700;
}

.member-modal input,
.member-modal select {
  width: 100%;
  min-height: 40px;
  border: 1px solid #dededb;
  border-radius: 6px;
  padding: 8px 10px;
  font-weight: 400;
}

.member-modal input:focus,
.member-modal select:focus,
.member-toolbar select:focus {
  border-color: #171717;
  outline: 0;
}

.icon-button {
  display: grid;
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  place-items: center;
  border: 0;
  border-radius: 50%;
  background: #f1f1ef;
  color: #171717;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 18px;
}

@media (max-width: 900px) {
  .member-toolbar {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 700px) {
  .members-header {
    display: grid;
  }

  .permission-row {
    grid-template-columns: 1fr;
  }
}
</style>
