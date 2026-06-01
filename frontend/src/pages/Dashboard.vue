<template>
  <div>
    <div class="container-fluid py-4">
      <h1 class="mb-4">Trang chủ</h1>
      
      <!-- 4 thẻ thống kê -->
      <div class="row mb-4">
        <div class="col-md-3">
          <div class="card text-center">
            <div class="card-body">
              <h5 class="card-title"><i class="fas fa-chart-bar me-2"></i>Tổng tài liệu</h5>
              <p class="display-4">1,234</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-center">
            <div class="card-body">
              <h5 class="card-title"><i class="fas fa-list me-2"></i>Quy trình</h5>
              <p class="display-4">45</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-center">
            <div class="card-body">
              <h5 class="card-title"><i class="fas fa-star me-2"></i>Yêu thích</h5>
              <p class="display-4">28</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-center">
            <div class="card-body">
              <h5 class="card-title"><i class="fas fa-clock me-2"></i>Gần đây</h5>
              <p class="display-4">12</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
          <a class="nav-link" href="#recent" @click="activeTab = 'recent'" :class="{ active: activeTab === 'recent' }"><i class="fas fa-book me-2"></i>Tài liệu gần đây</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#favorites" @click="activeTab = 'favorites'" :class="{ active: activeTab === 'favorites' }"><i class="fas fa-star me-2"></i>Yêu thích</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#activity" @click="activeTab = 'activity'" :class="{ active: activeTab === 'activity' }"><i class="fas fa-clock me-2"></i>Hoạt động gần đây</a>
        </li>
      </ul>

      <!-- Tab content -->
      <div class="card">
        <div class="card-body">
          <div v-if="activeTab === 'recent'">
            <p><i class="fas fa-book me-2"></i>Tài liệu gần đây sẽ hiển thị ở đây</p>
          </div>
          <div v-else-if="activeTab === 'favorites'">
            <p><i class="fas fa-star me-2"></i>Tài liệu yêu thích sẽ hiển thị ở đây</p>
          </div>
          <div v-else-if="activeTab === 'activity'">
            <p><i class="fas fa-clock me-2"></i>Hoạt động gần đây sẽ hiển thị ở đây</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Dashboard',
  data() {
    return {
      activeTab: 'recent'
    }
  }
}
</script>

<style scoped>
.card {
  border-radius: 8px;
  box-shadow: none;
}

.nav-tabs .nav-link {
  cursor: pointer;
}

.display-4 {
  font-size: 2.5rem;
  font-weight: bold;
  color: #171717;
}
</style>
