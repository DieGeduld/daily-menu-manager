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
      currentMenuId: window.dailyMenuAjax.currentMenuId || null, // Hier initialisieren wir mit dem globalen Wert
    };
  },

  getters: {
    totalPrice(state) {
      return state.cartItems
        .reduce((total, item) => {
          return total + item.price * item.quantity;
        }, 0)
        .toFixed(2); // + ' €'
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
    UPDATE_ITEM_QUANTITY(
      state,
      { itemId, quantity, notes = '', price, title, menuId, menuItemId },
    ) {
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
          menuId: menuId || state.currentMenuId, // Verwende übergebene menuId oder die aktuelle
          menuItemId: menuItemId || itemId, // Verwende übergebene menuItemId oder itemId als Fallback
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

    SET_CURRENT_MENU_ID(state, menuId) {
      state.currentMenuId = menuId;
    },

    RESET_CART(state) {
      state.cartItems = [];
      state.customerInfo = {
        name: '',
        phone: '',
        consumptionType: 'pickup',
        pickupTime: '',
        notes: '',
      };
      // Behalte die currentMenuId
    },
  },

  actions: {
    updateItemQuantity({ commit }, payload) {
      commit('UPDATE_ITEM_QUANTITY', payload);
    },

    updateCustomerInfo({ commit }, info) {
      commit('UPDATE_CUSTOMER_INFO', info);
    },

    setCurrentMenuId({ commit }, menuId) {
      commit('SET_CURRENT_MENU_ID', menuId);
    },

    placeOrder({ commit, state, getters }) {
      if (getters.isValidOrder) {
        // Stelle sicher, dass die menuId in jedem Item gesetzt ist
        const cartItemsWithMenuId = state.cartItems.map((item) => ({
          ...item,
          menuId: item.menuId || state.currentMenuId || parseInt(window.currentMenuId, 10) || 0,
        }));

        // Prepare order data from state
        const orderData = {
          action: 'submit_order',
          nonce: window.dailyMenuAjax.nonce,
          items: JSON.stringify(cartItemsWithMenuId),
          customerInfo: JSON.stringify(state.customerInfo),
          menuId:
            state.currentMenuId || window.currentMenuId || $('#daily-menu-app').data('menu-id'),
          totalPrice: getters.totalPrice,
        };

        console.log('Sending order data:', orderData);

        $.ajax({
          url: dailyMenuAjax.ajaxurl,
          type: 'POST',
          data: orderData,
          success: function (response) {
            if (response.success) {
              // Build order details
              let detailsHtml =
                '<h4>Bestellte Gerichte:</h4><ul style="text-align: left; list-style-type: none; padding-left: 0;">';
              response.data.items.forEach(function (item) {
                detailsHtml += `<li style="margin-bottom: 10px;">
                          ${item.quantity}x ${item.title} (${(item.price * item.quantity).toFixed(
                  2,
                )} ${window.dailyMenuAjax.currencySymbol})`;
                if (item.notes) {
                  detailsHtml += `<br><small>Anmerkung: ${item.notes}</small>`;
                }
                detailsHtml += '</li>';
              });
              detailsHtml += '</ul>';
              detailsHtml += `<p><strong>Gesamtbetrag: ${response.data.total_amount.toFixed(
                2,
              )}&nbsp;${window.dailyMenuAjax.currencySymbol}</strong></p>`;

              Swal.fire({
                title: 'Bestellung erfolgreich aufgegeben!',
                html: `
                          <p>Ihre Bestellnummer: <strong>${response.data.order_number}</strong></p>
                          <p>Bitte nennen Sie diese Nummer bei der Abholung.</p>
                          ${detailsHtml}
                      `,
                icon: 'success',
                confirmButtonText: 'Schließen',
              }).then((result) => {
                // Always reload
                location.reload();
              });
            } else {
              Swal.fire({
                title: dailyMenuAjax.messages.orderError,
                icon: 'error',
                confirmButtonText: 'OK',
              });
            }
          },
          error: function () {
            Swal.fire({
              title: dailyMenuAjax.messages.orderError,
              icon: 'error',
              confirmButtonText: 'OK',
            });
          },
          complete: function () {
            $('.submit-order').prop('disabled', false).text('Bestellung aufgeben');
          },
        });

        // Reset cart after successful order
        commit('RESET_CART');
      } else {
        console.log('Invalid order, please check your information');
      }
    },
  },
});
