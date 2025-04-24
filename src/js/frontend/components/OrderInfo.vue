<template>
  <div class="order-info-column">
    <div class="order-summary">
      <h3>{{ translations.orderSummary || 'Order Summary' }}</h3>
      
      <div v-if="cartItems.length > 0" class="order-items">
        <div v-for="item in cartItems" :key="item.id" class="order-item">
          <div class="item-details">
            <span class="item-quantity">{{ item.quantity }} x</span>
            <span class="item-title">{{ item.title }}</span>
            <span class="item-price">{{ (item.price * item.quantity).toFixed(2) }} €</span>
          </div>
          <div v-if="item.notes" class="item-notes">
            {{ item.notes }}
          </div>
        </div>
      </div>
      <div v-else class="empty-cart">
        {{ translations.cartEmpty || 'Your cart is empty' }}
      </div>
      
      <div class="order-total">
        {{ translations.total || 'Total Amount' }}:
        <span id="total-amount">{{ totalPrice }}</span>
      </div>
    </div>

    <div class="customer-info">
      <div class="form-field">
        <label for="customer_name">
          {{ translations.name || 'Name' }}<span class="asterisk">*</span>
        </label>
        <input 
          type="text" 
          id="customer_name" 
          v-model="customerInfo.name" 
          required
        >
      </div>
      
      <div class="form-field">
        <label for="customer_phone">
          {{ translations.phoneNumber || 'Phone Number' }}<span class="asterisk">*</span>
          {{ translations.forPossibleInquiries || '(For possible inquiries)' }}
        </label>
        <input 
          type="tel" 
          id="customer_phone" 
          v-model="customerInfo.phone" 
          pattern="[0-9\s\+\-()]+"
          :placeholder="translations.phonenumber_example || 'e.g. (555) 123-4567'"
          required
        >
      </div>

      <div class="form-field">
        <label>
          {{ translations.pickup_or_eat_in || 'Pickup or Eat in' }}<span class="asterisk">*</span>
        </label>
        <div class="button-group consumptionType-group">
          <button 
            type="button" 
            :class="{ active: customerInfo.consumptionType === 'pickup' }" 
            @click="customerInfo.consumptionType = 'pickup'"
          >
            {{ translations.pickup || 'Pickup' }}
          </button>
          <button 
            type="button" 
            :class="{ active: customerInfo.consumptionType === 'dine-in' }" 
            @click="customerInfo.consumptionType = 'dine-in'"
          >
            {{ translations.dine_in || 'Eat in' }}
          </button>
        </div>
      </div>
      
      <div class="form-field">
        <label for="pickup_time">
          {{ translations.pickup_time || "Pickup time" }}<span class="asterisk">*</span>
        </label>
        <select id="pickup_time" v-model="customerInfo.pickupTime" required>
          <option value="" disabled>{{ translations.select_pickup_time || "Select a pickup time" }}</option>
          <option value="09:00">09:00</option>
          <option value="10:00">10:00</option>
          <option value="11:00">11:00</option>
          <option value="12:00">12:00</option>
          <option value="13:00">13:00</option>
          <option value="14:00">14:00</option>
          <option value="15:00">15:00</option>
          <option value="16:00">16:00</option>
          <option value="17:00">17:00</option>
        </select>
      </div>
      
      <div class="form-field">
        <label for="general_notes">
          {{ translations.order_notes || 'Order Notes' }}
        </label>
        <textarea 
          id="general_notes" 
          v-model="customerInfo.notes"
        ></textarea>
      </div>
    </div>

    <button 
      type="button" 
      class="submit-order" 
      :style="{ background: '#E74551' }" 
      @click="submitOrder"
      :disabled="!isValidOrder"
    >
      {{ translations.place_order || 'Place Order' }}  
    </button>
  </div>
</template>

<script>
import { useStore } from 'vuex'
import { reactive, computed, watch, onMounted } from 'vue'

export default {
  name: 'OrderInfo',
  props: {
    translations: {
      type: Object,
      default: () => ({})
    }
  },
  setup(props) {
    const store = useStore()
    
    // Reaktives Objekt für Kundendaten
    const customerInfo = reactive({
      name: '',
      phone: '',
      consumptionType: 'pickup',
      pickupTime: '',
      notes: ''
    })
    
    // Berechnete Eigenschaften aus dem Store
    const cartItems = computed(() => store.state.cartItems)
    const totalPrice = computed(() => store.getters.totalPrice)
    const isValidOrder = computed(() => store.getters.isValidOrder)
    
    // Bestellung abschicken
    const submitOrder = () => {
      store.dispatch('updateCustomerInfo', customerInfo)
      store.dispatch('placeOrder')
    }
    
    // Beim Laden der Komponente Kundendaten aus dem Store holen
    onMounted(() => {
      if (store.state.customerInfo) {
        Object.assign(customerInfo, store.state.customerInfo)
      }
    })
    
    // Bei Änderungen der Kundendaten den Store aktualisieren
    watch(customerInfo, (newValue) => {
      store.dispatch('updateCustomerInfo', newValue)
    }, { deep: true })
    
    return {
      customerInfo,
      cartItems,
      totalPrice,
      isValidOrder,
      submitOrder
    }
  }
}
</script>

<style lang="scss" scoped>

.asterisk {
  color: #E74551;
}


.order-info-column {
  padding: 20px;
  border-radius: 5px;
  position: sticky;
  top: 20px;
  
  .order-summary {
    margin-bottom: 20px;
    
    h3 {
      margin-top: 0;
    }
    
    .order-items {
      margin-top: 10px;
      
      .order-item {
        padding: 8px 0;
        border-bottom: 1px solid #eee;
        
        .item-details {
          display: flex;
          justify-content: space-between;
          align-items: center;

          .item-title {
            text-overflow: ellipsis;
            flex: 1;
          }
          
          .quantity-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 0 10px;
          }
          
          .item-quantity {
            display: block;
            margin: 0 15px 0 0;
          }
        }
        
        .item-notes {
          font-size: 0.9em;
          color: #666;
          font-style: italic;
          margin-top: 5px;
        }
      }
    }
    
    .empty-cart {
      padding: 10px 0;
      color: #999;
      font-style: italic;
    }
    
    .order-total {
      font-weight: bold;
      margin-top: 15px;
      text-align: right;
    }
  }
  
  .customer-info {

    input, textarea {
      box-shadow: none;
    }
    
    .consumptionType-group {
      width: 100%;
      display: flex;
      justify-content: space-between;
      margin-bottom: 15px;
      button {
        width: 100%;
        padding: 10px;
        cursor: pointer;
        font-weight: bold;

        &:first-child {
          border-top-left-radius: 8px;
          border-bottom-left-radius: 8px;
          &:not(.active) {
            border-right: none;
          } 
        }
        
        &:last-child {
          border-top-right-radius: 8px;
          border-bottom-right-radius: 8px;
          &:not(.active) {
            border-left: none;
          } 
        }

        &.active {
          background: #E74551;
          color: white;
        } 
        &:not(.active) {
          color: #333;
          border: 1px solid #ddd;
          background: white;
        } 
      } 
    }
    
    .form-field {
      margin-bottom: 15px;
      
      label {
        display: block;
        margin-bottom: 5px;
      }
      
      input, select, textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 15px;
        // &[required] {
        //   border: 1px solid #E74551;
        // }

      }
      
      textarea {
        min-height: 80px;
      }
    }
  }
  
  .submit-order {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 4px;
    color: white;
    font-weight: bold;
    cursor: pointer;
    transition: opacity 0.2s;
    
    &:hover {
      opacity: 0.9;
    }
    
    &:disabled {
      background-color: #999 !important;
      cursor: not-allowed;
    }
  }
}
</style>