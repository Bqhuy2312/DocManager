<template>
  <div class="login-container">
    <div class="login-box">
      <h1 class="text-center mb-4">
        <i class="fas fa-user-lock"></i> DocManager
      </h1>
      <form @submit.prevent="handleLogin">
        <div v-if="error" class="alert alert-danger" role="alert">
          {{ error }}
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input
            type="email"
            class="form-control"
            id="email"
            v-model="email"
            required
          />
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Mật khẩu</label>
          <input
            type="password"
            class="form-control"
            id="password"
            v-model="password"
            required
          />
        </div>
        <button type="submit" class="btn btn-primary w-100" :disabled="loading">
          {{ loading ? "Đang đăng nhập..." : "Đăng nhập" }}
        </button>
      </form>
    </div>
  </div>
</template>

<script>
import { login } from "@/services/authService";

export default {
  data() {
    return {
      email: "",
      password: "",
      error: "",
      loading: false,
    };
  },
  methods: {
    async handleLogin() {
      this.error = "";
      this.loading = true;
      try {
        const data = await login(this.email, this.password);
        localStorage.setItem("user", JSON.stringify(data.user));
        this.$router.push("/dashboard");
      } catch (err) {
        this.error =
          err.response?.data?.message || "Lỗi đăng nhập. Vui lòng thử lại.";
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  padding: 20px;
  background: #f6f6f4;
}

.login-box {
  background: white;
  padding: 32px;
  border: 1px solid #dededb;
  border-radius: 8px;
  width: 100%;
  max-width: 360px;
}

</style>
