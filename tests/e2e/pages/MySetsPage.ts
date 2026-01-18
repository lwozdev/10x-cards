/**
 * Page Object: My Sets Page
 * Represents /sets route - list of user's flashcard sets
 *
 * Test Coverage: TC-SETS-001
 */

import { Page, Locator, expect } from '@playwright/test';
import { BasePage } from './BasePage';

export class MySetsPage extends BasePage {
  // Locators
  readonly pageHeading: Locator;
  readonly setItems: Locator;
  readonly setNames: Locator;
  readonly createNewSetButton: Locator;
  readonly learnButtons: Locator;
  readonly deleteButtons: Locator;
  readonly emptyStateMessage: Locator;
  readonly logo: Locator;

  constructor(page: Page) {
    super(page);

    // Page elements - based on actual template (sets/list.html.twig)
    this.pageHeading = page.locator('h1:has-text("Moje zestawy")');
    this.setItems = page.locator('[data-set-list-target="setCard"]');
    this.setNames = page.locator('[data-set-list-target="setCard"] h3');
    this.createNewSetButton = page.locator('a:has-text("Generuj nowy zestaw")');
    this.learnButtons = page.locator('a:has-text("Ucz się")');
    this.deleteButtons = page.locator('button[data-action*="confirmDelete"]');
    this.emptyStateMessage = page.locator('text=Nie masz jeszcze żadnych zestawów');

    // Navigation - use role and name to be more specific (avoid ambiguity with "Generate new set" button)
    this.logo = page.getByRole('link', { name: /Fiszki AI/i });
  }

  /**
   * Navigate to my sets page
   */
  async navigate() {
    await this.goto('/sets');
  }

  /**
   * Get number of sets displayed
   */
  async getSetCount(): Promise<number> {
    return await this.setItems.count();
  }

  /**
   * Get all set names
   */
  async getSetNames(): Promise<string[]> {
    const count = await this.setNames.count();
    const names: string[] = [];

    for (let i = 0; i < count; i++) {
      const name = await this.setNames.nth(i).textContent();
      names.push(name || '');
    }

    return names;
  }

  /**
   * Find set by name
   */
  async findSetByName(name: string): Promise<Locator | null> {
    const count = await this.setNames.count();

    for (let i = 0; i < count; i++) {
      const setName = await this.setNames.nth(i).textContent();
      if (setName?.includes(name)) {
        return this.setItems.nth(i);
      }
    }

    return null;
  }

  /**
   * Verify set exists by name
   */
  async verifySetExists(name: string): Promise<boolean> {
    const set = await this.findSetByName(name);
    return set !== null;
  }

  /**
   * Click on a set's learn button
   */
  async clickLearnForSet(index: number) {
    await this.learnButtons.nth(index).click();
  }

  /**
   * Click on a set's delete button
   */
  async clickDeleteForSet(index: number) {
    await this.deleteButtons.nth(index).click();
  }

  /**
   * Navigate to create new set
   */
  async clickCreateNewSet() {
    await this.createNewSetButton.click();
  }

  /**
   * Verify empty state is shown
   */
  async verifyEmptyState() {
    await expect(this.emptyStateMessage).toBeVisible();
  }

  /**
   * Click logo to navigate to generate page
   */
  async clickLogo() {
    await this.logo.click();
  }
}
