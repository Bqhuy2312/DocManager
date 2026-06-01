import { createRouter, createWebHistory } from "vue-router";
import Login from "../pages/Login.vue";
import Dashboard from "../pages/Dashboard.vue";
import AllDocuments from "../pages/AllDocuments.vue";
import Favorites from "../pages/Favorites.vue";
import Categories from "../pages/Categories.vue";
import Folders from "../pages/Folders.vue";
import Upload from "../pages/Upload.vue";
import Members from "../pages/Members.vue";
import Settings from "../pages/Settings.vue";
import DocumentDetail from "../pages/DocumentDetail.vue";

const routes = [
  { path: "/", redirect: "/login" },
  { path: "/login", name: "Login", component: Login },
  { path: "/dashboard", name: "Dashboard", component: Dashboard },
  { path: "/documents", name: "AllDocuments", component: AllDocuments },
  { path: "/documents/:id", name: "DocumentDetail", component: DocumentDetail },
  { path: "/favorites", name: "Favorites", component: Favorites },
  { path: "/categories", name: "Categories", component: Categories },
  { path: "/folders", name: "Folders", component: Folders },
  { path: "/upload", name: "Upload", component: Upload },
  { path: "/members", name: "Members", component: Members },
  { path: "/settings", name: "Settings", component: Settings },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
