import { useTranslationsStore } from '../store/translations'

export const i18nPlugin = {
  install: (app) => {
    app.config.globalProperties.$t = function(key) {
      const translations = useTranslationsStore()
      return translations.t(key)
    }
  }
}