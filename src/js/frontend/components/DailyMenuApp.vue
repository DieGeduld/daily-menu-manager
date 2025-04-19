<template>
  <div id="menu-order-form" class="daily-menu-manager">
    <div class="menu-layout">
      <div class="menu-items-column">
        <h2>{{ title }} - {{ formattedDate }}</h2>
        <p>Menü ID: {{ menuId }}</p>
        <p v-if="loading">Menü wird geladen...</p>
        <div v-else>
          <div class="menu-items-column">
            <p v-if="error">{{ error }}</p>
            <p v-else-if="menuItems.length === 0">Keine Menüpunkte verfügbar.</p>
            <div v-else class="menu-items-list">
              <menu-item
                v-for="item in menuItems"
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
      <div class="order-summary-column">
        <h2>Bestellübersicht</h2>
        <p>Hier wird die Bestellübersicht angezeigt.</p>
        <OrderInfo/>
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
    }
  },
  data() {
    return {
      loading: true,
      error: null,
      menuItems: []
    };
  },
  computed: {
    formattedDate() {
      if (!this.menuDate) return '';
      
      const date = new Date(this.menuDate);
      return formatDate(date, window.dailyMenuAjax.dateFormat);
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
        this.menuItems = await response || [];

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

.menu-items-list {
  padding: 0;
  margin: 20px 0;
}
</style>