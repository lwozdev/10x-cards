/**
 * Environment Variables Check
 * Verifies that .env.test and .env.test.local are loaded correctly
 */

import { test, expect } from '@playwright/test';

test.describe('Environment Configuration', () => {
  test('should load environment variables from .env.test', () => {
    // These variables are defined in .env.test
    expect(process.env.KERNEL_CLASS).toBe('App\\Kernel');
    expect(process.env.APP_SECRET).toBeDefined();
    expect(process.env.DATABASE_URL).toContain('flashcards_test');
  });

  test('should have BASE_URL configured', () => {
    // BASE_URL can come from .env.test, .env.test.local, or environment
    expect(process.env.BASE_URL).toBeDefined();
    console.log('BASE_URL:', process.env.BASE_URL);
  });

  test('should allow .env.test.local to override .env.test', () => {
    // If you create .env.test.local with E2E_USERNAME=custom@test.com
    // it should override the value from .env.test
    console.log('E2E_USERNAME from env:', process.env.E2E_USERNAME);
    console.log('E2E_PASSWORD from env:', process.env.E2E_PASSWORD ? '***' : 'NOT SET');

    // Just verify they exist (values depend on your .env.test.local)
    expect(process.env.E2E_USERNAME).toBeDefined();
  });

  test.skip('example: using env vars in test', async ({ page }) => {
    // Example of how to use env vars in actual tests
    const username = process.env.E2E_USERNAME || 'test@example.com';
    const password = process.env.E2E_PASSWORD || 'defaultpassword';

    await page.goto('/login');
    await page.fill('input[name="email"]', username);
    await page.fill('input[name="password"]', password);

    // ... rest of test
  });
});
