<template>
  <div class="menu-item" :data-item-available_quantity="availableQuantity" :data-item-id="itemId">
    <div class="menu-item-header">
      <span class="menu-item-title">{{ title }} ({{ availableQuantity }} available)</span>
      <span class="menu-item-price">{{ price }} €</span>
    </div>
    <div class="menu-item-footer">
      <p class="menu-item-description">
        {{ description }}
      </p>
      <div class="menu-item-order">
        <div class="quantity-control">
          <!-- <label :for="'quantity_' + itemId">Menge:</label> -->
          <button type="button" style="background: #cc1939" class="quantity-btn minus" @click="decreaseQuantity">-</button>
          <div class="quantity" v-text="quantity"></div>  
          <button type="button" style="background: #cc1939" class="quantity-btn plus" @click="increaseQuantity">+</button>
        </div>
      </div>
    </div>
    <div class="item-notes" :class="{ 'show-notes': showNotes }">
      <label :for="'notes_' + itemId">Hinweise:</label>
      <input 
        type="text" 
        :name="'items[' + itemId + '][notes]'" 
        :id="'notes_' + itemId" 
        placeholder="z.B. ohne Zwiebeln"
        v-model="notes"
      >
    </div>
  </div>
</template>

<script>
export default {
  name: 'MenuItem',
  props: {
    itemId: {
      type: [String, Number],
      required: true
    },
    title: {
      type: String,
      required: true
    },
    description: {
      type: String,
      default: ''
    },
    price: {
      type: [String, Number],
      required: true
    },
    availableQuantity: {
      type: Number,
      default: 0
    }
  },
  data() {
    return {
      quantity: 0,
      notes: '',
      showNotes: false
    }
  },
  methods: {
    increaseQuantity() {
      if (this.quantity < this.availableQuantity) {
        this.quantity++;
        this.showNotes = this.quantity > 0;
        this.$emit('quantity-change', {
          itemId: this.itemId,
          quantity: this.quantity
        });
      }
    },
    decreaseQuantity() {
      if (this.quantity > 0) {
        this.quantity--;
        this.showNotes = this.quantity > 0;
        this.$emit('quantity-change', {
          itemId: this.itemId,
          quantity: this.quantity
        });
      }
    }
  },
  mounted() {
    console.log(`MenuItem ${this.itemId} wurde geladen.`);
  }
}
</script>

<style lang="scss" scoped>
.menu-item {
  border-bottom: 1px solid #eee;
  padding: 15px 0;
}

.menu-item-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
}

.menu-item-title {
  font-weight: bold;
  font-size: 1.1em;
}

.menu-item-price {
  font-weight: bold;
  color: #222;
}

.menu-item-description {
  color: #666;
  margin-bottom: 8px;
}

.menu-item-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.quantity-control {
  display: flex;
  align-items: center;
  gap: 5px;
}

.quantity-btn {
  width: 30px;
  height: 30px;
  border: none;
  border-radius: 50%;
  color: white;
  font-weight: bold;
  cursor: pointer;
}

.quantity-input {
  width: 40px;
  text-align: center;
}

.item-notes {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.4s ease-in-out, margin-top 0.4s ease-in-out;
  margin-top: 0;
}

.item-notes.show-notes {
  max-height: 90px;
  margin-top: 10px;
}

.item-notes input {
  width: 100%;
  padding: 5px;
  margin-top: 5px;
}

.quantity {
  font-size: 1.2em;
  font-weight: bold;
  width: 30px;
  text-align: center;
}
</style>