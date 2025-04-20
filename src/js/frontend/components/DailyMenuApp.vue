<template>
  <div id="menu-order-form" class="daily-menu-manager">
    <div class="menu-layout">
      <div class="menu-items-column">
        <h2>{{ title }} - {{ formattedDate }}</h2>
        <p v-if="loading">Menü wird geladen...</p>
        <div v-else>
          <div class="menu-items-column">
            <p v-if="error">{{ error }}</p>
            <p v-else-if="menuItems.length === 0">Keine Menüpunkte verfügbar.</p>
            <div v-else class="menu-items-list">
              <!-- Gruppierte Menüanzeige -->
              <div v-for="(items, itemType) in groupedMenuItems" :key="itemType" class="menu-group">
                <!-- TODO: Adding Translations-->
                <h3 class="menu-group-title">{{ itemType }}</h3>
                <menu-item
                  v-for="item in items"
                  :key="item.id"
                  :item-id="item.id"
                  :title="item.title"
                  :description="item.description"
                  :price="item.price"
                  :available-quantity="item.available_quantity || 0"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="order-summary-column">
        <h2>Bestellübersicht</h2>
        <p>Hier wird die Bestellübersicht angezeigt.</p>
        <OrderInfo :translations="translations" />
      </div>
    </div>
  </div>
</template>

<script>
import { getMenuItems } from '../services/api.js';
import { formatDate } from '../../common/helper.js';
import MenuItem from './MenuItem.vue';
import OrderInfo from './OrderInfo.vue';

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
  data() {
    return {
      loading: true,
      error: null,
      menuItems: [],
      translations: window.dailyMenuAjax.translations
    };
  },
  created() {

  },
  computed: {
    formattedDate() {
      if (!this.menuDate) return '';
      
      const date = new Date(this.menuDate);
      const dateFormat = window.dailyMenuAjax.dateFormat || 'DD.MM.YYYY';
      return formatDate(date, dateFormat);
    },
    // Neu hinzugefügte computed property für gruppierte Menüpunkte
    groupedMenuItems() {
      const grouped = {};
      
      this.menuItems.forEach(item => {
        const itemType = item.item_type || 'Sonstiges'; // Fallback, falls kein item_type vorhanden ist
        
        if (!grouped[itemType]) {
          grouped[itemType] = [];
        }
        
        grouped[itemType].push(item);
      });
      
      return grouped;
    }
  },
  mounted() {
    console.log('DailyMenuApp wurde geladen mit Menü ID:', this.menuId);
    this.fetchMenuItems();
  },
  methods: {
    async fetchMenuItems() {
      try {
        this.loading = true;

        const response = await getMenuItems();
        this.menuItems = response || [];

      } catch (error) {
        console.error('Fehler:', error);
        this.error = 'Das Menü konnte nicht geladen werden. Bitte versuchen Sie es später erneut.';
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
.daily-menu-container {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
}

.menu-layout {
  display: flex;
  gap: 20px;
}

.menu-items-column {
  flex: 2;
}

.order-summary-column {
  flex: 1;
  background-color: #f5f5f5;
  padding: 15px;
  border-radius: 5px;
}

.menu-items-list {
  padding: 0;
  margin: 20px 0;
}

/* Neue Stile für die Gruppierung */
.menu-group {
  margin-bottom: 25px;
}

.menu-group-title {
  border-bottom: 2px solid #eaeaea;
  padding-bottom: 8px;
  margin-bottom: 15px;
  color: #333;
}
</style>