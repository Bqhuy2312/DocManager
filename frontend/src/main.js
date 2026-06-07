import { createApp } from "vue";
import "bootstrap/dist/css/bootstrap.min.css";
import "bootstrap/dist/js/bootstrap.bundle.min.js";
import "@fortawesome/fontawesome-free/css/all.min.css";
import "./style.css";
import App from "./App.vue";
import router from "./router";
import { notify } from "./services/notificationService";

window.alert = (message) => {
  const cleanMessage = String(message ?? "").replace(/<[^>]*>/g, "").trim();
  notify({
    title: "Thông báo",
    message: cleanMessage,
    type: cleanMessage.toLowerCase().includes("không") ? "danger" : "success",
  });
};

createApp(App).use(router).mount("#app");
