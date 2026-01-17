/**
 * Page Object: Generate Flashcards Page
 * Represents /generate route - main AI flashcard generation interface
 *
 * Test Coverage: TC-GEN-001, TC-AI-01, TC-AI-02
 */

import { Page, Locator, expect } from '@playwright/test';
import { BasePage } from './BasePage';

export class GeneratePage extends BasePage {
  // Locators using data-* attributes for stability
  readonly sourceTextarea: Locator;
  readonly generateButton: Locator;
  readonly characterCounter: Locator;
  readonly currentCountElement: Locator;
  readonly progressBar: Locator;
  readonly loadingOverlay: Locator;
  readonly loadingMessage: Locator;
  readonly errorModal: Locator;

  constructor(page: Page) {
    super(page);

    // Form elements
    this.sourceTextarea = page.locator('textarea[name="source_text"]');
    this.generateButton = page.locator('button:has-text("Generuj fiszki")');

    // Stimulus controller targets
    this.characterCounter = page.locator('[data-generate-target="counter"]');
    this.currentCountElement = page.locator('[data-generate-target="currentCount"]');
    this.progressBar = page.locator('[data-generate-target="progressBar"]');
    this.loadingOverlay = page.locator('[data-generate-target="loadingOverlay"]');
    this.loadingMessage = page.locator('text=Analizowanie tekstu...');

    // Error handling
    this.errorModal = page.locator('.error-modal');
  }

  /**
   * Navigate to generate page
   */
  async navigate() {
    await this.goto('/generate');
  }

  /**
   * Enter text into source textarea
   */
  async enterSourceText(text: string) {
    await this.sourceTextarea.fill(text);
  }

  /**
   * Get current character count from UI
   */
  async getCharacterCount(): Promise<number> {
    const countText = await this.currentCountElement.textContent();
    return parseInt(countText || '0');
  }

  /**
   * Check if generate button is enabled
   */
  async isGenerateButtonEnabled(): Promise<boolean> {
    return await this.generateButton.isEnabled();
  }

  /**
   * Click generate button
   */
  async clickGenerate() {
    await this.generateButton.click();
  }

  /**
   * Wait for loading overlay to appear
   */
  async waitForLoadingOverlay() {
    await expect(this.loadingOverlay).toBeVisible();
  }

  /**
   * Wait for redirect to edit page after successful generation
   */
  async waitForRedirectToEdit() {
    await this.page.waitForURL('/sets/new/edit', { timeout: 30000 });
  }

  /**
   * Verify error modal is displayed
   */
  async verifyErrorModalVisible() {
    await expect(this.errorModal).toBeVisible();
    await expect(this.page.locator('text=Nie udało się wygenerować fiszek')).toBeVisible();
  }

  /**
   * Generate valid sample text of specified length
   */
  generateSampleText(length: number): string {
    const baseText = 'This is sample educational content about biology and science. ';
    const repetitions = Math.ceil(length / baseText.length);
    return baseText.repeat(repetitions).substring(0, length);
  }

  /**
   * Complete full generation flow with valid text
   */
  async generateFlashcards(textLength: number = 5000) {
    const text = this.generateSampleText(textLength);
    await this.enterSourceText(text);

    // Verify button is enabled
    await expect(this.generateButton).toBeEnabled();

    // Click and wait for processing
    await this.clickGenerate();
    await this.waitForLoadingOverlay();
    await this.waitForRedirectToEdit();
  }
}
