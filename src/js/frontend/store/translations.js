import { defineStore } from 'pinia'

export const useTranslationsStore = defineStore('translations', {
  state: () => ({
    translations: window.dailyMenuAjax.translations || {}
  }),
  actions: {
    t(key) {
      return this.translations[key] || key
    }
  }
})