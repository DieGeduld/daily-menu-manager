import { defineStore } from 'pinia'

export const useSettingsStore = defineStore('settings', {
  state: () => ({
    timeFormat: window.dailyMenuAjax.timeFormat,
    priceFormat: window.dailyMenuAjax.priceFormat,
    currencySymbol: window.dailyMenuAjax.currencySymbol,
    messages: window.dailyMenuAjax.messages
  }),
  actions: {
    formatPrice(amount) {
      return `${amount.toFixed(2)} ${this.currencySymbol}`
    }
  }
})