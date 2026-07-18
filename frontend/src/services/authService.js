import api from "./api";

const GUEST_DEVICE_KEY = 'guest_device_id'

const createGuestDeviceId = () => {
  if (window.crypto?.randomUUID) {
    return window.crypto.randomUUID()
  }

  return `guest-${Date.now()}-${Math.random().toString(36).slice(2, 12)}`
}

const getGuestDeviceId = () => {
  let guestDeviceId = localStorage.getItem(GUEST_DEVICE_KEY)

  if (!guestDeviceId) {
    guestDeviceId = createGuestDeviceId()
    localStorage.setItem(GUEST_DEVICE_KEY, guestDeviceId)
  }

  return guestDeviceId
}

export const login = async (email, password, twoFactorCode = "") => {
  const payload = { email, password }
  if (twoFactorCode) payload.two_factor_code = twoFactorCode

  const response = await api.post('/login', payload)
  localStorage.setItem('token', response.data.token)
  return response.data
}

export const register = async (payload) => {
  const response = await api.post('/register', payload)
  localStorage.setItem('token', response.data.token)
  return response.data
}

export const guestLogin = async () => {
  const response = await api.post('/guest-login', {
    guest_device_id: getGuestDeviceId(),
  })
  localStorage.setItem('token', response.data.token)
  return response.data
}

export const getDepartmentOptions = async () => {
  const response = await api.get('/departments/options')
  return response.data
}

export const logout = async () => {
  await api.post('/logout')
  localStorage.removeItem('token')
  localStorage.removeItem('user')
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
