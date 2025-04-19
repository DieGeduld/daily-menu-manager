<template>
  <div class="order-info-column">
    <div class="order-summary">
          <h3>{{ translations.order_summary || 'Order Summary' }}</h3>
          <div class="order-total">
              {{ translations.order_total || 'Total Amount' }}:
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
                  {{ translations.phonenumber || 'Phone Number' }}*
                  {{ translations.for_possible_inquiries || '(For possible inquiries)' }}
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
              <!-- <select name="consumption_type" id="consumption_type" required>
                  <option value=""><?php _e('Please choose', 'daily-menu-manager'); ?></option>
                  <?php foreach (SettingsController::getConsumptionTypes() as $type): ?>
                      <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></option>
                  <?php endforeach; ?>
              </select> -->
          </div>
          <div class="form-field">
              <label for="pickup_time">
                {{ translations.pickup_time || "Pickup time" }}*
              </label>
              <!-- <select name="pickup_time" id="pickup_time" required>
                  <option value=""><?php _e('Please choose', 'daily-menu-manager'); ?></option>
                  <?php
                  $timeFormat = SettingsController::getTimeFormat();
                  foreach (self::getAvailablePickupTimes() as $time) {
                      $timeFormatted = SettingsController::formatTime($time);
                      printf(
                          '<option value="%s">%s</option>',
                          esc_attr($time),
                          esc_html($timeFormatted)
                      );
                  }
              </select> -->
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
import { getMenuItems } from '../services/api.js';
import { formatDate } from '../../common/helper.js';
import MenuItem from './MenuItem.vue';

export default {
  name: 'DailyMenuApp',
  components: {
    MenuItem // Registriere die Komponente
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
    translations: {
      type: Object,
      default: '{}'
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

    }
  },
  mounted() {
    console.log('DailyMenuApp wurde geladen mit Menü ID:', this.menuId);
    //this.fetchMenuItems();
    this.translations = {
      order_summary: 'Bestellübersicht',
      order_total: 'Gesamtbetrag',
      name: 'Name',
      phonenumber: 'Telefonnummer',
      for_possible_inquiries: '(Für eventuelle Rückfragen)',
      pickup_or_eat_in: 'Abholung oder Vor Ort Essen',
      pickup_time: 'Abholzeit',
      order_notes: 'Bestellnotizen',
      place_order: 'Bestellung aufgeben'
    };
  },
  methods: {
    // async fetchMenuItems() {
    //   try {
    //     this.loading = true;

    //     const response = await getMenuItems();
    //     this.menuItems = await response || [];

    //   } catch (error) {
    //     console.error('Fehler:', error);
    //     this.error = 'Das Menü konnte nicht geladen werden. Bitte versuchen Sie es später erneut.';
    //   } finally {
    //     this.loading = false;
    //   }
    // }
  }
};
</script>

<style lang="scss" scoped>

</style>