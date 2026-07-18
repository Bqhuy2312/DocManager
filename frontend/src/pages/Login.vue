<template>
  <div class="login-page">
    <header class="login-hero">
      <div class="login-icon">
        <i class="far fa-file-alt"></i>
      </div>
      <h1>Hệ thống Quản lý Tài liệu Nội Bộ</h1>
      <p>Đăng nhập để truy cập hệ thống</p>
    </header>

    <section class="login-card">
      <div class="login-card-heading">
        <h2>{{ isRegisterMode ? "Đăng ký" : "Đăng nhập" }}</h2>
        <p>{{ isRegisterMode ? "Tạo tài khoản người xem để truy cập hệ thống" : "Nhập thông tin đăng nhập của bạn" }}</p>
      </div>

      <div v-if="!requiresTwoFactor" class="auth-mode-switch" role="tablist" aria-label="Chọn chế độ xác thực">
        <button
          type="button"
          :class="{ active: !isRegisterMode }"
          @click="setAuthMode('login')"
        >
          Đăng nhập
        </button>
        <button
          type="button"
          :class="{ active: isRegisterMode }"
          @click="setAuthMode('register')"
        >
          Đăng ký
        </button>
      </div>

      <form @submit.prevent="isRegisterMode ? handleRegister() : handleLogin()">
        <div v-if="error" class="alert alert-danger" role="alert">
          {{ error }}
        </div>

        <label v-if="isRegisterMode" class="login-field" for="full-name">
          <span>Họ tên</span>
          <div class="input-shell">
            <i class="far fa-user"></i>
            <input
              id="full-name"
              v-model="fullName"
              type="text"
              required
              placeholder="Nhập họ tên"
            >
          </div>
        </label>

        <label class="login-field" for="email">
          <span>Email</span>
          <div class="input-shell">
            <i class="far fa-envelope"></i>
            <input
              id="email"
              v-model="email"
              type="email"
              :disabled="requiresTwoFactor"
              required
              placeholder="email@company.com"
            >
          </div>
        </label>

        <label v-if="isRegisterMode" class="login-field" for="department">
          <span>Phòng ban</span>
          <div class="input-shell">
            <i class="far fa-building"></i>
            <select
              id="department"
              v-model="departmentId"
              required
              :disabled="departmentsLoading"
            >
              <option value="">{{ departmentsLoading ? "Đang tải phòng ban..." : "Chọn phòng ban" }}</option>
              <option v-for="department in departments" :key="department.id" :value="department.id">
                {{ department.name }}
              </option>
            </select>
          </div>
        </label>

        <label class="login-field" for="password">
          <span>Mật khẩu</span>
          <div class="input-shell">
            <i class="fas fa-lock"></i>
            <input
              id="password"
              v-model="password"
              type="password"
              :disabled="requiresTwoFactor"
              required
              placeholder="Nhập mật khẩu"
            >
          </div>
        </label>

        <label v-if="isRegisterMode" class="login-field" for="password-confirmation">
          <span>Xác nhận mật khẩu</span>
          <div class="input-shell">
            <i class="fas fa-lock"></i>
            <input
              id="password-confirmation"
              v-model="passwordConfirmation"
              type="password"
              required
              placeholder="Nhập lại mật khẩu"
            >
          </div>
        </label>

        <div v-if="requiresTwoFactor && !isRegisterMode" class="login-field">
          <label for="two-factor-code">Mã xác thực 2FA</label>
          <div class="two-factor-entry" @click="$refs.twoFactorInput?.focus()">
            <span
              v-for="index in 6"
              :key="index"
              class="two-factor-dot"
              :class="{ filled: twoFactorCode.length >= index, 'is-filled': twoFactorCode.length >= index }"
            ></span>
            <input
              id="two-factor-code"
              ref="twoFactorInput"
              :value="twoFactorCode"
              type="password"
              inputmode="numeric"
              maxlength="6"
              class="two-factor-hidden"
              required
              @input="updateTwoFactorCode"
            >
          </div>
          <small>Nhập mã bảo mật 6 số bạn đã đặt trong trang cài đặt.</small>
        </div>

        <button type="submit" class="login-submit" :disabled="loading">
          {{ submitLabel }}
        </button>

        <div v-if="!requiresTwoFactor && !isRegisterMode" class="login-divider">
          <span>hoặc</span>
        </div>

        <button
          v-if="!requiresTwoFactor && !isRegisterMode"
          type="button"
          class="guest-login-button"
          :disabled="loading"
          @click="handleGuestLogin"
        >
          <i class="far fa-eye me-2"></i>Đăng nhập nhanh với tư cách khách
        </button>

        <button
          v-if="requiresTwoFactor"
          type="button"
          class="login-secondary"
          :disabled="loading"
          @click="resetTwoFactorStep"
        >
          Nhập lại email/mật khẩu
        </button>
      </form>
    </section>
  </div>
</template>

<script>
import { getDepartmentOptions, guestLogin, login, register } from "@/services/authService";

export default {
  data() {
    return {
      isRegisterMode: false,
      fullName: "",
      email: "",
      password: "",
      passwordConfirmation: "",
      departmentId: "",
      departments: [],
      departmentsLoading: false,
      twoFactorCode: "",
      requiresTwoFactor: false,
      error: "",
      loading: false,
    };
  },
  computed: {
    submitLabel() {
      if (this.loading) {
        return this.isRegisterMode ? "Đang đăng ký..." : "Đang đăng nhập...";
      }

      if (this.requiresTwoFactor && !this.isRegisterMode) {
        return "Xác nhận 2FA";
      }

      return this.isRegisterMode ? "Đăng ký" : "Đăng nhập";
    },
  },
  mounted() {
    this.loadDepartments();
  },
  methods: {
    setAuthMode(mode) {
      this.isRegisterMode = mode === "register";
      this.error = "";
      this.requiresTwoFactor = false;
      this.twoFactorCode = "";
    },
    async loadDepartments() {
      this.departmentsLoading = true;

      try {
        this.departments = await getDepartmentOptions();
      } catch {
        this.departments = [];
      } finally {
        this.departmentsLoading = false;
      }
    },
    getErrorMessage(err, fallback) {
      const errors = err.response?.data?.errors;

      if (errors) {
        return Object.values(errors).flat()[0] || fallback;
      }

      return err.response?.data?.message || fallback;
    },
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
    async handleRegister() {
      this.error = "";

      if (this.password !== this.passwordConfirmation) {
        this.error = "Mật khẩu xác nhận không khớp.";
        return;
      }

      this.loading = true;

      try {
        const data = await register({
          full_name: this.fullName,
          email: this.email,
          password: this.password,
          password_confirmation: this.passwordConfirmation,
          department_id: this.departmentId,
        });

        localStorage.setItem("user", JSON.stringify(data.user));
        this.$router.push("/dashboard");
      } catch (err) {
        this.error = this.getErrorMessage(err, "Không thể đăng ký tài khoản. Vui lòng thử lại.");
      } finally {
        this.loading = false;
      }
    },
    async handleGuestLogin() {
      this.error = "";
      this.loading = true;

      try {
        const data = await guestLogin();
        localStorage.setItem("user", JSON.stringify(data.user));
        this.$router.push("/dashboard");
      } catch (err) {
        this.error = err.response?.data?.message || "Không thể đăng nhập với tư cách người xem.";
      } finally {
        this.loading = false;
      }
    },
    resetTwoFactorStep() {
      this.requiresTwoFactor = false;
      this.twoFactorCode = "";
      this.error = "";
    },
    updateTwoFactorCode(event) {
      this.twoFactorCode = event.target.value.replace(/\D/g, "").slice(0, 6);
      event.target.value = this.twoFactorCode;
    },
  },
};
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: grid;
  align-content: start;
  justify-items: center;
  padding: 22px 16px 44px;
  background: #f6f6f4;
}

.login-hero {
  display: grid;
  justify-items: center;
  gap: 10px;
  margin-bottom: 30px;
  text-align: center;
}

.login-icon {
  display: grid;
  width: 80px;
  height: 80px;
  place-items: center;
  border-radius: 14px;
  background: #171717;
  color: #ffffff;
  font-size: 2rem;
}

.login-hero h1 {
  margin: 6px 0 0;
  color: #171717;
  font-size: 1.65rem;
  font-weight: 800;
}

.login-hero p {
  margin: 0;
  color: #707070;
  font-size: 1rem;
}

.login-card {
  width: min(100%, 560px);
  padding: 30px;
  border: 1px solid #dededb;
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 16px 40px rgba(23, 23, 23, 0.06);
}

.login-card-heading {
  margin-bottom: 26px;
}

.login-card-heading h2 {
  margin: 0 0 6px;
  color: #171717;
  font-size: 1.55rem;
  font-weight: 800;
}

.login-card-heading p {
  margin: 0;
  color: #707070;
}

.auth-mode-switch {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 6px;
  padding: 5px;
  margin-bottom: 22px;
  border: 1px solid #dededb;
  border-radius: 8px;
  background: #f6f6f4;
}

.auth-mode-switch button {
  min-height: 38px;
  border: 0;
  border-radius: 6px;
  background: transparent;
  color: #707070;
  font-weight: 800;
}

.auth-mode-switch button.active {
  background: #171717;
  color: #ffffff;
}

.login-field {
  display: grid;
  gap: 10px;
  margin-bottom: 20px;
  color: #171717;
  font-weight: 700;
}

.login-field small {
  color: #707070;
  font-weight: 400;
}

.input-shell {
  display: flex;
  min-height: 50px;
  align-items: center;
  gap: 12px;
  padding: 0 16px;
  border: 1px solid #dededb;
  border-radius: 7px;
  background: #ffffff;
  color: #707070;
}

.input-shell:focus-within {
  border-color: #171717;
}

.input-shell input,
.input-shell select {
  width: 100%;
  border: 0;
  outline: 0;
  background: transparent;
  color: #171717;
  font-size: 0.95rem;
}

.input-shell select {
  cursor: pointer;
}

.input-shell input::placeholder {
  color: #8a8a84;
}

.login-submit,
.login-secondary,
.guest-login-button {
  width: 100%;
  min-height: 50px;
  border-radius: 7px;
  font-weight: 800;
}

.login-submit {
  border: 1px solid #171717;
  background: #171717;
  color: #ffffff;
}

.login-submit:disabled,
.login-secondary:disabled,
.guest-login-button:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.login-secondary,
.guest-login-button {
  margin-top: 10px;
  border: 1px solid #dededb;
  background: #ffffff;
  color: #171717;
}

.guest-login-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.guest-login-button:hover {
  border-color: #171717;
}

.login-divider {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 18px 0 8px;
  color: #707070;
  font-size: 0.82rem;
  font-weight: 700;
}

.login-divider::before,
.login-divider::after {
  content: "";
  height: 1px;
  flex: 1;
  background: #dededb;
}

.two-factor-entry {
  position: relative;
  display: flex;
  justify-content: center;
  gap: 10px;
  padding: 8px 0;
  cursor: text;
}

.two-factor-dot {
  width: 22px;
  height: 22px;
  border: 2px solid #171717;
  border-radius: 50%;
  background: #ffffff;
  transition: background-color 0.15s ease, transform 0.15s ease;
}

.two-factor-dot.filled {
  background: #171717 !important;
  transform: scale(0.94);
}

.two-factor-hidden {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  border: 0;
  outline: 0;
  opacity: 0;
  cursor: text;
}

@media (max-width: 560px) {
  .login-card {
    padding: 22px;
  }

  .login-icon {
    width: 68px;
    height: 68px;
    font-size: 1.7rem;
  }
}
</style>
