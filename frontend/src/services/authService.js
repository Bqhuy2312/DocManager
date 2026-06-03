import api from "./api";

export const login = async (email, password) => {
  const response = await api.post('/login', { email, password })
  localStorage.setItem('token', response.data.token)
  return response.data
}

export const logout = async () => {
  await api.post('/logout')
  localStorage.removeItem('token')
}

export const getCurrentUser = async () => {
  const response = await api.get('/me')
  return response.data
}

export const uploadAvatar = async (avatar) => {
  const formData = new FormData()
  formData.append('avatar', avatar)
  const response = await api.post('/me/avatar', formData)
  return response.data
}
