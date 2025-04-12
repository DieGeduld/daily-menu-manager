import { test, expect } from "@playwright/test";

test.describe("Daily Menu Manager Admin", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/wp-admin/admin.php?page=daily-menu-manager");
  });

  test("should display the menu management page", async ({ page }) => {
    await expect(
      page.getByRole("heading", {
        name: /Tagesmenü verwalten|Manage Daily Menu/i,
      })
    ).toBeVisible();

    await expect(
        page.getByText(/Datum auswählen:|Select Date:/i)
      ).toBeVisible();
   
  });

  test("should display the orders page", async ({ page }) => {
    await page.goto("/wp-admin/admin.php?page=daily-menu-orders");
    await expect(
      page.getByRole("heading", { name: /Bestellungen|Orders/i })
    ).toBeVisible();
    
    await expect(
      page.getByText(/Heutige Bestellungen|Today's orders/i)
    ).toBeVisible();
    await expect(
      page.getByText(/Heutiger Umsatz|Today's revenue/i)
    ).toBeVisible();
    await expect(
      page.getByText(/Bestellte Artikel|Ordered items/i)
    ).toBeVisible();

});

  test("should be able to access settings page", async ({ page }) => {
    await page.goto("/wp-admin/admin.php?page=daily-menu-manager-settings");
    await expect(
      page.getByRole("heading", { name: /Einstellungen|settings/i })
    ).toBeVisible();
  });
});
