<template>
  <div id="menu-order-form" class="daily-menu-manager">
    <div class="menu-layout">
      <div class="menu-items-column">
        <h2>{{ title }} - {{ formattedDate }}</h2>

        <p v-if="loading">Menü wird geladen...</p>
        <div v-else>
            <p v-if="error">{{ error }}</p>
            <p v-else-if="this.currentMenudata.grouped_items.length === 0">Keine Menüpunkte verfügbar.</p>

            <div v-else class="menu-items-list">

              <div v-for="(items, groupName) in this.currentMenudata.grouped_items" class="menu-group">
                <h2 class="menu-group-title text-xl font-semibold mb-4">{{ groupName }}</h2>
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
      currentMenudata: [],
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
        this.currentMenudata = response || [];

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

@import '../../../fonts/SourceSans3.css';

.daily-menu-manager {
  @apply mx-auto p-4 font-source-sans bg-white rounded-lg shadow-md border border-gray-200 text-gray-800 text-sm;
  .menu-items-column {
    h2 {
      @apply text-2xl text-gray-800 font-source-sans;
      @apply font-semibold;
      @apply mb-4;
    }
  }
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

/* Modified styles for menu-group */
.menu-group {
  margin-bottom: 25px;
  background-color: white; /* Direct CSS instead of @apply */
}

.menu-group-title {
  border-bottom: 2px solid #eaeaea;
  padding-bottom: 8px;
  margin-bottom: 15px;
  color: #333;
}
</style>