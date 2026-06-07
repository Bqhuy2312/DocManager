import { reactive } from "vue";

export const notificationState = reactive({
  toasts: [],
  confirm: null,
});

let toastId = 0;

export const notify = ({ title = "Thông báo", message = "", type = "success", duration = 3200 } = {}) => {
  const id = ++toastId;
  notificationState.toasts.push({ id, title, message, type });

  if (duration) {
    window.setTimeout(() => {
      dismissToast(id);
    }, duration);
  }
};

export const dismissToast = (id) => {
  notificationState.toasts = notificationState.toasts.filter((toast) => toast.id !== id);
};

export const confirmDialog = ({
  title = "Xác nhận thao tác",
  message = "Bạn có chắc chắn muốn tiếp tục?",
  confirmText = "Xác nhận",
  cancelText = "Hủy",
  tone = "danger",
} = {}) => {
  return new Promise((resolve) => {
    notificationState.confirm = {
      title,
      message,
      confirmText,
      cancelText,
      tone,
      resolve,
    };
  });
};

export const closeConfirm = (result = false) => {
  if (!notificationState.confirm) return;
  const { resolve } = notificationState.confirm;
  notificationState.confirm = null;
  resolve(result);
};
