<template>
  <div class="login-container">
    <div class="login-box">
      <h1 class="text-center mb-4"><i class="fas fa-user-lock"></i> DocManager</h1>
      <form @submit.prevent="handleLogin">
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input type="email" class="form-control" id="email" v-model="email" required>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Mật khẩu</label>
          <input type="password" class="form-control" id="password" v-model="password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Đăng nhập</button>
      </form>
      <hr>
      <p class="text-center text-muted">Hoặc đăng nhập nhanh:</p>
      <div class="demo-accounts">
        <button class="btn btn-outline-secondary btn-sm w-100 mb-2" @click="demoLogin('admin@demo.com')">
          <i class="fas fa-user-shield"></i> Admin (Quản trị viên)
        </button>
        <button class="btn btn-outline-secondary btn-sm w-100 mb-2" @click="demoLogin('editor@demo.com')">
          <i class="fas fa-user-edit"></i> Editor (Biên tập viên)
        </button>
        <button class="btn btn-outline-secondary btn-sm w-100" @click="demoLogin('viewer@demo.com')">
          <i class="fas fa-user"></i> Viewer (Người xem)
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Login',
  data() {
    return {
      email: '',
      password: ''
    }
  },
  methods: {
    handleLogin() {
      if (this.email && this.password) {
        // Giả lập đăng nhập
        localStorage.setItem('user', JSON.stringify({ email: this.email, role: 'user' }))
        this.$router.push('/dashboard')
      }
    },
    demoLogin(email) {
      this.email = email
      const roles = {
        'admin@demo.com': 'admin',
        'editor@demo.com': 'editor',
        'viewer@demo.com': 'viewer'
      }
      localStorage.setItem('user', JSON.stringify({ email, role: roles[email] }))
      this.$router.push('/dashboard')
    }
  }
}
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.login-box {
  background: white;
  padding: 40px;
  border-radius: 10px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
  width: 100%;
  max-width: 400px;
}

.demo-accounts button {
  font-size: 0.9rem;
}
</style>
