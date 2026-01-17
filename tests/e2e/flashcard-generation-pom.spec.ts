/**
 * E2E Test: AI Flashcard Generation Flow (Using Page Object Model)
 *
 * Refactored version using:
 * - Page Object Model for maintainability
 * - Custom fixtures for page objects
 * - API mocking for controlled testing
 * - Test data fixtures for reusability
 *
 * Reference: test-plan.md Section 5.1 (TC-GEN-001, TC-AI-01, TC-AI-02, TC-GEN-003)
 */

import { test, expect } from './fixtures/custom-fixtures';
import { TestTexts } from './fixtures/test-data';
import {
  mockSuccessfulGeneration,
  mockTimeoutError,
  mockRateLimitError,
  mockSetSaveSuccess,
} from './helpers/api-mocks';

test.describe('AI Flashcard Generation (Page Object Model)', () => {
  /**
   * TC-GEN-001: Complete flow of generating flashcards
   * User Story: US-003, US-006
   *
   * MARKED AS SKIP - Controllers not yet implemented
   */
  test.skip('should generate flashcards from valid text input', async ({ page, generatePage, editSetPage }) => {
    // Mock API responses for controlled testing
    await mockSuccessfulGeneration(page);
    await mockSetSaveSuccess(page);

    // Step 1: Navigate to generate page
    await generatePage.navigate();

    // Step 2: Verify initial state
    await expect(generatePage.sourceTextarea).toBeVisible();
    await expect(generatePage.generateButton).toBeDisabled();

    // Step 3: Enter valid text (5000 characters)
    await generatePage.enterSourceText(TestTexts.validGenerationText);

    // Step 4: Verify character counter updates
    const charCount = await generatePage.getCharacterCount();
    expect(charCount).toBeGreaterThanOrEqual(1000);
    expect(charCount).toBeLessThanOrEqual(10000);

    // Step 5: Verify button becomes enabled
    expect(await generatePage.isGenerateButtonEnabled()).toBe(true);

    // Step 6: Click generate and wait for loading
    await generatePage.clickGenerate();
    await generatePage.waitForLoadingOverlay();

    // Step 7: Wait for redirect to edit page
    await generatePage.waitForRedirectToEdit();

    // Step 8: Verify we're on edit page with generated cards
    const cardCount = await editSetPage.getCardCount();
    expect(cardCount).toBeGreaterThan(0);

    // Step 9: Verify suggested set name is populated
    const suggestedName = await editSetPage.getSuggestedName();
    expect(suggestedName).not.toBe('');

    // Step 10: Complete edit and save flow
    await editSetPage.completeEditAndSave('My E2E Test Set', 0, 2);

    // Step 11: Verify redirect back to generate page
    await expect(page).toHaveURL('/generate');
  });

  /**
   * TC-AI-01: Validation - text below minimum (1000 chars)
   */
  test.skip('should disable button when text is below 1000 characters', async ({ generatePage }) => {
    await generatePage.navigate();

    // Input 999 characters
    await generatePage.enterSourceText(TestTexts.tooShortText);

    // Button should remain disabled
    expect(await generatePage.isGenerateButtonEnabled()).toBe(false);

    // Counter should show warning state
    await expect(generatePage.characterCounter).toHaveClass(/text-red|text-error/);
  });

  /**
   * TC-AI-02: Validation - text above maximum (10000 chars)
   */
  test.skip('should disable button when text exceeds 10000 characters', async ({ generatePage }) => {
    await generatePage.navigate();

    // Input 10001 characters
    await generatePage.enterSourceText(TestTexts.tooLongText);

    // Button should be disabled
    expect(await generatePage.isGenerateButtonEnabled()).toBe(false);

    // Progress bar should show error state
    await expect(generatePage.progressBar).toHaveClass(/bg-red|bg-error/);
  });

  /**
   * TC-GEN-003: Error handling - API timeout
   */
  test.skip('should display error modal on API timeout', async ({ page, generatePage }) => {
    await generatePage.navigate();

    // Mock timeout error
    await mockTimeoutError(page);

    // Enter valid text and generate
    await generatePage.enterSourceText(TestTexts.validGenerationText);
    await generatePage.clickGenerate();

    // Verify error modal appears
    await generatePage.verifyErrorModalVisible();

    // User should remain on /generate page
    await expect(page).toHaveURL('/generate');

    // Text should be preserved
    const textValue = await generatePage.sourceTextarea.inputValue();
    expect(textValue).toBe(TestTexts.validGenerationText);
  });

  /**
   * TC-GEN-004: Error handling - Rate limit exceeded
   */
  test.skip('should display appropriate error for rate limit', async ({ page, generatePage }) => {
    await generatePage.navigate();

    // Mock rate limit error
    await mockRateLimitError(page);

    await generatePage.enterSourceText(TestTexts.validGenerationText);
    await generatePage.clickGenerate();

    // Verify error message mentions rate limit
    await expect(page.locator('text=Za dużo żądań')).toBeVisible();
  });

  /**
   * Visual regression test - Generate page screenshot
   * Following playwright-expert guideline: implement visual comparison
   */
  test.skip('should match generate page screenshot', async ({ page, generatePage }) => {
    await generatePage.navigate();

    // Take screenshot and compare with baseline
    await expect(page).toHaveScreenshot('generate-page.png', {
      fullPage: true,
      animations: 'disabled',
    });
  });

  /**
   * Test boundary conditions - minimum valid text
   */
  test.skip('should accept exactly 1000 characters', async ({ generatePage }) => {
    await generatePage.navigate();

    await generatePage.enterSourceText(TestTexts.minimumValidText);

    const charCount = await generatePage.getCharacterCount();
    expect(charCount).toBeGreaterThanOrEqual(1000);

    expect(await generatePage.isGenerateButtonEnabled()).toBe(true);
  });

  /**
   * Test boundary conditions - maximum valid text
   */
  test.skip('should accept exactly 10000 characters', async ({ generatePage }) => {
    await generatePage.navigate();

    await generatePage.enterSourceText(TestTexts.maximumValidText);

    const charCount = await generatePage.getCharacterCount();
    expect(charCount).toBeLessThanOrEqual(10000);

    expect(await generatePage.isGenerateButtonEnabled()).toBe(true);
  });
});
