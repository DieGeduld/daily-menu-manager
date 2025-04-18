<template>
  <div class="daily-menu-container">
    <h2>{{ title }} - {{ formattedDate }}</h2>
    <p>Menü ID: {{ menuId }}</p>
    <p v-if="loading">Menü wird geladen...</p>
    <div v-else>
      <p v-if="error">{{ error }}</p>
      <p v-else-if="menuItems.length === 0">Keine Menüpunkte verfügbar.</p>
      <ul v-else class="menu-items-list">
        <li v-for="item in menuItems" :key="item.id" class="menu-item">
          <h3>{{ item.title }}</h3>
          <p class="description">{{ item.description }}</p>
          <p class="price">{{ item.price }} €</p>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>

import { getMenuItems } from '../services/api.js';
import { formatDate } from '../../common/helper.js';

export default {
  name: 'DailyMenuApp',
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
    },

  },
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
  list-style: none;
  padding: 0;
  margin: 20px 0;
}

.menu-item {
  border-bottom: 1px solid #eee;
  padding: 15px 0;
}

.menu-item h3 {
  margin-top: 0;
  margin-bottom: 8px;
}

.description {
  color: #666;
  margin-bottom: 8px;
}

.price {
  font-weight: bold;
  color: #222;
}
</style>