/**
 * E2E Test: Edit Set Flow
 *
 * Tests flashcard editing, deletion, and saving
 * Reference: test-plan.md TC-EDIT-004
 */

import { test, expect } from './fixtures/custom-fixtures';
import { TestSetNames } from './fixtures/test-data';
import { mockSetSaveSuccess, mockDuplicateSetNameError } from './helpers/api-mocks';

test.describe('Edit Flashcard Set', () => {
  /**
   * TC-EDIT-004: Saving set with edits and deletions
   * MARKED AS SKIP - Controllers not yet implemented
   */
  test.skip('should save set after editing and deleting cards', async ({ page, editSetPage }) => {
    // Mock successful save
    await mockSetSaveSuccess(page);

    // Navigate to edit page (assuming we have pending_set in session)
    await page.goto('/sets/new/edit');

    // Verify initial card count (e.g., 10 cards)
    const initialCount = await editSetPage.getCardCount();
    expect(initialCount).toBeGreaterThan(0);

    // Delete 3 cards
    await editSetPage.deleteCards(3);

    // Edit 2 cards
    await editSetPage.editCard(0, 'Edited Front 1', 'Edited Back 1');
    await editSetPage.editCard(1, 'Edited Front 2', 'Edited Back 2');

    // Verify card count decreased
    const afterDeleteCount = await editSetPage.getCardCount();
    expect(afterDeleteCount).toBe(initialCount - 3);

    // Enter set name and save
    await editSetPage.enterSetName(TestSetNames.valid);
    await editSetPage.saveSet();

    // Verify redirect to generate page
    await editSetPage.waitForRedirectToGenerate();
    await expect(page).toHaveURL('/generate');
  });

  /**
   * Validation: Empty set name
   */
  test.skip('should show validation error for empty set name', async ({ editSetPage }) => {
    await editSetPage.page.goto('/sets/new/edit');

    // Leave name empty and try to save
    await editSetPage.enterSetName('');
    await editSetPage.saveSet();

    // Verify validation error
    await editSetPage.verifyNameValidationError();
  });

  /**
   * Validation: Set name too short
   */
  test.skip('should show validation error for set name < 3 characters', async ({ editSetPage }) => {
    await editSetPage.page.goto('/sets/new/edit');

    await editSetPage.enterSetName(TestSetNames.tooShort);
    await editSetPage.saveSet();

    await editSetPage.verifyNameValidationError();
  });

  /**
   * Validation: Set name too long
   */
  test.skip('should show validation error for set name > 100 characters', async ({ editSetPage }) => {
    await editSetPage.page.goto('/sets/new/edit');

    await editSetPage.enterSetName(TestSetNames.tooLong);
    await editSetPage.saveSet();

    await editSetPage.verifyNameValidationError();
  });

  /**
   * Error handling: Duplicate set name
   */
  test.skip('should show error for duplicate set name', async ({ page, editSetPage }) => {
    await mockDuplicateSetNameError(page);

    await editSetPage.page.goto('/sets/new/edit');

    await editSetPage.enterSetName(TestSetNames.duplicate);
    await editSetPage.saveSet();

    // Verify error message
    await expect(page.locator('text=Zestaw o tej nazwie już istnieje')).toBeVisible();
  });

  /**
   * Edge case: Delete all cards
   */
  test.skip('should prevent saving when all cards are deleted', async ({ editSetPage }) => {
    await editSetPage.page.goto('/sets/new/edit');

    const initialCount = await editSetPage.getCardCount();

    // Delete all cards
    await editSetPage.deleteCards(initialCount);

    // Verify error message or disabled save button
    await editSetPage.enterSetName(TestSetNames.valid);

    const saveButton = editSetPage.saveButton;
    await expect(saveButton).toBeDisabled();

    // Or verify error message
    await expect(editSetPage.page.locator('text=Musisz mieć przynajmniej 1 fiszkę')).toBeVisible();
  });
});
