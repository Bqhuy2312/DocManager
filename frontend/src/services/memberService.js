import api from "./api";

export const getMembers = async () => {
  const response = await api.get("/users");
  return response.data;
};

export const createMember = async (payload) => {
  const response = await api.post("/users", payload);
  return response.data;
};

export const getMember = async (id) => {
  const response = await api.get(`/users/${id}`);
  return response.data;
};

export const updateMember = async (id, payload) => {
  const response = await api.patch(`/users/${id}`, payload);
  return response.data;
};

export const deleteMember = async (id) => {
  await api.delete(`/users/${id}`);
};
