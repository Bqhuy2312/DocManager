<template>
  <div class="container-fluid py-4">
    <div class="row">
      <div class="col-md-8">
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3><i class="fas fa-file"></i> {{ document.name }}</h3>
            <div>
              <button class="btn btn-sm btn-warning me-2" v-if="canEdit"><i class="fas fa-edit"></i></button>
              <button class="btn btn-sm btn-success me-2" @click="downloadDocument"><i class="fas fa-download"></i></button>
              <button class="btn btn-sm" :class="document.isFavorite ? 'btn-warning' : 'btn-outline-warning'" 
                      @click="toggleFavorite"><i class="fas fa-star"></i></button>
            </div>
          </div>
          <div class="card-body">
            <div class="mb-4">
              <p class="text-muted">{{ document.description }}</p>
            </div>

            <!-- Thông tin tài liệu -->
            <div class="row mb-4">
              <div class="col-md-6">
                <p><strong><i class="fas fa-tag"></i> Danh mục:</strong> {{ document.category }}</p>
                <p><strong><i class="fas fa-building"></i> Phòng ban:</strong> {{ document.department }}</p>
              </div>
              <div class="col-md-6">
                <p><strong><i class="fas fa-user"></i> Người tạo:</strong> {{ document.author }}</p>
                <p><strong><i class="fas fa-calendar"></i> Cập nhật:</strong> {{ formatDate(document.updatedAt) }}</p>
              </div>
            </div>

            <!-- Tag -->
            <div class="mb-4">
              <p><strong><i class="fas fa-bookmark"></i> Tag:</strong></p>
              <div>
                <span v-for="tag in document.tags" :key="tag" class="badge bg-secondary me-2">{{ tag }}</span>
              </div>
            </div>

            <!-- Bình luận -->
            <div class="mt-5">
              <h5><i class="fas fa-comment"></i> Bình luận ({{ comments.length }})</h5>
              <div class="mb-3">
                <textarea class="form-control" v-model="newComment" placeholder="Viết bình luận..." rows="3"></textarea>
                <button class="btn btn-primary btn-sm mt-2" @click="addComment">Gửi</button>
              </div>

              <div v-for="comment in comments" :key="comment.id" class="card mb-2">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <div>
                      <strong>{{ comment.author }}</strong>
                      <small class="text-muted ms-2">{{ formatDate(comment.createdAt) }}</small>
                    </div>
                    <button v-if="canDeleteComment(comment)" class="btn btn-sm btn-danger" @click="deleteComment(comment.id)"><i class="fas fa-trash"></i></button>
                  </div>
                  <p class="mt-2">{{ comment.content }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar thông tin -->
      <div class="col-md-4">
        <div class="card mb-3">
          <div class="card-header bg-light">
            <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Thông Tin</h6>
          </div>
          <div class="card-body">
            <p><strong>📥 Lượt xem:</strong> {{ document.views }}</p>
            <p><strong><i class="fas fa-download"></i> Lượt tải:</strong> {{ document.downloads }}</p>
            <p><strong><i class="fas fa-comment"></i> Bình luận:</strong> {{ comments.length }}</p>
            <p><strong><i class="fas fa-star"></i> Yêu thích:</strong> {{ document.favorites }}</p>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header bg-light">
            <h6 class="mb-0"><i class="fas fa-users"></i> Người Có Quyền</h6>
          </div>
          <div class="card-body">
            <small>
              <p v-for="person in document.sharedWith" :key="person.id">
                <i class="fas fa-user"></i> {{ person.name }} ({{ person.role }})
              </p>
            </small>
          </div>
        </div>

        <div class="card">
          <div class="card-header bg-light">
            <h6 class="mb-0"><i class="fas fa-check"></i> Trạng Thái Phê Duyệt</h6>
          </div>
          <div class="card-body">
            <span v-if="document.status === 'approved'" class="badge bg-success p-2">Đã phê duyệt</span>
            <span v-else-if="document.status === 'pending'" class="badge bg-warning p-2">Chờ phê duyệt</span>
            <span v-else class="badge bg-danger p-2">Từ chối</span>
            <p class="small text-muted mt-2">Phê duyệt bởi: {{ document.approvedBy }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'DocumentDetail',
  data() {
    return {
      document: {
        id: 1,
        name: 'Quy trình tuyển dụng',
        description: 'Quy trình chi tiết cho việc tuyển dụng nhân sự mới vào công ty',
        category: 'Quy trình',
        department: 'Nhân sự',
        author: 'Nguyễn Văn A',
        updatedAt: new Date('2026-05-30'),
        tags: ['HR', 'Tuyển dụng', 'Quy trình'],
        views: 156,
        downloads: 42,
        favorites: 28,
        isFavorite: true,
        status: 'approved',
        approvedBy: 'Trần Thị B',
        sharedWith: [
          { id: 1, name: 'Nguyễn Văn A', role: 'Chủ' },
          { id: 2, name: 'Lê Văn C', role: 'Biên tập viên' },
          { id: 3, name: 'Phạm Thị D', role: 'Người xem' }
        ]
      },
      newComment: '',
      comments: [
        { id: 1, author: 'Trần Thị B', content: 'Tài liệu rất hữu ích!', createdAt: new Date('2026-05-30T10:30'), userId: 2 },
        { id: 2, author: 'Lê Văn C', content: 'Cần cập nhật phần công việc thêm', createdAt: new Date('2026-05-31T14:15'), userId: 3 }
      ]
    }
  },
  computed: {
    canEdit() {
      const user = JSON.parse(localStorage.getItem('user') || '{}')
      return user.role === 'admin' || user.role === 'editor'
    }
  },
  methods: {
    downloadDocument() {
      alert('Tải xuống tài liệu: ' + this.document.name)
      this.document.downloads++
    },
    toggleFavorite() {
      this.document.isFavorite = !this.document.isFavorite
      this.document.favorites += this.document.isFavorite ? 1 : -1
    },
    addComment() {
      if (this.newComment.trim()) {
        this.comments.push({
          id: Math.max(...this.comments.map(c => c.id), 0) + 1,
          author: 'Bạn',
          content: this.newComment,
          createdAt: new Date(),
          userId: 1
        })
        this.newComment = ''
      }
    },
    deleteComment(id) {
      this.comments = this.comments.filter(c => c.id !== id)
    },
    canDeleteComment(comment) {
      const user = JSON.parse(localStorage.getItem('user') || '{}')
      return user.role === 'admin' || comment.userId === 1
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString('vi-VN', { hour: '2-digit', minute: '2-digit' })
    }
  }
}
</script>

<style scoped>
.card {
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.badge {
  font-size: 0.9rem;
}
</style>
