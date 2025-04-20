<template>
  <div class="order-info-column">
    <div class="order-summary">
          <h3>{{ translations.orderSummary || 'Order Summary' }}</h3>
          <div class="order-total">
              {{ translations.total || 'Total Amount' }}:
              <span id="total-amount">{{ totalPrice }}</span>
          </div>
      </div>

      <div class="customer-info">
          <div class="form-field">
              <label for="customer_name">
                  {{ translations.name || 'Name' }}*
              </label>
              <input type="text" name="customer_name" id="customer_name" required>
          </div>
          <div class="form-field">
              <label for="customer_phone">
                  {{ translations.phoneNumber || 'Phone Number' }}*
                  {{ translations.forPossibleInquiries || '(For possible inquiries)' }}
              </label>
              <input type="tel" 
                  name="customer_phone" 
                  id="customer_phone" 
                  pattern="[0-9\s\+\-()]+"
                  :placeholder="translations.phonenumber_example || 'e.g. (555) 123-4567'">
          </div>

          <div class="form-field">
              <label for="consumption_type">
                {{ translations.pickup_or_eat_in || 'Pickup or Eat in' }}*
              </label>
              <!-- Consumption type select here -->
          </div>
          <div class="form-field">
              <label for="pickup_time">
                {{ translations.pickup_time || "Pickup time" }}*
              </label>
              <!-- Pickup time select here -->
          </div>
          <div class="form-field">
              <label for="general_notes">
                  {{ translations.order_notes || 'Order Notes' }}
              </label>
              <textarea name="general_notes" id="general_notes"></textarea>
          </div>
      </div>

      <button type="submit" class="submit-order" :style="{ background: '#cc1939' }">
        {{ translations.place_order || 'Place Order' }}  
      </button>
  </div>
</template>

<script>
export default {
  name: 'OrderInfo',
  props: {
    translations: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      totalPrice: 0
    };
  },
  methods: {
    calculateTotalPrice() {
      this.totalPrice = 0.00.toFixed(2);
    }
  },
  mounted() {
    this.calculateTotalPrice();
  }
};
</script>

<style lang="scss" scoped>
.order-info-column {
  padding: 20px;
  background: #f9f9f9;
  border-radius: 5px;
  
  .order-summary {
    margin-bottom: 20px;
    
    h3 {
      margin-top: 0;
    }
    
    .order-total {
      font-weight: bold;
      margin-top: 10px;
    }
  }
  
  .customer-info {
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
  }
}
</style>