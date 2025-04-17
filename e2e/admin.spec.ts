import { test, expect } from '@playwright/test';
import { loadTranslations as poTranslations } from './utils/po-parser';

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
    });

    test(`should display the menu management page in ${locale}`, async ({ page }) => {
      await page.goto('/wp-admin/admin.php?page=daily-menu-manager');

      const menuTitle = translations['Manage Daily Menu'] || 'Manage Daily Menu';
      await expect(page.getByRole('heading', { name: menuTitle })).toBeVisible();
    });

    test('should display the orders page', async ({ page }) => {
      await page.goto('/wp-admin/admin.php?page=daily-menu-orders');

      let translation = translations['Orders'] || 'Orders';
      await expect(page.getByRole('heading', { name: translation })).toBeVisible();

      translation = translations["Today's orders"] || "Today's orders";
      await expect(page.locator('.summary-table tr').nth(0).locator('td').first()).toHaveText(translation);
      
      translation = translations["Today's revenue"] || "Today's revenue";
      await expect(page.locator('.summary-table tr').nth(1).locator('td').first()).toHaveText(translation);
      
      translation = translations['Ordered items'] || 'Ordered items';
      await expect(page.locator('.summary-table tr').nth(2).locator('td').first()).toHaveText(translation);
    });

    test('should be able to access settings page', async ({ page }) => {
      await page.goto('/wp-admin/admin.php?page=daily-menu-manager-settings');
      let translation = translations['Settings'] || 'Settings';
      await expect(page.getByRole('heading', { name: translation })).toBeVisible();
    });
  });
}
