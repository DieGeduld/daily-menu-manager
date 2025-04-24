<template>
  <div 
  :data-item-available_quantity="availableQuantity" 
  :class="{'menu-item': true, 'soldout': availableQuantity == 0}" 
  :data-item-id="itemId">
    <div class="menu-item-header" v-if="availableQuantity > 0">
      <span class="menu-item-title">{{ title }} ({{ availableQuantity }} available)</span>
      <span class="menu-item-price">{{ price }} €</span>
    </div>
    
    <div class="menu-item-header soldout" v-else>
      <div class="left"><span class="menu-item-title">{{ title }}</span><span class="badge badge-secondary">{{ translations.soldout }}</span></div>
      <span class="menu-item-price">{{ price }} €</span>
    </div>

    <div class="menu-item-footer">
      <p class="menu-item-description">
        {{ description }}
      </p>
      <div class="menu-item-order">
        <div class="quantity-control">
          <button type="button" class="quantity-btn minus" @click="decreaseQuantity" :disabled="!getQuantity">-</button>
          <div class="quantity" v-text="getQuantity"></div>  
          <button type="button" class="quantity-btn plus" @click="increaseQuantity" :disabled="availableQuantity === 0 || getQuantity >= availableQuantity">+</button>
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
import { useStore } from 'vuex'
import { ref, computed, watch } from 'vue'

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
    },
    translations: {
      type: Object,
      default: () => ({})
    }
  },
  setup(props) {
    const store = useStore()
    const notes = ref('')
    const showNotes = ref(false)
    
    // Getter für die Artikelmenge aus dem Store
    const getQuantity = computed(() => {
      return store.getters.getItemQuantity(props.itemId)
    })
    
    // Menge erhöhen
    const increaseQuantity = () => {
      if (getQuantity.value < props.availableQuantity) {
        store.dispatch('updateItemQuantity', {
          itemId: props.itemId,
          quantity: getQuantity.value + 1,
          notes: notes.value,
          price: props.price,
          title: props.title
        })
      }
    }
    
    // Menge verringern
    const decreaseQuantity = () => {
      if (getQuantity.value > 0) {
        store.dispatch('updateItemQuantity', {
          itemId: props.itemId,
          quantity: getQuantity.value - 1,
          notes: notes.value,
          price: props.price,
          title: props.title
        })
      }
    }
    
    // Beobachter für Quantity (zeigt Notizfeld)
    watch(getQuantity, (newValue) => {
      showNotes.value = newValue > 0
    })
    
    // Beobachter für Notizen
    watch(notes, (newValue) => {
        store.dispatch('updateItemQuantity', {
          itemId: props.itemId,
          quantity: getQuantity.value,
          notes: newValue,
          price: props.price,
          title: props.title
        })
    })
    
    return {
      notes,
      showNotes,
      getQuantity,
      increaseQuantity,
      decreaseQuantity
    }
  }
}
</script>

<style scoped>

.menu-item {
  border-bottom: 1px solid #eee;
  padding: 10px 0;
}

.menu-item-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
  font-size: 16px;
}

.badge {
  align-self: center;
  background: #E74551;
}

.menu-item-header.soldout {
  color: #b1b1b1;
  .menu-item-price {
    color: #b1b1b1;
  }
  .menu-item-order {
    opacity: 0.3;
  }
}

.menu-item-header.soldout .left {
  display: flex;
  gap: 10px;
}

.menu-item-header.soldout .menu-item-title {
  text-decoration: line-through;
}

.menu-item-header.soldout .menu-item-price {
  text-decoration: line-through;
}

.menu-item-title {
  font-weight: bold;
  font-size: 18px;
}

.menu-item-price {
  font-weight: bold;
  color: #222;
}

.menu-item-description {
  color: #666;
  margin-bottom: 8px;
  font-size: 14px;
}

.menu-item-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.quantity-control {
  display: flex;
  align-items: center;
  gap: 0px;
  border: 1px solid #666;
  border-radius: 20px;
  overflow: hidden;

  .quantity-btn {
    width: 25px;
    height: 30px;
    border: none;
    color: white;
    font-weight: bold;
    cursor: pointer;
    user-select: none;
    padding-bottom: 2px;
    color: #333;
    &:not(:disabled)&:hover {
      color: white;
      background: #e74551;
    } 
  }

  .quantity-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
  }
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
  box-shadow: none;
}

.quantity {
  font-size: 1.2em;
  font-weight: bold;
  text-align: center;
  padding: 0 3px;
}
</style>