import { test as setup, expect } from '@playwright/test';

setup('authenticate as admin', async ({ page }) => {
  // Ensure we start with a fresh session
  await page.context().clearCookies();
  
  await page.goto('/wp-login.php');

  await page.locator('#user_login').fill(process.env.WP_ADMIN_USER || 'admin');
  await page.locator('#user_pass').fill(process.env.WP_ADMIN_PASS || 'password');
  await page.locator('#rememberme').check();
  await page.locator('#wp-submit').click();

  await page.waitForURL('**/wp-admin/**');

  // Store signed-in state
  await page.context().storageState({
    path: 'playwright/.auth/admin.json'
  });
});