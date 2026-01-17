/**
 * Page Object: Login Page
 * Represents /login route - user authentication interface
 *
 * Test Coverage: TC-AUTH-001 (when auth is implemented)
 */

import { Page, Locator, expect } from '@playwright/test';
import { BasePage } from './BasePage';

export class LoginPage extends BasePage {
  // Locators
  readonly emailInput: Locator;
  readonly passwordInput: Locator;
  readonly submitButton: Locator;
  readonly registerLink: Locator;
  readonly forgotPasswordLink: Locator;
  readonly errorMessage: Locator;

  constructor(page: Page) {
    super(page);

    this.emailInput = page.locator('input[name="email"]');
    this.passwordInput = page.locator('input[name="password"]');
    this.submitButton = page.locator('button[type="submit"]');
    this.registerLink = page.locator('a:has-text("Zarejestruj się")');
    this.forgotPasswordLink = page.locator('a:has-text("Zapomniałeś hasła")');
    this.errorMessage = page.locator('.error-message');
  }

  /**
   * Navigate to login page
   */
  async navigate() {
    await this.goto('/login');
  }

  /**
   * Perform login with credentials
   */
  async login(email: string, password: string) {
    await this.emailInput.fill(email);
    await this.passwordInput.fill(password);
    await this.submitButton.click();
  }

  /**
   * Wait for successful login redirect
   */
  async waitForLoginSuccess() {
    await this.page.waitForURL(/\/(generate|sets)/);
  }

  /**
   * Verify login error is displayed
   */
  async verifyLoginError(expectedMessage?: string) {
    await expect(this.errorMessage).toBeVisible();
    if (expectedMessage) {
      await expect(this.errorMessage).toContainText(expectedMessage);
    }
  }

  /**
   * Navigate to register page
   */
  async goToRegister() {
    await this.registerLink.click();
  }
}
