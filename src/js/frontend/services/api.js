/**
 * Holt die Menüelemente für ein bestimmtes Datum
 *
 * @returns {Promise<Array>} - Die Menüelemente
 */
export async function getMenuItems() {
  try {
    const formData = new FormData();
    formData.append('action', 'get_current_menu');
    formData.append('_ajax_nonce', window.dailyMenuAjax.nonce);

    const response = await fetch(window.dailyMenuAjax.ajaxurl, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
    });

    const data = await response.json();

    if (!data.success) {
      throw new Error(data.data || 'Error loading menu items');
    }

    return data.data || [];
  } catch (error) {
    console.error('API Error:', error);
    throw error;
  }
}

/**
 * Aktualisiert die verfügbaren Mengen der Menüelemente
 *
 * @param {number|string} menuId - Die ID des Menüs
 * @param {string} ajaxUrl - WordPress AJAX URL
 * @param {string} nonce - Sicherheits-Token
 * @returns {Promise<Object>} - Objekt mit Item-IDs als Schlüssel und verfügbaren Mengen als Werte
 */
export async function getAvailableQuantities() {
  try {
    const formData = new FormData();
    formData.append('action', 'get_available_quantities');
    formData.append('_ajax_nonce', window.dailyMenuAjax.nonce);

    const response = await fetch(window.dailyMenuAjax.ajaxurl, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
    });

    const data = await response.json();

    if (!data.success) {
      throw new Error(data.data || 'Error loading available quantities');
    }

    return data.data.quantities || {};
  } catch (error) {
    console.error('API Error:', error);
    throw error;
  }
}

/**
 * Sendet eine Bestellung ab
 *
 * @param {Object} orderData - Die Bestelldaten
 * @returns {Promise<Object>} - Die Antwortdaten
 */
export async function submitOrder(orderData) {
  try {
    const formData = new FormData();
    formData.append('action', 'submit_order');
    formData.append('menu_id', orderData.menu_id);
    formData.append('_ajax_nonce', window.dailyMenuAjax.nonce);
    formData.append('customer_name', orderData.customer_name);
    formData.append('customer_phone', orderData.customer_phone);
    formData.append('pickup_time', orderData.pickup_time);
    formData.append('notes', orderData.notes);

    // Bestellelemente hinzufügen
    orderData.items.forEach((item, index) => {
      formData.append(`items[${index}][id]`, item.id);
      formData.append(`items[${index}][quantity]`, item.quantity);
      formData.append(`items[${index}][notes]`, item.notes || '');
    });

    const response = await fetch(window.dailyMenuAjax.ajaxurl, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
    });

    const data = await response.json();

    if (!data.success) {
      throw new Error(data.data || 'Error submitting order');
    }

    return data.data || {};
  } catch (error) {
    console.error('API Error:', error);
    throw error;
  }
}

/**
 * Hilfsfunktion für einfachere Formularübermittlung
 *
 * @param {FormData|Object} formData - Die zu sendenden Formulardaten
 * @param {string} action - Die AJAX-Aktion
 * @returns {Promise<any>} - Die Antwortdaten
 */
export async function sendRequest(formData, action) {
  try {
    const data = formData instanceof FormData ? formData : new FormData();

    // Wenn es kein FormData-Objekt ist, Daten manuell hinzufügen
    if (!(formData instanceof FormData)) {
      Object.keys(formData).forEach((key) => {
        data.append(key, formData[key]);
      });
    }

    // AJAX-Aktion und Nonce hinzufügen
    data.append('action', action);
    data.append('_ajax_nonce', window.dailyMenuAjax.nonce);

    const response = await fetch(window.dailyMenuAjax.ajaxurl, {
      method: 'POST',
      body: data,
      credentials: 'same-origin',
    });

    return await response.json();
  } catch (error) {
    console.error('API Error:', error);
    throw error;
  }
}
