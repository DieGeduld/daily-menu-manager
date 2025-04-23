// src/frontend.js
import { createApp } from 'vue';
import DailyMenuApp from './components/DailyMenuApp.vue';
import store from './store';

//import '@/src/sass/frontend.scss'

// Warten bis DOM geladen ist
document.addEventListener('DOMContentLoaded', () => {
  // Finde den Mount-Punkt
  const appElement = document.getElementById('daily-menu-app');

  // Nur fortfahren, wenn das Element existiert
  if (appElement) {
    // Daten aus den data-Attributen lesen
    const menuId = appElement.dataset.menuId;
    const menuDate = appElement.dataset.menuDate;
    const title = appElement.dataset.title;

    // Vue-App erstellen und mit Store registrieren
    const app = createApp(DailyMenuApp, {
      menuId,
      menuDate,
      title,
    });

    // Store einbinden
    app.use(store);

    // App mounten
    app.mount(appElement);

    console.log('Daily Menu App wurde initialisiert!');
  }
});
