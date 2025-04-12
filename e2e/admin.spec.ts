import { test, expect } from '@playwright/test';
import { loadTranslations as poTranslations } from './utils/po-parser';

// // Haupttest-Suite
// test.describe('Daily Menu Manager Admin', () => {
//   // Reguläre Tests
//   test.beforeEach(async ({ page }) => {
//     await page.goto('/wp-admin/admin.php?page=daily-menu-manager');
//   });

//   test('should display the menu management page', async ({ page }) => {
//     await expect(
//       page.getByRole('heading', {
//         name: /Tagesmenü verwalten|Manage Daily Menu/i,
//       }),
//     ).toBeVisible();

//     await expect(page.getByText(/Datum auswählen:|Select Date:/i)).toBeVisible();
//   });

//   test('should display the orders page', async ({ page }) => {
//     await page.goto('/wp-admin/admin.php?page=daily-menu-orders');
//     await expect(page.getByRole('heading', { name: /Bestellungen|Orders/i })).toBeVisible();

//     await expect(page.getByText(/Heutige Bestellungen|Today's orders/i)).toBeVisible();
//     await expect(page.getByText(/Heutiger Umsatz|Today's revenue/i)).toBeVisible();
//     await expect(page.getByText(/Bestellte Artikel|Ordered items/i)).toBeVisible();
//   });

//   test('should be able to access settings page', async ({ page }) => {
//     await page.goto('/wp-admin/admin.php?page=daily-menu-manager-settings');
//     await expect(page.getByRole('heading', { name: /Einstellungen|settings/i })).toBeVisible();
//   });
// });

// Separate Test-Suites für jede Sprache
const locales = ['en_US', 'de_DE'];

for (const locale of locales) {
  test.describe(`Daily Menu Manager Admin [${locale}]`, () => {
    let translations: Record<string, string>;

    test.beforeEach(async ({ page }) => {
      // PO-Übersetzungen laden
      try {
        translations = await poTranslations(locale);
        console.log(`Loaded translations for ${locale}!`);
      } catch (error) {
        console.error(`Failed to load translations for ${locale}:`, error);
        translations = {}; // Fallback auf leeres Objekt
      }

      // Sprache im WordPress setzen
      await page.goto(`/wp-admin/options-general.php`);
      if (locale === 'en_US') {
        await page.locator('#WPLANG').selectOption(''); // Englisch hat keine spezielle Locale
      } else {
        await page.locator('#WPLANG').selectOption(`${locale}`);
      }
      await page.locator('#submit').click();

      await page.goto(`/wp-admin/admin.php?page=daily-menu-manager&locale=${locale}`);
    });

    test(`should display the menu management page in ${locale}`, async ({ page }) => {

      await page.goto("/wp-admin/admin.php?page=daily-menu-manager");

      const menuTitle = translations['Manage Daily Menu'] || 'Manage Daily Menu';
      await expect(page.getByRole('heading', { name: menuTitle })).toBeVisible();
    });
  });
}