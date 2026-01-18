/**
 * Custom Playwright Fixtures
 * Extended test context with page objects and utilities
 *
 * Following playwright-expert guideline: Use browser contexts for isolating test environments
 */

import { test as base } from '@playwright/test';
import { GeneratePage } from '../pages/GeneratePage';
import { EditSetPage } from '../pages/EditSetPage';
import { LoginPage } from '../pages/LoginPage';
import { MySetsPage } from '../pages/MySetsPage';

/**
 * Extended test fixtures with page objects
 */
type CustomFixtures = {
  generatePage: GeneratePage;
  editSetPage: EditSetPage;
  loginPage: LoginPage;
  mySetsPage: MySetsPage;
};

/**
 * Extend base test with custom fixtures
 */
export const test = base.extend<CustomFixtures>({
  /**
   * Generate Page fixture
   * Automatically initializes GeneratePage for each test
   */
  generatePage: async ({ page }, use) => {
    const generatePage = new GeneratePage(page);
    await use(generatePage);
  },

  /**
   * Edit Set Page fixture
   * Automatically initializes EditSetPage for each test
   */
  editSetPage: async ({ page }, use) => {
    const editSetPage = new EditSetPage(page);
    await use(editSetPage);
  },

  /**
   * Login Page fixture
   * Automatically initializes LoginPage for each test
   */
  loginPage: async ({ page }, use) => {
    const loginPage = new LoginPage(page);
    await use(loginPage);
  },

  /**
   * My Sets Page fixture
   * Automatically initializes MySetsPage for each test
   */
  mySetsPage: async ({ page }, use) => {
    const mySetsPage = new MySetsPage(page);
    await use(mySetsPage);
  },
});

export { expect } from '@playwright/test';
