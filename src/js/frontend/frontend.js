import { createApp } from 'vue';
import DailyMenuApp from './components/DailyMenuApp.vue';
import store from './store';

document.addEventListener('DOMContentLoaded', () => {
  const appElement = document.getElementById('daily-menu-app');

  if (appElement) {
    const menuId = appElement.dataset.menuId;
    const menuDate = appElement.dataset.menuDate;
    const title = appElement.dataset.title;

    const app = createApp(DailyMenuApp, {
      menuId,
      menuDate,
      title,
    });

    app.use(store);

    // --- Devtools aktivieren, wenn erlaubt ---
    if (import.meta.env.DEV) {
      app.config.devtools = true;
    }

    app.mount(appElement);

    console.log('Daily Menu App wurde initialisiert!');
  }
});
