import api from "./api";

export const getDashboard = async () => {
  const response = await api.get("/dashboard");
  return response.data;
};

export const getDashboardActivities = async (params = {}) => {
  const response = await api.get("/dashboard/activities", { params });
  return response.data;
};
