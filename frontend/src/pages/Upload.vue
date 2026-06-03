<template>
  <div class="container-fluid py-4">
    <h1 class="mb-4"><i class="fas fa-arrow-up"></i> Tải Lên Tài Liệu</h1>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="success" class="alert alert-success">{{ success }}</div>

    <div class="row">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-body">
            <form @submit.prevent="submitForm">
              <div class="mb-3">
                <label class="form-label"><strong>Tiêu đề tài liệu</strong></label>
                <input v-model="form.title" type="text" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label"><strong>Mô tả</strong></label>
                <textarea v-model="form.description" class="form-control" rows="4"></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label"><strong>Danh mục / thư mục con</strong></label>
                <select v-model="form.folderId" class="form-select" required>
                  <option value="">-- Chọn thư mục con --</option>
                  <option v-for="folder in metadata.folders" :key="folder.id" :value="folder.id">
                    {{ folder.parent.name }} / {{ folder.name }}
                  </option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label"><strong>Tag, phân cách bằng dấu phẩy</strong></label>
                <input v-model="form.tags" type="text" class="form-control" placeholder="VD: tuyển dụng, onboarding">
              </div>
              <div class="mb-3">
                <label class="form-label"><strong>Chọn file, tối đa 100MB</strong></label>
                <input ref="fileInput" type="file" class="form-control" @change="handleFileUpload" required>
                <div v-if="form.file" class="mt-2 text-muted">
                  {{ form.file.name }} ({{ formatFileSize(form.file.size) }})
                </div>
              </div>
              <button type="submit" class="btn btn-primary btn-lg w-100" :disabled="submitting">
                {{ submitting ? "Đang tải lên Cloudinary..." : "Tải lên" }}
              </button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-4 mt-3 mt-lg-0">
        <div class="card">
          <div class="card-header"><strong>Quy trình</strong></div>
          <div class="card-body">
            <p>Tài liệu được lưu trên Cloudinary và URL được ghi vào <code>file_path</code>.</p>
            <p class="mb-0">Sau khi tải lên, tài liệu có trạng thái chờ phê duyệt.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { getDocumentMetadata, uploadDocument } from "@/services/documentService";

export default {
  name: "Upload",
  data() {
    return {
      metadata: { folders: [] },
      form: {
        title: "",
        description: "",
        folderId: "",
        tags: "",
        file: null,
      },
      submitting: false,
      error: "",
      success: "",
    };
  },
  async mounted() {
    try {
      this.metadata = await getDocumentMetadata();
    } catch (error) {
      this.error = error.response?.data?.message || "Không thể tải danh mục và thư mục.";
    }
  },
  methods: {
    handleFileUpload(event) {
      this.form.file = event.target.files[0] || null;
    },
    async submitForm() {
      this.submitting = true;
      this.error = "";
      this.success = "";

      const formData = new FormData();
      formData.append("title", this.form.title);
      formData.append("description", this.form.description);
      formData.append("folder_id", this.form.folderId);
      formData.append("tags", this.form.tags);
      formData.append("file", this.form.file);

      try {
        const document = await uploadDocument(formData);
        this.success = "Tải tài liệu lên thành công. Tài liệu đang chờ phê duyệt.";
        this.resetForm();
        this.$router.push(`/documents/${document.id}`);
      } catch (error) {
        this.error = error.response?.data?.message || "Không thể tải tài liệu lên.";
      } finally {
        this.submitting = false;
      }
    },
    resetForm() {
      this.form = { title: "", description: "", folderId: "", tags: "", file: null };
      this.$refs.fileInput.value = "";
    },
    formatFileSize(bytes) {
      if (!bytes) return "0 Bytes";
      const units = ["Bytes", "KB", "MB", "GB"];
      const index = Math.floor(Math.log(bytes) / Math.log(1024));
      return `${Math.round((bytes / Math.pow(1024, index)) * 100) / 100} ${units[index]}`;
    },
  },
};
</script>
