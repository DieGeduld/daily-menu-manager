<template>
  <div id="menu-order-form" class="daily-menu-manager">
    <div class="menu-layout">
      <div class="menu-items-column">
        <h2 class="menu-title">{{ title }} - {{ formattedDate }}</h2>

        <p class="loading" v-if="loading">Menü wird geladen...</p>
        <div v-else>
            <p v-if="error">{{ error }}</p>
            <p v-else-if="currentMenudata.grouped_items && currentMenudata.grouped_items.length === 0">Keine Menüpunkte verfügbar.</p>

            <div v-else class="menu-items-list">
              <div v-for="(items, groupName) in currentMenudata.grouped_items" :key="groupName" class="menu-group">
                <h2 class="menu-group-title text-xl font-semibold">{{ groupName }}</h2>
                <menu-item
                  v-for="item in items"
                  :key="item.id"
                  :item-id="item.id"
                  :title="item.title"
                  :description="item.description"
                  :price="item.price"
                  :available-quantity="item.available_quantity || 0"
                  :translations="translations"
                />
              </div>
            </div>
        </div>
      </div>
      <div class="order-summary-column">
        <order-info :translations="translations" />
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { getMenuItems } from '../services/api.js'
import { formatDate } from '../../common/helper.js'
import MenuItem from './MenuItem.vue'
import OrderInfo from './OrderInfo.vue'

export default {
  name: 'DailyMenuApp',
  components: {
    MenuItem,
    OrderInfo
  },
  props: {
    menuId: {
      type: [String, Number],
      required: true
    },
    menuDate: {
      type: String,
      required: true
    },
    title: {
      type: String,
      default: 'Tagesmenü'
    },
  },
  setup(props) {
    const loading = ref(true)
    const error = ref(null)
    const currentMenudata = ref({})
    const translations = ref(window.dailyMenuAjax?.translations || {})
    
    // Formatiertes Datum
    const formattedDate = computed(() => {
      if (!props.menuDate) return ''
      
      const date = new Date(props.menuDate)
      const dateFormat = window.dailyMenuAjax?.dateFormat || 'DD.MM.YYYY'
      return formatDate(date, dateFormat)
    })
    
    // Menüpunkte laden
    const fetchMenuItems = async () => {
      try {
        loading.value = true
        const response = await getMenuItems()
        currentMenudata.value = response || {}
      } catch (err) {
        console.error('Fehler:', err)
        error.value = 'Das Menü konnte nicht geladen werden. Bitte versuchen Sie es später erneut.'
      } finally {
        loading.value = false
      }
    }
    
    // Komponente initialisieren
    onMounted(() => {
      console.log('DailyMenuApp wurde geladen mit Menü ID:', props.menuId)
      fetchMenuItems()
    })
    
    return {
      loading,
      error,
      currentMenudata,
      translations,
      formattedDate
    }
  }
}
</script>

<style scoped>
@import '../../../fonts/SourceSans3.css';

.daily-menu-manager {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem;
  font-family: 'Source Sans 3', sans-serif;
  background-color: #F5F6F6;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  color: #1f2937;
  font-size: 0.875rem;
}

.loading {
  padding: 1rem 0;
  color: #6b7280;
  font-style: italic;
}

.menu-layout {
  display: flex;
  gap: 1.25rem;
}

.menu-items-column {
  flex: 2;
  background: white;
  border-radius: 0.5rem;
  box-shadow: 3px 3px 7px #00000022;
}

h2.menu-title {
  padding: 20px 20px 0;
}

h2.menu-group-title {
  padding: 10px 20px;
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: #E74551;

}


.order-summary-column {
  flex: 1;
  background: #F5F6F6;
}


.order-info-column {
  box-shadow: 3px 3px 7px #00000022;
  background: #fff;
}


.order-summary-column h2 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 1rem;
}

.menu-items-list {
  padding: 0;
  margin: 1.25rem 0;
}

.menu-item {
  padding: 10px 20px;
  border-bottom: 1px solid #c6c6c6;
  &:nth-child(even) {
    background: #F5F6F7;
  }
  /* &:hover {
    background: #ededed;
  } */
  &:nth-child(1) {
    border-top: 1px solid #c6c6c6
  }
}


.menu-group {
  &:not(:last-child) {
    margin-bottom: 1.5rem;
  }
}

@media (max-width: 768px) {
  .menu-layout {
    flex-direction: column;
  }
}
</style>