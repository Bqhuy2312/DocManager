import api from "./api";

export const login = async (email, password, twoFactorCode = "") => {
  const payload = { email, password }
  if (twoFactorCode) payload.two_factor_code = twoFactorCode

  const response = await api.post('/login', payload)
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
