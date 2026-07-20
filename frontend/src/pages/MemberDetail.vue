<template>
  <div class="container-fluid py-4 member-detail-page">
    <header class="page-heading">
      <button class="back-button" type="button" @click="$router.push('/members')">
        <i class="fas fa-arrow-left me-2"></i>Quay lại thành viên
      </button>
      <div>
        <span class="page-eyebrow">Quản lý thành viên</span>
        <h1>Hồ sơ thành viên</h1>
        <p>Xem thông tin, cập nhật quyền hạn và quản lý tài liệu đã tải lên.</p>
      </div>
    </header>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <Loading v-if="profileLoading" type="detail" />

    <template v-else-if="member">
      <div class="member-detail-layout">
        <aside class="member-profile">
          <div class="profile-avatar">
            <img v-if="member.avatar" :src="member.avatar" alt="Avatar">
            <span v-else>{{ initials(member.full_name) }}</span>
          </div>

          <div class="profile-identity">
            <h2>{{ member.full_name }}</h2>
            <p>{{ member.email }}</p>
            <span class="role-badge" :class="`role-${member.role}`">
              {{ roleLabel(member.role) }}
            </span>
          </div>

          <div class="profile-stats">
            <div class="profile-stat">
              <span class="profile-stat-icon"><i class="fas fa-building"></i></span>
              <span>
                <small>Phòng ban</small>
                <strong>{{ member.department?.name || "Chưa có" }}</strong>
              </span>
            </div>
            <div class="profile-stat">
              <span class="profile-stat-icon"><i class="fas fa-file-alt"></i></span>
              <span>
                <small>Tài liệu đã tải lên</small>
                <strong>{{ member.documents_count || 0 }} tài liệu</strong>
              </span>
            </div>
            <div class="profile-stat">
              <span class="profile-stat-icon"><i class="fas fa-calendar-alt"></i></span>
              <span>
                <small>Ngày tham gia</small>
                <strong>{{ formatDate(member.created_at) }}</strong>
              </span>
            </div>
          </div>
        </aside>

        <form class="edit-panel" @submit.prevent="saveMember">
          <div class="panel-heading">
            <span class="panel-heading-icon"><i class="fas fa-user-pen"></i></span>
            <div>
              <h2>Thông tin người dùng</h2>
              <p>Cập nhật hồ sơ, phòng ban và quyền truy cập của thành viên.</p>
            </div>
          </div>

          <div class="edit-panel-content">
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
                <input
                  v-model="form.password"
                  type="password"
                  minlength="6"
                  placeholder="Bỏ trống nếu không đổi"
                >
              </label>
            </div>

            <div class="role-note">
              <span class="role-note-icon"><i class="fas fa-shield-alt"></i></span>
              <span>
                <strong>Phân quyền hiện tại</strong>
                <small>{{ permissionDescription(form.role) }}</small>
              </span>
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
                <i class="fas fa-save me-1"></i>
                {{ saving ? "Đang lưu..." : "Lưu thay đổi" }}
              </button>
            </div>
          </div>
        </form>
      </div>

      <section class="document-section">
        <div class="document-section-heading">
          <div>
            <span class="page-eyebrow">Nội dung của thành viên</span>
            <h2>Tài liệu đã tải lên</h2>
            <p>Theo dõi toàn bộ tài liệu do {{ member.full_name }} đăng tải.</p>
          </div>

          <div class="document-summary" aria-label="Thống kê trạng thái tài liệu">
            <span><strong>{{ documents.length }}</strong> Tổng số</span>
            <span><strong>{{ statusCounts.approved }}</strong> Đã duyệt</span>
            <span><strong>{{ statusCounts.pending }}</strong> Chờ duyệt</span>
            <span><strong>{{ statusCounts.rejected }}</strong> Từ chối</span>
          </div>
        </div>

        <div class="document-toolbar">
          <label class="document-search">
            <i class="fas fa-search"></i>
            <input
              v-model.trim="documentSearch"
              type="search"
              placeholder="Tìm theo tên, mô tả, tên file hoặc thẻ..."
            >
          </label>

          <select v-model="selectedStatus" aria-label="Lọc theo trạng thái">
            <option value="">Tất cả trạng thái</option>
            <option value="approved">Đã phê duyệt</option>
            <option value="pending">Chờ phê duyệt</option>
            <option value="rejected">Đã từ chối</option>
          </select>

          <select v-model="selectedCategory" aria-label="Lọc theo danh mục">
            <option value="">Tất cả danh mục</option>
            <option v-for="category in documentCategories" :key="category.value" :value="category.value">
              {{ category.label }}
            </option>
          </select>

          <select v-model="documentSort" aria-label="Sắp xếp tài liệu">
            <option value="recent">Cập nhật gần nhất</option>
            <option value="oldest">Cũ nhất</option>
            <option value="name">Tên A - Z</option>
            <option value="access">Truy cập nhiều nhất</option>
          </select>

          <button
            v-if="hasDocumentFilters"
            class="clear-filter-button"
            type="button"
            title="Xóa bộ lọc"
            aria-label="Xóa bộ lọc"
            @click="resetDocumentFilters"
          >
            <i class="fas fa-rotate-left"></i>
          </button>
        </div>

        <div class="document-results-bar">
          <span>{{ filteredDocuments.length }} tài liệu phù hợp</span>
          <span v-if="hasDocumentFilters">Đang áp dụng bộ lọc</span>
        </div>

        <div v-if="documentsError" class="alert alert-danger">{{ documentsError }}</div>
        <Loading v-if="documentsLoading" type="cards" :count="6" />

        <div v-else-if="!filteredDocuments.length" class="document-empty">
          <span><i class="fas fa-folder-open"></i></span>
          <h3>{{ documents.length ? "Không tìm thấy tài liệu phù hợp" : "Chưa có tài liệu được tải lên" }}</h3>
          <p>{{ documents.length ? "Hãy thử thay đổi từ khóa hoặc bộ lọc." : "Tài liệu của thành viên sẽ xuất hiện tại đây." }}</p>
          <button v-if="hasDocumentFilters" type="button" @click="resetDocumentFilters">Xóa bộ lọc</button>
        </div>

        <div v-else class="row g-4">
          <div v-for="document in paginatedDocuments" :key="document.id" class="col-md-6 col-xl-4">
            <DocumentCard
              :document="document"
              show-status
              @view="viewDocument"
              @toggle-favorite="toggleFavorite"
              @download="downloadDocument"
            />
          </div>
        </div>

        <PaginationControls
          v-if="!documentsLoading && filteredDocuments.length"
          :page="currentPage"
          :per-page="itemsPerPage"
          :total="filteredDocuments.length"
          :scroll-on-change="false"
          @update:page="currentPage = $event"
        />
      </section>
    </template>
  </div>
</template>

<script>
import DocumentCard from "@/components/common/DocumentCard.vue";
import Loading from "@/components/common/Loading.vue";
import PaginationControls from "@/components/common/PaginationControls.vue";
import {
  downloadDocumentFile,
  getDocuments,
  toggleFavoriteDocument,
} from "@/services/documentService";
import { deleteMember, getMember, getMembers, updateMember } from "@/services/memberService";
import { confirmDialog, notify } from "@/services/notificationService";
import realtimeRefresh from "@/mixins/realtimeRefresh";

export default {
  name: "MemberDetail",
  components: { DocumentCard, Loading, PaginationControls },
  mixins: [realtimeRefresh],
  realtimeScopes: ["member", "department", "document"],
  data() {
    return {
      member: null,
      departments: [],
      documents: [],
      form: {
        full_name: "",
        email: "",
        password: "",
        department_id: "",
        role: "viewer",
      },
      documentSearch: "",
      selectedStatus: "",
      selectedCategory: "",
      documentSort: "recent",
      currentPage: 1,
      itemsPerPage: 15,
      profileLoading: false,
      documentsLoading: false,
      saving: false,
      error: "",
      documentsError: "",
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
    documentCategories() {
      const categories = new Map();

      this.documents.forEach((document) => {
        const value = this.documentCategoryKey(document);
        if (!value || categories.has(value)) return;

        categories.set(value, [document.folder, document.category].filter(Boolean).join(" / "));
      });

      return [...categories.entries()]
        .map(([value, label]) => ({ value, label }))
        .sort((a, b) => a.label.localeCompare(b.label, "vi"));
    },
    statusCounts() {
      return this.documents.reduce(
        (counts, document) => {
          if (Object.prototype.hasOwnProperty.call(counts, document.status)) {
            counts[document.status] += 1;
          }
          return counts;
        },
        { approved: 0, pending: 0, rejected: 0 },
      );
    },
    filteredDocuments() {
      const query = this.documentSearch.toLocaleLowerCase("vi");

      return [...this.documents]
        .filter((document) => {
          const searchableText = [
            document.title,
            document.description,
            document.file_name,
            ...(document.tags || []),
          ]
            .filter(Boolean)
            .join(" ")
            .toLocaleLowerCase("vi");

          const matchesSearch = !query || searchableText.includes(query);
          const matchesStatus = !this.selectedStatus || document.status === this.selectedStatus;
          const matchesCategory =
            !this.selectedCategory || this.documentCategoryKey(document) === this.selectedCategory;

          return matchesSearch && matchesStatus && matchesCategory;
        })
        .sort((a, b) => {
          if (this.documentSort === "oldest") return this.dateValue(a.updated_at) - this.dateValue(b.updated_at);
          if (this.documentSort === "name") return a.title.localeCompare(b.title, "vi");
          if (this.documentSort === "access") return (b.access_count || 0) - (a.access_count || 0);
          return this.dateValue(b.updated_at) - this.dateValue(a.updated_at);
        });
    },
    paginatedDocuments() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      return this.filteredDocuments.slice(start, start + this.itemsPerPage);
    },
    hasDocumentFilters() {
      return Boolean(
        this.documentSearch ||
          this.selectedStatus ||
          this.selectedCategory ||
          this.documentSort !== "recent",
      );
    },
  },
  watch: {
    documentSearch() {
      this.currentPage = 1;
    },
    selectedStatus() {
      this.currentPage = 1;
    },
    selectedCategory() {
      this.currentPage = 1;
    },
    documentSort() {
      this.currentPage = 1;
    },
  },
  async mounted() {
    await Promise.all([this.loadMemberData(), this.loadDocuments()]);
  },
  methods: {
    refreshRealtimeData(payload = {}) {
      if (payload.scope === "document") return this.loadDocuments(false);
      if (payload.scope === "member" && payload.entity_id && payload.entity_id !== this.$route.params.id) {
        return Promise.resolve();
      }
      if (payload.scope === "member" || payload.scope === "department") return this.loadMemberData(false);
      return Promise.all([this.loadMemberData(false), this.loadDocuments(false)]);
    },
    async loadMemberData(showLoading = true) {
      if (showLoading) this.profileLoading = true;
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
        if (showLoading) this.profileLoading = false;
      }
    },
    async loadDocuments(showLoading = true) {
      if (showLoading) this.documentsLoading = true;
      this.documentsError = "";

      try {
        this.documents = await getDocuments({ created_by: this.$route.params.id });
        const totalPages = Math.max(1, Math.ceil(this.filteredDocuments.length / this.itemsPerPage));
        this.currentPage = Math.min(this.currentPage, totalPages);
      } catch (error) {
        this.documentsError = error.response?.data?.message || "Không thể tải tài liệu của thành viên.";
      } finally {
        if (showLoading) this.documentsLoading = false;
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

        if (this.form.password) payload.password = this.form.password;

        const updated = await updateMember(this.member.id, payload);
        this.member = updated;
        this.form.password = "";
        this.syncCurrentUser(updated);
        await this.loadDocuments(false);
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
    resetDocumentFilters() {
      this.documentSearch = "";
      this.selectedStatus = "";
      this.selectedCategory = "";
      this.documentSort = "recent";
      this.currentPage = 1;
    },
    viewDocument(id) {
      this.$router.push(`/documents/${id}`);
    },
    async downloadDocument(document) {
      if (document.status !== "approved") return;

      try {
        await downloadDocumentFile(document);
      } catch (error) {
        notify({
          title: "Không thể tải tài liệu",
          message: error.response?.data?.message || "Vui lòng thử lại sau.",
          type: "error",
        });
      }
    },
    async toggleFavorite(document) {
      try {
        const result = await toggleFavoriteDocument(document.id);
        document.is_favorite = result.is_favorite;
      } catch (error) {
        notify({
          title: "Không thể cập nhật đánh dấu",
          message: error.response?.data?.message || "Vui lòng thử lại sau.",
          type: "error",
        });
      }
    },
    documentCategoryKey(document) {
      if (!document.folder && !document.category) return "";
      return `${document.folder || ""}::${document.category || ""}`;
    },
    dateValue(value) {
      const date = new Date(value);
      return Number.isNaN(date.getTime()) ? 0 : date.getTime();
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
      return { admin: "Admin", editor: "Editor", viewer: "Viewer" }[role] || role;
    },
    permissionDescription(role) {
      return {
        admin: "Toàn quyền quản lý tài liệu, thư mục, phê duyệt và thành viên.",
        editor: "Có thể tải lên, cập nhật tài liệu của mình và quản lý thư mục.",
        viewer: "Có thể xem, tải xuống và đánh dấu tài liệu đã được phê duyệt.",
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
.member-detail-page {
  max-width: 1640px;
  margin: 0 auto;
}

.page-heading {
  display: block;
  margin-bottom: 22px;
}

.page-heading > div {
  min-width: 0;
}

.page-heading h1,
.document-section-heading h2 {
  margin: 2px 0 4px;
  color: #171717;
  font-size: 1.65rem;
  font-weight: 800;
}

.page-heading p,
.document-section-heading p,
.panel-heading p {
  margin: 0;
  color: #707070;
}

.page-eyebrow {
  color: #797973;
  font-size: 0.72rem;
  font-weight: 800;
  text-transform: uppercase;
}

.back-button {
  display: inline-flex;
  align-items: center;
  flex: 0 0 auto;
  margin-bottom: 20px;
  padding: 0;
  border: 0;
  background: transparent;
  color: #292929;
  font-weight: 700;
}

.back-button:hover {
  background: transparent;
  color: #171717;
}

.member-detail-layout {
  display: grid;
  grid-template-columns: minmax(260px, 310px) minmax(0, 1fr);
  gap: 22px;
  align-items: start;
}

.member-profile,
.edit-panel {
  overflow: hidden;
  border: 1px solid #dededb;
  border-radius: 8px;
  background: #fff;
}

.member-profile {
  padding: 24px 22px;
}

.profile-avatar {
  display: grid;
  width: 88px;
  height: 88px;
  place-items: center;
  margin: 0 auto 15px;
  overflow: hidden;
  border: 4px solid #fff;
  border-radius: 50%;
  background: #171717;
  color: #fff;
  box-shadow: 0 0 0 1px #d7d7d3, 0 8px 20px rgba(23, 23, 23, 0.12);
  font-size: 1.3rem;
  font-weight: 800;
}

.profile-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.profile-identity {
  text-align: center;
}

.profile-identity h2 {
  margin: 0;
  overflow-wrap: anywhere;
  font-size: 1.25rem;
  font-weight: 800;
}

.profile-identity p {
  margin: 5px 0 12px;
  overflow-wrap: anywhere;
  color: #707070;
  font-size: 0.88rem;
}

.role-badge {
  display: inline-flex;
  padding: 5px 11px;
  border-radius: 999px;
  font-size: 0.76rem;
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
  gap: 2px;
  margin-top: 22px;
  padding-top: 12px;
  border-top: 1px solid #eeeeec;
}

.profile-stat {
  display: grid;
  grid-template-columns: 34px minmax(0, 1fr);
  align-items: center;
  gap: 10px;
  padding: 9px 0;
}

.profile-stat-icon,
.panel-heading-icon,
.role-note-icon {
  display: grid;
  place-items: center;
  background: #f1f1ef;
  color: #292929;
}

.profile-stat-icon {
  width: 34px;
  height: 34px;
  border-radius: 6px;
  font-size: 0.82rem;
}

.profile-stat > span:last-child {
  display: grid;
  min-width: 0;
  gap: 1px;
}

.profile-stat small {
  color: #797973;
  font-size: 0.72rem;
}

.profile-stat strong {
  overflow: hidden;
  font-size: 0.84rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.panel-heading {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 18px 20px;
  border-bottom: 1px solid #eeeeec;
}

.panel-heading-icon {
  width: 38px;
  height: 38px;
  flex: 0 0 38px;
  border-radius: 7px;
}

.panel-heading h2 {
  margin: 0 0 3px;
  font-size: 1.08rem;
  font-weight: 800;
}

.panel-heading p {
  font-size: 0.84rem;
}

.edit-panel-content {
  padding: 20px;
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
  font-size: 0.84rem;
  font-weight: 700;
}

.full-row {
  grid-column: 1 / -1;
}

.form-grid input,
.form-grid select,
.document-toolbar input,
.document-toolbar select {
  width: 100%;
  min-height: 42px;
  border: 1px solid #d7d7d3;
  border-radius: 6px;
  background: #fff;
  color: #292929;
}

.form-grid input,
.form-grid select {
  padding: 8px 11px;
  font-weight: 400;
}

.form-grid input:focus,
.form-grid select:focus,
.document-toolbar input:focus,
.document-toolbar select:focus {
  border-color: #171717;
  outline: 0;
  box-shadow: 0 0 0 3px rgba(23, 23, 23, 0.08);
}

.role-note {
  display: grid;
  grid-template-columns: 36px minmax(0, 1fr);
  align-items: center;
  gap: 11px;
  margin-top: 18px;
  padding: 13px;
  border: 1px solid #e8e8e5;
  border-radius: 7px;
  background: #f8f8f6;
}

.role-note-icon {
  width: 36px;
  height: 36px;
  border-radius: 6px;
}

.role-note > span:last-child {
  display: grid;
  gap: 3px;
}

.role-note strong {
  font-size: 0.84rem;
}

.role-note small {
  color: #666;
  font-size: 0.8rem;
}

.actions {
  display: flex;
  justify-content: flex-end;
  gap: 9px;
  margin-top: 20px;
  padding-top: 18px;
  border-top: 1px solid #eeeeec;
}

.document-section {
  margin-top: 30px;
  padding-top: 26px;
  border-top: 1px solid #dededb;
}

.document-section-heading {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 16px;
}

.document-section-heading h2 {
  font-size: 1.45rem;
}

.document-summary {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 6px;
}

.document-summary span {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 9px;
  border: 1px solid #dededb;
  border-radius: 6px;
  background: #fff;
  color: #707070;
  font-size: 0.75rem;
}

.document-summary strong {
  color: #171717;
}

.document-toolbar {
  display: grid;
  grid-template-columns: minmax(260px, 1.7fr) repeat(3, minmax(150px, 0.65fr)) auto;
  gap: 10px;
  padding: 12px;
  border: 1px solid #dededb;
  border-radius: 8px;
  background: #fff;
}

.document-search {
  position: relative;
  display: flex;
  align-items: center;
}

.document-search i {
  position: absolute;
  left: 12px;
  color: #797973;
  pointer-events: none;
}

.document-toolbar input {
  padding: 8px 11px 8px 36px;
}

.document-toolbar select {
  padding: 8px 32px 8px 10px;
}

.clear-filter-button {
  display: grid;
  width: 42px;
  height: 42px;
  place-items: center;
  border: 1px solid #d7d7d3;
  border-radius: 6px;
  background: #fff;
  color: #292929;
}

.clear-filter-button:hover {
  border-color: #171717;
  background: #171717;
  color: #fff;
}

.document-results-bar {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding: 11px 2px 13px;
  color: #707070;
  font-size: 0.78rem;
}

.document-empty {
  display: grid;
  min-height: 230px;
  place-items: center;
  align-content: center;
  padding: 28px;
  border: 1px dashed #cfcfca;
  border-radius: 8px;
  color: #707070;
  text-align: center;
}

.document-empty > span {
  display: grid;
  width: 46px;
  height: 46px;
  place-items: center;
  margin-bottom: 10px;
  border-radius: 50%;
  background: #eeeeec;
  color: #292929;
}

.document-empty h3 {
  margin: 0;
  color: #292929;
  font-size: 1rem;
}

.document-empty p {
  margin: 5px 0 0;
  font-size: 0.84rem;
}

.document-empty button {
  margin-top: 13px;
  padding: 7px 11px;
  border: 1px solid #171717;
  border-radius: 6px;
  background: #fff;
  color: #171717;
  font-weight: 700;
}

:global(body.theme-dark) .member-profile,
:global(body.theme-dark) .edit-panel,
:global(body.theme-dark) .document-toolbar,
:global(body.theme-dark) .document-summary span {
  border-color: #3f3f46;
  background: #242428;
  color: #f4f4f5;
}

:global(body.theme-dark) .panel-heading,
:global(body.theme-dark) .actions,
:global(body.theme-dark) .profile-stats,
:global(body.theme-dark) .document-section {
  border-color: #3f3f46;
}

:global(body.theme-dark) .profile-stat-icon,
:global(body.theme-dark) .panel-heading-icon,
:global(body.theme-dark) .role-note-icon,
:global(body.theme-dark) .document-empty > span {
  background: #34343a;
  color: #f4f4f5;
}

:global(body.theme-dark) .role-note,
:global(body.theme-dark) .document-empty {
  border-color: #3f3f46;
  background: #202024;
}

:global(body.theme-dark) .clear-filter-button,
:global(body.theme-dark) .document-empty button {
  border-color: #52525b;
  background: #242428;
  color: #f4f4f5;
}

:global(body.theme-dark) .back-button {
  border: 0;
  background: transparent;
  color: #f4f4f5;
}

:global(body.theme-dark) .role-admin {
  background: #4a252a;
  color: #fca5a5;
}

:global(body.theme-dark) .role-editor {
  background: #493a1f;
  color: #fcd34d;
}

:global(body.theme-dark) .role-viewer {
  background: #1d3b4c;
  color: #7dd3fc;
}

@media (max-width: 1150px) {
  .document-toolbar {
    grid-template-columns: repeat(3, minmax(0, 1fr)) auto;
  }

  .document-search {
    grid-column: 1 / -1;
  }
}

@media (max-width: 900px) {
  .member-detail-layout {
    grid-template-columns: 1fr;
  }

  .member-profile {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    column-gap: 18px;
    align-items: center;
  }

  .profile-avatar {
    margin: 0;
  }

  .profile-identity {
    text-align: left;
  }

  .profile-stats {
    grid-column: 1 / -1;
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 700px) {
  .page-heading,
  .document-section-heading {
    align-items: flex-start;
    flex-direction: column;
  }

  .document-summary {
    justify-content: flex-start;
  }

  .form-grid,
  .profile-stats,
  .document-toolbar {
    grid-template-columns: 1fr;
  }

  .document-search,
  .clear-filter-button {
    grid-column: auto;
  }

  .clear-filter-button {
    width: 100%;
  }

  .full-row {
    grid-column: auto;
  }

  .actions {
    flex-wrap: wrap;
  }

  .actions .me-auto {
    width: 100%;
    margin-right: 0 !important;
  }
}

@media (max-width: 480px) {
  .member-profile {
    grid-template-columns: 1fr;
    text-align: center;
  }

  .profile-avatar {
    margin: 0 auto;
  }

  .profile-identity {
    margin-top: 12px;
    text-align: center;
  }
}
</style>
