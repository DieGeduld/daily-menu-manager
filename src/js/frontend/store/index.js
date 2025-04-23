// src/store/index.js
import { createStore } from 'vuex';

export default createStore({
  state() {
    return {
      cartItems: [],
      customerInfo: {
        name: '',
        phone: '',
        consumptionType: 'pickup',
        pickupTime: '',
        notes: '',
      },
    };
  },

  getters: {
    totalPrice(state) {
      return (
        state.cartItems
          .reduce((total, item) => {
            return total + item.price * item.quantity;
          }, 0)
          .toFixed(2) + ' €'
      );
    },

    getItemQuantity: (state) => (itemId) => {
      const item = state.cartItems.find((item) => item.id == itemId);
      return item ? item.quantity : 0;
    },

    isValidOrder(state) {
      return (
        state.cartItems.length > 0 &&
        state.customerInfo.name &&
        state.customerInfo.phone &&
        state.customerInfo.pickupTime
      );
    },
  },

  mutations: {
    UPDATE_ITEM_QUANTITY(state, { itemId, quantity, notes = '', price, title }) {
      const existingItem = state.cartItems.find((item) => item.id == itemId);

      if (existingItem) {
        if (quantity > 0) {
          existingItem.quantity = quantity;
          if (notes) {
            existingItem.notes = notes;
          }
        } else {
          // Entferne Item bei Menge 0
          state.cartItems = state.cartItems.filter((item) => item.id != itemId);
        }
      } else if (quantity > 0) {
        // Füge neues Item hinzu
        state.cartItems.push({
          id: itemId,
          quantity,
          notes,
          price: parseFloat(price),
          title,
        });
      }
    },

    UPDATE_CUSTOMER_INFO(state, info) {
      state.customerInfo = { ...state.customerInfo, ...info };
    },

    RESET_CART(state) {
      state.cartItems = [];
    },
  },

  actions: {
    updateItemQuantity({ commit }, payload) {
      commit('UPDATE_ITEM_QUANTITY', payload);
    },

    updateCustomerInfo({ commit }, info) {
      commit('UPDATE_CUSTOMER_INFO', info);
    },

    placeOrder({ commit, state, getters }) {
      // Hier würde die Bestellung an den Server geschickt werden
      console.log('Bestellung aufgegeben:', {
        items: state.cartItems,
        customerInfo: state.customerInfo,
        totalPrice: getters.totalPrice,
      });

      // Bestellung zurücksetzen
      commit('RESET_CART');
      alert('Vielen Dank für Ihre Bestellung!');
    },
  },
});
