<template>
  <div class="container-fluid py-4">
    <div class="backup-header">
      <div>
        <h1><i class="fas fa-database"></i> Sao lưu dữ liệu</h1>
        <p>Quản lý các bản sao lưu database và file Cloudinary của hệ thống.</p>
      </div>

      <div class="backup-actions">
        <button class="btn btn-outline-dark" type="button" :disabled="creating || restoring" @click="runBackup('database')">
          <i class="fas fa-server me-2"></i>Backup database
        </button>
        <button class="btn btn-dark" type="button" :disabled="creating || restoring" @click="runBackup('full')">
          <i class="fas fa-cloud-download-alt me-2"></i>Backup full
        </button>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <section class="backup-info">
      <div>
        <strong>Database</strong>
        <span>Xuất toàn bộ bảng dữ liệu thành file SQL.</span>
      </div>
      <div>
        <strong>Full</strong>
        <span>Bao gồm database, tài liệu, phiên bản tài liệu và avatar từ Cloudinary.</span>
      </div>
      <div>
        <strong>Lưu trữ</strong>
        <span>File zip được lưu riêng trong storage/app/backups.</span>
      </div>
    </section>

    <Loading v-if="loading" type="list" :count="4" />

    <div v-else class="backup-panel">
      <div class="backup-panel-head">
        <h2>Danh sách backup</h2>
        <button class="btn btn-outline-dark btn-sm" type="button" :disabled="loading" @click="loadBackups">
          <i class="fas fa-sync-alt me-1"></i>Làm mới
        </button>
      </div>

      <p v-if="!backups.length" class="text-muted">Chưa có bản backup nào.</p>

      <div v-else class="backup-table-wrap">
        <table class="backup-table">
          <thead>
            <tr>
              <th>Loại</th>
              <th>Trạng thái</th>
              <th>Dung lượng</th>
              <th>Cloudinary</th>
              <th>Người tạo</th>
              <th>Thời gian</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="backup in backups" :key="backup.id">
              <td>
                <strong>{{ typeLabel(backup.type) }}</strong>
                <small>{{ backup.file_name || "Chưa có file" }}</small>
              </td>
              <td>
                <span class="status-pill" :class="backup.status">{{ statusLabel(backup.status) }}</span>
                <small v-if="backup.message" class="error-text">{{ backup.message }}</small>
              </td>
              <td>{{ formatFileSize(backup.file_size) }}</td>
              <td>
                <span>{{ backup.documents_count }} tài liệu</span>
                <small>{{ backup.versions_count }} phiên bản, {{ backup.avatars_count }} avatar</small>
              </td>
              <td>{{ backup.created_by || "Không rõ" }}</td>
              <td>{{ formatDateTime(backup.created_at) }}</td>
              <td>
                <div class="row-actions">
                  <button
                    class="btn btn-outline-dark btn-sm"
                    type="button"
                    :disabled="backup.status !== 'success'"
                    @click="download(backup)"
                  >
                    <i class="fas fa-download me-1"></i>Tải
                  </button>
                  <button
                    class="btn btn-outline-primary btn-sm"
                    type="button"
                    :disabled="backup.status !== 'success' || creating || restoring"
                    :title="backup.type === 'full' ? 'Import database.sql, không phục hồi file Cloudinary thật' : 'Import database.sql từ backup này'"
                    @click="restoreFromBackup(backup)"
                  >
                    <i class="fas fa-file-import me-1"></i>Import
                  </button>
                  <button class="btn btn-outline-danger btn-sm" type="button" @click="removeBackup(backup)">
                    <i class="fas fa-trash me-1"></i>Xóa
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import Loading from "@/components/common/Loading.vue";
import {
  createBackup,
  deleteBackup,
  downloadBackup,
  getBackups,
  restoreBackup,
  restoreStoredBackup,
} from "@/services/backupService";
import { confirmDialog, notify } from "@/services/notificationService";

export default {
  name: "Backups",
  components: { Loading },
  data() {
    return {
      backups: [],
      loading: false,
      creating: false,
      restoring: false,
      error: "",
    };
  },
  async mounted() {
    await this.loadBackups();
  },
  methods: {
    async loadBackups() {
      this.loading = true;
      this.error = "";

      try {
        this.backups = await getBackups();
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể tải danh sách backup.";
      } finally {
        this.loading = false;
      }
    },
    async runBackup(type) {
      const confirmed = await confirmDialog({
        title: type === "full" ? "Tạo backup full" : "Tạo backup database",
        message: type === "full"
          ? "Backup full có thể mất lâu nếu có nhiều file Cloudinary. Bạn muốn tiếp tục?"
          : "Hệ thống sẽ xuất database thành file zip. Bạn muốn tiếp tục?",
        confirmText: "Tạo backup",
      });

      if (!confirmed) return;

      this.creating = true;
      try {
        const backup = await createBackup(type);
        this.backups = [backup, ...this.backups.filter((item) => item.id !== backup.id)];
        notify({
          title: backup.status === "success" ? "Đã tạo backup" : "Backup thất bại",
          message: backup.status === "success"
            ? `${this.typeLabel(type)} đã sẵn sàng để tải xuống.`
            : backup.message || "Không thể tạo backup.",
          type: backup.status === "success" ? "success" : "danger",
        });
      } catch (error) {
        notify({
          title: "Không thể tạo backup",
          message: error.response?.data?.message || "Vui lòng thử lại sau.",
          type: "danger",
        });
        await this.loadBackups();
      } finally {
        this.creating = false;
      }
    },
    async download(backup) {
      try {
        await downloadBackup(backup);
      } catch (error) {
        notify({
          title: "Không thể tải backup",
          message: error.response?.data?.message || "File backup không khả dụng.",
          type: "danger",
        });
      }
    },
    async restoreFromBackup(backup) {
      const confirmed = await confirmDialog({
        title: "Import backup",
        message: backup.type === "full"
          ? "Backup full sẽ import database.sql vào hệ thống, nhưng không phục hồi file Cloudinary thật. Hệ thống sẽ tự tạo một backup database trước khi import. Bạn muốn tiếp tục?"
          : "Backup database sẽ import database.sql vào hệ thống và có thể ghi đè dữ liệu hiện tại. Hệ thống sẽ tự tạo một backup database trước khi import. Bạn muốn tiếp tục?",
        confirmText: "Import backup",
        tone: "danger",
      });

      if (!confirmed) return;

      this.restoring = true;
      try {
        const result = await restoreStoredBackup(backup);
        notify({
          title: "Import backup thành công",
          message: result.message || "Dữ liệu đã được khôi phục từ backup.",
          type: "success",
        });
        await this.loadBackups();
      } catch (error) {
        notify({
          title: "Không thể import backup",
          message: error.response?.data?.message || "File backup không hợp lệ hoặc không thể import.",
          type: "danger",
        });
      } finally {
        this.restoring = false;
      }
    },
    async restoreFromFile(event) {
      const file = event.target.files[0];
      event.target.value = "";
      if (!file) return;

      const confirmed = await confirmDialog({
        title: "Import backup",
        message: "Thao tác này sẽ import database.sql vào hệ thống và có thể ghi đè dữ liệu hiện tại. Hệ thống sẽ tự tạo một backup database trước khi import. Bạn muốn tiếp tục?",
        confirmText: "Import backup",
        tone: "danger",
      });

      if (!confirmed) return;

      this.restoring = true;
      try {
        const result = await restoreBackup(file);
        notify({
          title: "Import backup thành công",
          message: result.message || "Dữ liệu đã được khôi phục từ backup.",
          type: "success",
        });
        await this.loadBackups();
      } catch (error) {
        notify({
          title: "Không thể import backup",
          message: error.response?.data?.message || "File backup không hợp lệ hoặc không thể import.",
          type: "danger",
        });
      } finally {
        this.restoring = false;
      }
    },
    async removeBackup(backup) {
      const confirmed = await confirmDialog({
        title: "Xóa backup",
        message: `Xóa ${backup.file_name || this.typeLabel(backup.type)}?`,
        confirmText: "Xóa backup",
        tone: "danger",
      });

      if (!confirmed) return;

      try {
        await deleteBackup(backup.id);
        this.backups = this.backups.filter((item) => item.id !== backup.id);
        notify({
          title: "Đã xóa backup",
          message: "Bản backup đã được xóa khỏi hệ thống.",
        });
      } catch (error) {
        notify({
          title: "Không thể xóa backup",
          message: error.response?.data?.message || "Vui lòng thử lại sau.",
          type: "danger",
        });
      }
    },
    typeLabel(type) {
      return {
        database: "Database",
        full: "Full backup",
      }[type] || type;
    },
    statusLabel(status) {
      return {
        pending: "Chờ xử lý",
        running: "Đang chạy",
        success: "Hoàn tất",
        failed: "Thất bại",
      }[status] || status;
    },
    formatDateTime(value) {
      if (!value) return "Chưa có";
      return new Date(value).toLocaleString("vi-VN", {
        timeZone: "Asia/Ho_Chi_Minh",
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });
    },
    formatFileSize(bytes) {
      if (!bytes) return "0 Bytes";
      const units = ["Bytes", "KB", "MB", "GB"];
      const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
      return `${Math.round((bytes / Math.pow(1024, index)) * 100) / 100} ${units[index]}`;
    },
  },
};
</script>

<style scoped>
.backup-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 22px;
}

.backup-header h1 {
  margin: 0;
  font-size: 1.85rem;
  font-weight: 800;
}

.backup-header p {
  margin: 6px 0 0;
  color: #707070;
}

.backup-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.backup-info {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
  margin-bottom: 22px;
}

.backup-info div,
.backup-panel {
  border: 1px solid #dededb;
  border-radius: 8px;
  background: #fff;
}

.backup-info div {
  display: grid;
  gap: 5px;
  padding: 16px;
}

.backup-info strong {
  color: #171717;
}

.backup-info span {
  color: #707070;
  font-size: 0.9rem;
}

.backup-panel {
  padding: 20px;
}

.backup-panel-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  margin-bottom: 16px;
}

.backup-panel-head h2 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 800;
}

.backup-table-wrap {
  overflow-x: auto;
}

.backup-table {
  width: 100%;
  min-width: 900px;
  border-collapse: collapse;
}

.backup-table th,
.backup-table td {
  padding: 14px 12px;
  border-top: 1px solid #ededeb;
  vertical-align: middle;
}

.backup-table th {
  color: #707070;
  font-size: 0.78rem;
  font-weight: 800;
  text-transform: uppercase;
}

.backup-table small {
  display: block;
  margin-top: 3px;
  color: #707070;
}

.status-pill {
  display: inline-flex;
  min-height: 28px;
  align-items: center;
  border-radius: 999px;
  padding: 0 10px;
  background: #ededeb;
  color: #555;
  font-size: 0.82rem;
  font-weight: 800;
}

.status-pill.success {
  background: #dcfce7;
  color: #166534;
}

.status-pill.running,
.status-pill.pending {
  background: #fef3c7;
  color: #92400e;
}

.status-pill.failed {
  background: #fee2e2;
  color: #b91c1c;
}

.error-text {
  color: #b91c1c !important;
}

.row-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

@media (max-width: 900px) {
  .backup-header,
  .backup-panel-head {
    display: grid;
  }

  .backup-info {
    grid-template-columns: 1fr;
  }
}
</style>
