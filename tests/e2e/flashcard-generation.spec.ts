/**
 * E2E Test: AI Flashcard Generation Flow
 *
 * Tests complete user journey from text input to saved flashcard set
 * Reference: test-plan.md Section 5.1 (TC-GEN-001)
 * Follows playwright-expert skill guidelines
 */

import { test, expect } from '@playwright/test';

test.describe('AI Flashcard Generation', () => {
  test.beforeEach(async ({ page }) => {
    // TODO: Use authenticated state when auth is implemented
    // For now, navigate directly to generation page
    await page.goto('/generate');
  });

  /**
   * TC-GEN-001: Complete flow of generating flashcards
   * User Story: US-003, US-006
   *
   * MARKED AS SKIP - Controllers not yet implemented
   */
  test.skip('should generate flashcards from valid text input', async ({ page }) => {
    // Step 1: Verify page loads with form
    await expect(page.locator('textarea[name="source_text"]')).toBeVisible();
    await expect(page.locator('button:has-text("Generuj fiszki")')).toBeDisabled();

    // Step 2: Input valid text (5000 characters)
    const validText = 'This is sample educational content about biology. '.repeat(100);
    await page.fill('textarea[name="source_text"]', validText);

    // Step 3: Verify character counter updates
    await expect(page.locator('[data-generate-target="currentCount"]')).toContainText('5000');

    // Step 4: Verify button becomes enabled
    const submitButton = page.locator('button:has-text("Generuj fiszki")');
    await expect(submitButton).toBeEnabled();

    // Step 5: Click generate button
    await submitButton.click();

    // Step 6: Verify loading overlay appears
    await expect(page.locator('[data-generate-target="loadingOverlay"]')).toBeVisible();
    await expect(page.locator('text=Analizowanie tekstu...')).toBeVisible();

    // Step 7: Wait for redirect to edit page (timeout: 30s for AI processing)
    await page.waitForURL('/sets/new/edit', { timeout: 30000 });

    // Step 8: Verify flashcards are displayed in edit view
    await expect(page.locator('[data-edit-set-target="cardItem"]')).toHaveCount(10, { timeout: 5000 });

    // Step 9: Verify suggested set name is populated
    const setNameInput = page.locator('input[name="name"]');
    await expect(setNameInput).not.toHaveValue('');

    // Step 10: Save the set
    await setNameInput.fill('My E2E Test Set');
    await page.click('button:has-text("Zapisz zestaw")');

    // Step 11: Verify redirect back to generate page
    await page.waitForURL('/generate');

    // Step 12: Verify success message (if implemented)
    // await expect(page.locator('.snackbar:has-text("Zestaw zapisany")')).toBeVisible();
  });

  /**
   * TC-AI-01: Validation - text below minimum (1000 chars)
   */
  test.skip('should disable button when text is below 1000 characters', async ({ page }) => {
    const textarea = page.locator('textarea[name="source_text"]');
    const button = page.locator('button:has-text("Generuj fiszki")');

    // Input 999 characters
    await textarea.fill('a'.repeat(999));

    // Button should remain disabled
    await expect(button).toBeDisabled();

    // Counter should show red/warning state
    const counter = page.locator('[data-generate-target="counter"]');
    await expect(counter).toHaveClass(/text-red/);
  });

  /**
   * TC-AI-02: Validation - text above maximum (10000 chars)
   */
  test.skip('should disable button when text exceeds 10000 characters', async ({ page }) => {
    const textarea = page.locator('textarea[name="source_text"]');
    const button = page.locator('button:has-text("Generuj fiszki")');

    // Input 10001 characters
    await textarea.fill('a'.repeat(10001));

    // Button should be disabled
    await expect(button).toBeDisabled();

    // Progress bar should be red
    const progressBar = page.locator('[data-generate-target="progressBar"]');
    await expect(progressBar).toHaveClass(/bg-red/);
  });

  /**
   * TC-GEN-003: Error handling - API timeout
   */
  test.skip('should display error modal on API failure', async ({ page }) => {
    // Mock API to return error
    await page.route('/api/generate', route => route.abort('timedout'));

    const textarea = page.locator('textarea[name="source_text"]');
    const button = page.locator('button:has-text("Generuj fiszki")');

    await textarea.fill('Valid text content. '.repeat(50));
    await button.click();

    // Error modal should appear
    await expect(page.locator('.error-modal')).toBeVisible();
    await expect(page.locator('text=Nie udało się wygenerować fiszek')).toBeVisible();

    // User should remain on /generate page
    await expect(page).toHaveURL('/generate');

    // Text should be preserved in textarea
    await expect(textarea).not.toHaveValue('');
  });

  /**
   * Visual regression test - Take screenshot of generate page
   * Following playwright-expert guideline: implement visual comparison
   */
  test.skip('should match generate page screenshot', async ({ page }) => {
    // Take screenshot and compare with baseline
    await expect(page).toHaveScreenshot('generate-page.png', {
      fullPage: true,
      animations: 'disabled',
    });
  });
});
