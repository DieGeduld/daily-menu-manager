import { defineConfig, devices } from '@playwright/test';
import dotenv from 'dotenv';

// Load environment variables from .env file
dotenv.config();

export default defineConfig({
  testDir: './e2e',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  // retries: process.env.CI ? 2 : 0,
  // workers: process.env.CI ? 1 : undefined,
  reporter: 'html',

  projects: [
    {
      name: 'setup',
      testMatch: /.*\.setup\.ts/,
    },
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
        // Store authentication state between tests
        storageState: 'playwright/.auth/admin.json',
        //headless: false,
      },
      dependencies: ['setup'],
    },
  ],

  use: {
    baseURL: process.env.WP_BASE_URL || 'http://localhost:8080',
    trace: 'on-first-retry',
  },
});
