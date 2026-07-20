<template>
  <div class="container-fluid py-4">
    <div class="departments-header">
      <div>
        <h1><i class="fas fa-building"></i> Phòng ban</h1>
        <p>Quản lý tên, mô tả và cơ cấu phòng ban của thành viên.</p>
      </div>

      <button class="btn btn-dark" type="button" @click="openCreateModal">
        <i class="fas fa-plus me-2"></i>Thêm phòng ban
      </button>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="department-toolbar">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input v-model.trim="searchQuery" type="search" placeholder="Tìm theo tên hoặc mô tả">
      </div>

      <select v-model="sortBy">
        <option value="name-asc">Tên A-Z</option>
        <option value="name-desc">Tên Z-A</option>
        <option value="members-desc">Nhiều thành viên nhất</option>
        <option value="newest">Mới tạo</option>
      </select>
    </div>

    <Loading v-if="loading" type="cards" :count="6" />
    <p v-else-if="!filteredDepartments.length" class="text-muted">Không tìm thấy phòng ban phù hợp.</p>

    <div v-else class="row g-4">
      <div v-for="department in filteredDepartments" :key="department.id" class="col-md-6 col-xl-4">
        <div class="card department-card h-100">
          <div class="card-body">
            <div class="department-card-top">
              <div class="department-icon">
                <i class="fas fa-building"></i>
              </div>
              <span>{{ department.users_count || 0 }} thành viên</span>
            </div>

            <h5>{{ department.name }}</h5>
            <p>{{ department.description || "Chưa có mô tả." }}</p>
          </div>

          <div class="card-footer">
            <button class="btn btn-outline-dark btn-sm" type="button" @click="openEditModal(department)">
              <i class="fas fa-pen me-1"></i>Sửa
            </button>
            <button class="btn btn-outline-danger btn-sm" type="button" @click="removeDepartment(department)">
              <i class="fas fa-trash me-1"></i>Xóa
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showModal" class="modal-backdrop-panel" @click.self="closeModal">
      <form class="department-modal" @submit.prevent="saveDepartment">
        <div class="modal-title-row">
          <div>
            <h5>{{ editingDepartment ? "Sửa phòng ban" : "Thêm phòng ban" }}</h5>
            <p>{{ editingDepartment ? "Cập nhật tên và mô tả phòng ban." : "Tạo phòng ban mới để gán cho thành viên." }}</p>
          </div>
          <button class="icon-button" type="button" aria-label="Đóng" @click="closeModal">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <label>
          Tên phòng ban
          <input v-model.trim="form.name" type="text" required placeholder="Ví dụ: Nhân sự">
        </label>

        <label>
          Mô tả
          <textarea v-model.trim="form.description" rows="4" placeholder="Mô tả ngắn về phòng ban"></textarea>
        </label>

        <div class="modal-actions">
          <button class="btn btn-outline-secondary" type="button" @click="closeModal">Hủy</button>
          <button class="btn btn-dark" type="submit" :disabled="saving">
            {{ saving ? "Đang lưu..." : "Lưu phòng ban" }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import Loading from "@/components/common/Loading.vue";
import {
  createDepartment,
  deleteDepartment,
  getDepartments,
  updateDepartment,
} from "@/services/departmentService";
import { confirmDialog, notify } from "@/services/notificationService";
import realtimeRefresh from "@/mixins/realtimeRefresh";

const emptyForm = () => ({
  name: "",
  description: "",
});

export default {
  name: "Departments",
  mixins: [realtimeRefresh],
  realtimeScopes: ["department", "member"],
  components: { Loading },
  data() {
    return {
      departments: [],
      loading: false,
      saving: false,
      error: "",
      searchQuery: "",
      sortBy: "name-asc",
      showModal: false,
      editingDepartment: null,
      form: emptyForm(),
    };
  },
  computed: {
    filteredDepartments() {
      const query = this.searchQuery.toLowerCase();

      return [...this.departments]
        .filter((department) => {
          return (
            department.name?.toLowerCase().includes(query) ||
            department.description?.toLowerCase().includes(query)
          );
        })
        .sort((a, b) => {
          if (this.sortBy === "name-desc") return b.name.localeCompare(a.name);
          if (this.sortBy === "members-desc") return (b.users_count || 0) - (a.users_count || 0);
          if (this.sortBy === "newest") return new Date(b.created_at) - new Date(a.created_at);
          return a.name.localeCompare(b.name);
        });
    },
  },
  async mounted() {
    await this.loadDepartments();
  },
  methods: {
    refreshRealtimeData() {
      return this.loadDepartments();
    },
    async loadDepartments() {
      this.loading = true;
      this.error = "";

      try {
        this.departments = await getDepartments();
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể tải danh sách phòng ban.";
      } finally {
        this.loading = false;
      }
    },
    openCreateModal() {
      this.editingDepartment = null;
      this.form = emptyForm();
      this.showModal = true;
    },
    openEditModal(department) {
      this.editingDepartment = department;
      this.form = {
        name: department.name || "",
        description: department.description || "",
      };
      this.showModal = true;
    },
    closeModal() {
      if (!this.saving) this.showModal = false;
    },
    async saveDepartment() {
      this.saving = true;

      try {
        const payload = {
          name: this.form.name,
          description: this.form.description || null,
        };
        const saved = this.editingDepartment
          ? await updateDepartment(this.editingDepartment.id, payload)
          : await createDepartment(payload);

        if (this.editingDepartment) {
          this.departments = this.departments.map((department) =>
            department.id === saved.id ? saved : department
          );
        } else {
          this.departments = [...this.departments, saved];
        }

        this.showModal = false;
        notify({
          title: this.editingDepartment ? "Đã cập nhật phòng ban" : "Đã thêm phòng ban",
          message: `${saved.name} đã được lưu.`,
        });
      } catch (error) {
        notify({
          title: "Không thể lưu phòng ban",
          message: error.response?.data?.message || "Vui lòng kiểm tra lại thông tin.",
          type: "error",
        });
      } finally {
        this.saving = false;
      }
    },
    async removeDepartment(department) {
      const confirmed = await confirmDialog({
        title: "Xóa phòng ban",
        message: `Xóa ${department.name}? Thành viên thuộc phòng ban này sẽ chuyển về trạng thái chưa có phòng ban.`,
        confirmText: "Xóa phòng ban",
        tone: "danger",
      });

      if (!confirmed) return;

      try {
        await deleteDepartment(department.id);
        this.departments = this.departments.filter((item) => item.id !== department.id);
        notify({
          title: "Đã xóa phòng ban",
          message: `${department.name} đã được xóa khỏi hệ thống.`,
        });
      } catch (error) {
        notify({
          title: "Không thể xóa phòng ban",
          message: error.response?.data?.message || "Vui lòng thử lại sau.",
          type: "error",
        });
      }
    },
  },
};
</script>

<style scoped>
.departments-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 22px;
}

.departments-header h1 {
  margin: 0;
  font-size: 1.8rem;
  font-weight: 700;
}

.departments-header p {
  margin: 6px 0 0;
  color: #707070;
}

.department-toolbar {
  display: grid;
  grid-template-columns: minmax(240px, 1fr) minmax(190px, 240px);
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
.department-toolbar select {
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

.department-card {
  border: 1px solid #dededb;
  border-radius: 8px;
}

.department-card-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 18px;
}

.department-card-top span {
  padding: 5px 10px;
  border-radius: 999px;
  background: #f1f1ef;
  color: #707070;
  font-size: 0.76rem;
  font-weight: 800;
}

.department-icon {
  display: grid;
  width: 44px;
  height: 44px;
  place-items: center;
  border-radius: 8px;
  background: #171717;
  color: #fff;
}

.department-card h5 {
  margin-bottom: 8px;
  font-weight: 800;
}

.department-card p {
  margin: 0;
  color: #707070;
}

.card-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
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

.department-modal {
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

.department-modal label {
  display: grid;
  gap: 6px;
  margin-bottom: 14px;
  color: #292929;
  font-weight: 700;
}

.department-modal input,
.department-modal textarea {
  width: 100%;
  border: 1px solid #dededb;
  border-radius: 6px;
  padding: 8px 10px;
  font-weight: 400;
}

.department-modal input {
  min-height: 40px;
}

.department-modal input:focus,
.department-modal textarea:focus,
.department-toolbar select:focus {
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

@media (max-width: 760px) {
  .departments-header,
  .department-toolbar {
    grid-template-columns: 1fr;
    display: grid;
  }
}
</style>
