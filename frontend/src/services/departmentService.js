import api from "./api";

export const getDepartments = async () => {
  const response = await api.get("/departments");
  return response.data;
};

export const createDepartment = async (payload) => {
  const response = await api.post("/departments", payload);
  return response.data;
};

export const updateDepartment = async (id, payload) => {
  const response = await api.patch(`/departments/${id}`, payload);
  return response.data;
};

export const deleteDepartment = async (id) => {
  await api.delete(`/departments/${id}`);
};
