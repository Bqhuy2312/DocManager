<template>
  <Teleport to="body">
    <div class="toast-stack">
      <div
        v-for="toast in notificationState.toasts"
        :key="toast.id"
        class="app-toast"
        :class="toast.type"
      >
        <span class="toast-icon">
          <i class="fas" :class="toastIcon(toast.type)"></i>
        </span>
        <div>
          <strong>{{ toast.title }}</strong>
          <p v-if="toast.message">{{ toast.message }}</p>
        </div>
        <button type="button" aria-label="Đóng thông báo" @click="dismissToast(toast.id)">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>

    <div v-if="notificationState.confirm" class="confirm-backdrop" @click.self="closeConfirm(false)">
      <div class="confirm-dialog">
        <span class="confirm-icon" :class="notificationState.confirm.tone">
          <i class="fas" :class="confirmIcon(notificationState.confirm.tone)"></i>
        </span>
        <div class="confirm-copy">
          <h2>{{ notificationState.confirm.title }}</h2>
          <p>{{ notificationState.confirm.message }}</p>
        </div>
        <div class="confirm-actions">
          <button class="btn btn-light" type="button" @click="closeConfirm(false)">
            {{ notificationState.confirm.cancelText }}
          </button>
          <button
            class="btn"
            :class="notificationState.confirm.tone === 'danger' ? 'btn-danger' : 'btn-primary'"
            type="button"
            @click="closeConfirm(true)"
          >
            {{ notificationState.confirm.confirmText }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script>
import { closeConfirm, dismissToast, notificationState } from "@/services/notificationService";

export default {
  name: "NotificationHost",
  setup() {
    const toastIcon = (type) => ({
      success: "fa-check",
      danger: "fa-triangle-exclamation",
      warning: "fa-exclamation",
      info: "fa-info",
    }[type] || "fa-bell");

    const confirmIcon = (tone) => ({
      danger: "fa-trash",
      warning: "fa-triangle-exclamation",
      info: "fa-circle-info",
    }[tone] || "fa-circle-question");

    return {
      closeConfirm,
      confirmIcon,
      dismissToast,
      notificationState,
      toastIcon,
    };
  },
};
</script>

<style scoped>
.toast-stack {
  position: fixed;
  top: 18px;
  right: 18px;
  z-index: 2000;
  display: grid;
  width: min(360px, calc(100vw - 32px));
  gap: 10px;
}

.app-toast {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  align-items: start;
  gap: 12px;
  padding: 13px;
  border: 1px solid #dededb;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 14px 34px rgba(0, 0, 0, 0.14);
}

.toast-icon {
  display: inline-grid;
  width: 30px;
  height: 30px;
  place-items: center;
  border-radius: 50%;
  background: #eef8ef;
  color: #167a31;
}

.app-toast.danger .toast-icon {
  background: #fff0ed;
  color: #b42318;
}

.app-toast.warning .toast-icon {
  background: #fff7d6;
  color: #a66a00;
}

.app-toast.info .toast-icon {
  background: #edf4ff;
  color: #175cd3;
}

.app-toast strong {
  display: block;
  color: #171717;
  font-size: 0.92rem;
}

.app-toast p {
  margin: 2px 0 0;
  color: #707070;
  font-size: 0.82rem;
}

.app-toast button {
  border: 0;
  background: transparent;
  color: #707070;
}

.confirm-backdrop {
  position: fixed;
  inset: 0;
  z-index: 2100;
  display: grid;
  place-items: center;
  padding: 18px;
  background: rgba(0, 0, 0, 0.45);
}

.confirm-dialog {
  display: grid;
  width: min(420px, 100%);
  gap: 14px;
  padding: 18px;
  border: 1px solid #dededb;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 24px 54px rgba(0, 0, 0, 0.22);
}

.confirm-icon {
  display: inline-grid;
  width: 44px;
  height: 44px;
  place-items: center;
  border-radius: 50%;
  background: #edf4ff;
  color: #175cd3;
}

.confirm-icon.danger {
  background: #fff0ed;
  color: #b42318;
}

.confirm-icon.warning {
  background: #fff7d6;
  color: #a66a00;
}

.confirm-copy h2 {
  margin: 0;
  color: #171717;
  font-size: 1.05rem;
}

.confirm-copy p {
  margin: 6px 0 0;
  color: #707070;
  font-size: 0.9rem;
}

.confirm-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>
