import { applyLanguage } from "./languageService";

export const APP_SETTINGS_EVENT = "app-settings-updated";

const STORAGE_KEY = "docmanager_settings";

export const defaultAppSettings = {
  language: "vi",
  auto_save: true,
  dark_mode: false,
  timezone: "UTC+7",
  email_enabled: true,
  in_app_enabled: true,
  notify_upload: true,
  notify_edit: true,
  notify_approve: true,
  notify_system: true,
  two_factor_enabled: false,
};

export const normalizeAppSettings = (settings = {}) => ({
  ...defaultAppSettings,
  ...settings,
  auto_save: Boolean(settings.auto_save ?? defaultAppSettings.auto_save),
  dark_mode: Boolean(settings.dark_mode ?? defaultAppSettings.dark_mode),
  email_enabled: Boolean(settings.email_enabled ?? defaultAppSettings.email_enabled),
  in_app_enabled: Boolean(settings.in_app_enabled ?? defaultAppSettings.in_app_enabled),
  notify_upload: Boolean(settings.notify_upload ?? defaultAppSettings.notify_upload),
  notify_edit: Boolean(settings.notify_edit ?? defaultAppSettings.notify_edit),
  notify_approve: Boolean(settings.notify_approve ?? defaultAppSettings.notify_approve),
  notify_system: Boolean(settings.notify_system ?? defaultAppSettings.notify_system),
  two_factor_enabled: Boolean(settings.two_factor_enabled ?? defaultAppSettings.two_factor_enabled),
});

export const getStoredAppSettings = () => {
  try {
    return normalizeAppSettings(JSON.parse(localStorage.getItem(STORAGE_KEY)) || {});
  } catch {
    return { ...defaultAppSettings };
  }
};

export const applyAppSettings = (settings = {}) => {
  const normalized = normalizeAppSettings(settings);

  localStorage.setItem(STORAGE_KEY, JSON.stringify(normalized));
  applyLanguage(normalized.language);
  document.body.classList.toggle("theme-dark", normalized.dark_mode);
  window.docManagerSettings = normalized;
  window.dispatchEvent(new CustomEvent(APP_SETTINGS_EVENT, { detail: normalized }));

  return normalized;
};

export const isAutoSaveEnabled = () => getStoredAppSettings().auto_save;
