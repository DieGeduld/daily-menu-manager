import { test, expect } from '@playwright/test';

const frontEndOrderUrl = process.env.FRONTEND_ORDER_URL || 'http://localhost:8080';

test.describe(`Daily Menu Manager Make A Order`, () => {
  test.beforeEach(async ({ page }) => {
    // Hier können Vorbereitungen erfolgen
  });

  test(`should show an order in the order page`, async ({ page }) => {

    const dishName = 'Testgericht'.concat(Math.floor(Math.random() * 1000).toString());
    const dishDescription = 'Testbeschreibung';
    const dishPrice = '10.00';
    const dishQuantity = '10';
    
    await page.goto('/wp-admin/admin.php?page=daily-menu-manager');
  
    // Dialog-Handler hinzufügen, der alle Bestätigungsdialoge automatisch mit "OK" bestätigt
    page.on('dialog', async dialog => {
      console.log(`Dialog-Nachricht: ${dialog.message()}`);
      // Mit "true" bestätigen (entspricht dem Klick auf "OK")
      await dialog.accept();
    });
  
    // Zähle die vorhandenen "Entfernen"-Buttons
    const removeButtons = page.locator('.remove-menu-item');
    const count = await removeButtons.count();
    
    // Wenn Elemente vorhanden sind, entferne sie nacheinander
    for (let i = 0; i < count; i++) {
      await page.locator('.remove-menu-item').first().click();
      await page.waitForTimeout(200);
    }
    
    await page.locator('.menu-controls .button').first().click();
    await page.locator('.menu-items input[name*="title"]').fill(dishName);
    await page.locator('.menu-items textarea[name*="description"]').fill(dishDescription);
    await page.locator('.menu-items input[name*="price"]').fill(dishPrice);
    await page.locator('.menu-items input[name*="available_quantity"]').fill(dishQuantity); 

    await page.locator('#save_menu').click();

    await page.waitForURL('**/wp-admin/**');

    // Navigiere zur Frontend-Order-Seite
    await page.goto(frontEndOrderUrl);

    await expect(page.locator('.menu-item .menu-item-title')).toContainText(dishName);
    
    await page.locator('.menu-item input[name*="quantity"]').fill('3');
    
    
    await page.locator('.order-info-column input[name="customer_name"]').fill('Max Mustermann');
    await page.locator('.order-info-column input[name="customer_phone"]').fill('123456789');
    await page.locator('.order-info-column select[name="consumption_type"]').selectOption('Abholen');
    await page.locator('.order-info-column select[name="pickup_time"]').selectOption('12:00');
    await page.locator('.order-info-column textarea[name="general_notes"]').fill('Testnotiz');
    
    await page.locator('.order-info-column .submit-order').click();


    
  });
});