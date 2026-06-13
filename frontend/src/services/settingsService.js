import api from "./api";

export const getSettings = async () => {
  const response = await api.get("/settings");
  return response.data;
};

export const updateProfile = async (profile) => {
  const response = await api.patch("/me/profile", profile);
  return response.data;
};

export const updateSettings = async (settings) => {
  const response = await api.patch("/me/settings", settings);
  return response.data;
};

export const updatePassword = async (password) => {
  const response = await api.patch("/me/password", password);
  return response.data;
};
