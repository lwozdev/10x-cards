import { defineConfig, devices } from '@playwright/test';
import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

/**
 * Get __dirname equivalent in ES modules
 */
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

/**
 * Load environment variables from .env.test and .env.test.local
 * .env.test.local takes precedence (gitignored for sensitive data)
 */
dotenv.config({ path: path.resolve(__dirname, '.env.test') });
dotenv.config({ path: path.resolve(__dirname, '.env.test.local'), override: true });

/**
 * Playwright configuration for E2E tests
 * Following best practices from playwright-expert skill
 */
export default defineConfig({
  // Test directory
  testDir: './tests/e2e',

  // Run tests in files in parallel
  fullyParallel: true,

  // Fail the build on CI if you accidentally left test.only in the source code
  forbidOnly: !!process.env.CI,

  // Retry on CI only
  retries: process.env.CI ? 2 : 0,

  // Opt out of parallel tests on CI
  workers: process.env.CI ? 1 : undefined,

  // Reporter to use
  reporter: [
    ['html', { outputFolder: 'var/playwright-report' }],
    ['list']
  ],

  // Shared settings for all the projects below
  use: {
    // Base URL to use in actions like `await page.goto('/')`
    baseURL: process.env.BASE_URL || 'http://localhost:8000',

    // Collect trace when retrying the failed test
    trace: 'on-first-retry',

    // Screenshot on failure
    screenshot: 'only-on-failure',

    // Video on failure
    video: 'retain-on-failure',
  },

  // Configure projects for major browsers
  // Following guideline: Initialize configuration only with Chromium/Desktop Chrome browser
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],

  // Run your local dev server before starting the tests
  // Uncomment if you want Playwright to start the server automatically
  // webServer: {
  //   command: 'symfony serve --port=8000',
  //   url: 'http://localhost:8000',
  //   reuseExistingServer: !process.env.CI,
  //   timeout: 120 * 1000,
  // },
});
