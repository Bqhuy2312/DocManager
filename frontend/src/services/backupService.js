import api from "./api";

export const getBackups = async () => {
  const response = await api.get("/backups");
  return response.data;
};

export const createBackup = async (type) => {
  const response = await api.post("/backups", { type });
  return response.data;
};

export const deleteBackup = async (id) => {
  const response = await api.delete(`/backups/${id}`);
  return response.data;
};

export const restoreBackup = async (file) => {
  const formData = new FormData();
  formData.append("file", file);

  const response = await api.post("/backups/restore", formData);
  return response.data;
};

export const downloadBackup = async (backup) => {
  const response = await api.get(`/backups/${backup.id}/download`, {
    responseType: "blob",
  });

  const url = window.URL.createObjectURL(new Blob([response.data]));
  const link = document.createElement("a");
  link.href = url;
  link.download = backup.file_name || "backup.zip";
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.URL.revokeObjectURL(url);
};
