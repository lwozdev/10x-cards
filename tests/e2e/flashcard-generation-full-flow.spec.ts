/**
 * E2E Test: Complete Flashcard Generation Flow with Authentication
 *
 * Tests the full user journey:
 * 1. Login with credentials from .env.test
 * 2. Navigate to /generate page by clicking logo
 * 3. Enter text (minimum 1000 characters)
 * 4. Generate flashcards
 * 5. Save the flashcard set
 * 6. Navigate to "My Sets" and verify the set was saved
 *
 * Reference: Full user flow (US-003, US-006, US-009)
 */

import { test, expect } from './fixtures/custom-fixtures';
import { TestTexts } from './fixtures/test-data';

test.describe('Complete Flashcard Generation Flow', () => {
  // Get credentials from environment variables
  const username = process.env.E2E_USERNAME || 'admin@example.com';
  const password = process.env.E2E_PASSWORD || 'admin1234';

  /**
   * TC-FULL-FLOW-001: Complete flow from login to saving and verifying set
   */
  test('should allow user to login, generate flashcards, save, and verify in My Sets', async ({
    page,
    loginPage,
    generatePage,
    editSetPage,
    mySetsPage,
  }) => {
    // Step 1: Login with credentials from .env.test
    await loginPage.navigate();
    await loginPage.login(username, password);
    await loginPage.waitForLoginSuccess();

    // Verify we're redirected after login
    await expect(page).toHaveURL(/\/(generate|sets)/);

    // Step 2: Navigate to /generate page by clicking logo
    // If we're not already on generate page, click the logo
    if (!page.url().includes('/generate')) {
      await mySetsPage.clickLogo();
      await expect(page).toHaveURL('/generate');
    }

    // Step 3: Verify page loaded and enter text (minimum 1000 characters)
    await expect(generatePage.sourceTextarea).toBeVisible();
    await expect(generatePage.generateButton).toBeDisabled();

    // Use test data with sufficient length (5000+ characters)
    await generatePage.enterSourceText(TestTexts.validGenerationText);

    // Wait a moment for Stimulus controller to initialize and update the counter
    await page.waitForTimeout(500);

    // Verify character count is within valid range
    const charCount = await generatePage.getCharacterCount();
    expect(charCount).toBeGreaterThanOrEqual(1000);
    expect(charCount).toBeLessThanOrEqual(10000);

    // Step 4: Verify button is enabled and click "Generuj fiszki"
    await expect(generatePage.generateButton).toBeEnabled();
    await generatePage.clickGenerate();

    // Wait for loading overlay
    await generatePage.waitForLoadingOverlay();

    // Step 5: Wait for redirect to edit page (AI processing may take up to 30s)
    await generatePage.waitForRedirectToEdit();

    // Verify we're on the edit page with generated cards
    const cardCount = await editSetPage.getCardCount();
    expect(cardCount).toBeGreaterThan(0);

    // Verify suggested set name is populated
    const suggestedName = await editSetPage.getSuggestedName();
    expect(suggestedName).not.toBe('');

    // Step 6: Save the flashcard set with a unique name
    const testSetName = `E2E Test Set ${Date.now()}`;
    await editSetPage.enterSetName(testSetName);
    await editSetPage.saveSet();

    // Wait for redirect after save (redirects to /sets)
    await editSetPage.waitForRedirectToSets();

    // Step 7: Verify we're on "Moje zestawy" page
    await expect(page).toHaveURL('/sets');

    // Verify the page loaded
    await expect(mySetsPage.pageHeading).toBeVisible();

    // Step 8: Verify the set we just created appears in the list
    const setExists = await mySetsPage.verifySetExists(testSetName);
    expect(setExists).toBe(true);

    // Get all set names and verify our set is there
    const allSetNames = await mySetsPage.getSetNames();
    const foundSet = allSetNames.some(name => name.includes(testSetName));
    expect(foundSet).toBe(true);

    // Optional: Log success
    console.log(`✓ Successfully created and verified set: "${testSetName}"`);
    console.log(`✓ Total sets in list: ${allSetNames.length}`);
  });

  /**
   * TC-FULL-FLOW-002: Generate with minimum valid text (1000 characters)
   */
  test('should successfully generate flashcards with exactly 1000 characters', async ({
    page,
    loginPage,
    generatePage,
    editSetPage,
    mySetsPage,
  }) => {
    // Login
    await loginPage.navigate();
    await loginPage.login(username, password);
    await loginPage.waitForLoginSuccess();

    // Navigate to generate page
    await generatePage.navigate();

    // Enter minimum valid text (1000 characters)
    await generatePage.enterSourceText(TestTexts.minimumValidText);

    // Verify character count
    const charCount = await generatePage.getCharacterCount();
    expect(charCount).toBeGreaterThanOrEqual(1000);

    // Verify button is enabled
    await expect(generatePage.generateButton).toBeEnabled();

    // Generate
    await generatePage.clickGenerate();
    await generatePage.waitForLoadingOverlay();
    await generatePage.waitForRedirectToEdit();

    // Verify cards were generated
    const cardCount = await editSetPage.getCardCount();
    expect(cardCount).toBeGreaterThan(0);

    // Save with unique name
    const testSetName = `E2E Min Text ${Date.now()}`;
    await editSetPage.enterSetName(testSetName);
    await editSetPage.saveSet();

    // Wait for redirect to My Sets page
    await editSetPage.waitForRedirectToSets();

    // Verify the set appears in the list
    await expect(page).toHaveURL('/sets');
    const setExists = await mySetsPage.verifySetExists(testSetName);
    expect(setExists).toBe(true);
  });

  /**
   * TC-FULL-FLOW-003: Verify logo navigation from any page
   */
  test('should navigate to generate page by clicking logo from My Sets', async ({
    page,
    loginPage,
    mySetsPage,
    generatePage,
  }) => {
    // Login
    await loginPage.navigate();
    await loginPage.login(username, password);
    await loginPage.waitForLoginSuccess();

    // Go to My Sets
    await mySetsPage.navigate();
    await expect(mySetsPage.pageHeading).toBeVisible();

    // Click logo
    await mySetsPage.clickLogo();

    // Verify we're on generate page
    await expect(page).toHaveURL('/generate');
    await expect(generatePage.sourceTextarea).toBeVisible();
  });
});
