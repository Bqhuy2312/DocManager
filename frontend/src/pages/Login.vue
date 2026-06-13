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
            :disabled="requiresTwoFactor"
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
            :disabled="requiresTwoFactor"
            required
          />
        </div>
        <div v-if="requiresTwoFactor" class="mb-3">
          <label for="two-factor-code" class="form-label">Mã xác thực 2FA</label>
          <input
            type="text"
            inputmode="numeric"
            maxlength="6"
            class="form-control"
            id="two-factor-code"
            v-model.trim="twoFactorCode"
            placeholder="Nhập 6 số"
            required
          />
          <small class="text-muted">Nhập mã bảo mật 6 số bạn đã đặt trong trang cài đặt.</small>
        </div>
        <button type="submit" class="btn btn-primary w-100" :disabled="loading">
          {{ loading ? "Đang đăng nhập..." : (requiresTwoFactor ? "Xác nhận 2FA" : "Đăng nhập") }}
        </button>
        <button
          v-if="requiresTwoFactor"
          type="button"
          class="btn btn-outline-secondary w-100 mt-2"
          :disabled="loading"
          @click="resetTwoFactorStep"
        >
          Nhập lại email/mật khẩu
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
      twoFactorCode: "",
      requiresTwoFactor: false,
      error: "",
      loading: false,
    };
  },
  methods: {
    async handleLogin() {
      this.error = "";
      this.loading = true;
      try {
        const data = await login(this.email, this.password, this.twoFactorCode);
        localStorage.setItem("user", JSON.stringify(data.user));
        this.$router.push("/dashboard");
      } catch (err) {
        if (err.response?.data?.requires_2fa) {
          this.requiresTwoFactor = true;
          this.error = err.response?.status === 423
            ? "Tài khoản đã bật 2FA. Vui lòng nhập mã xác thực."
            : err.response?.data?.message || "Mã xác thực không đúng.";
          return;
        }

        this.error = err.response?.data?.message || "Lỗi đăng nhập. Vui lòng thử lại.";
      } finally {
        this.loading = false;
      }
    },
    resetTwoFactorStep() {
      this.requiresTwoFactor = false;
      this.twoFactorCode = "";
      this.error = "";
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
