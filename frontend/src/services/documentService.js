import api from "./api";

export const getDocuments = async (params = {}) => {
  const response = await api.get("/documents", { params });
  return response.data;
};

export const getDocument = async (id) => {
  const response = await api.get(`/documents/${id}`);
  return response.data;
};

export const downloadDocumentFile = async (documentItem) => {
  const response = await api.get(`/documents/${documentItem.id}/download`, {
    responseType: "blob",
  });

  const contentType = response.headers["content-type"] || documentItem.mime_type || "application/octet-stream";
  const blob = response.data instanceof Blob
    ? response.data
    : new Blob([response.data], { type: contentType });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");

  link.href = url;
  link.download = documentItem.file_name || `${documentItem.title || "document"}`;
  document.body.appendChild(link);
  link.click();
  link.remove();

  URL.revokeObjectURL(url);
};

export const getFavoriteDocuments = async () => {
  const response = await api.get("/favorites");
  return response.data;
};

export const getDocumentMetadata = async () => {
  const response = await api.get("/documents/metadata");
  return response.data;
};

export const uploadDocument = async (formData) => {
  const response = await api.post("/documents", formData);
  return response.data;
};

export const approveDocument = async (id, status) => {
  const response = await api.patch(`/documents/${id}/approval`, { status });
  return response.data;
};

export const updateDocumentFile = async (id, formData) => {
  const response = await api.post(`/documents/${id}/update-file`, formData);
  return response.data;
};

export const toggleFavoriteDocument = async (id) => {
  const response = await api.post(`/documents/${id}/favorite`);
  return response.data;
};

export const deleteDocument = async (id) => {
  await api.delete(`/documents/${id}`);
};

export const getFolders = async () => {
  const response = await api.get("/folders");
  return response.data;
};

export const getCategoryFolders = async () => {
  const response = await api.get("/folders/categories");
  return response.data;
};

export const createFolder = async (payload) => {
  const response = await api.post("/folders", payload);
  return response.data;
};

export const updateFolder = async (id, payload) => {
  const response = await api.patch(`/folders/${id}`, payload);
  return response.data;
};

export const deleteFolder = async (id) => {
  await api.delete(`/folders/${id}`);
};
