/**
 * API Mocking Helpers
 * Utilities for mocking backend API responses
 *
 * Following playwright-expert guideline: Leverage API testing for backend validation
 */

import { Page, Route } from '@playwright/test';
import { MockGeneratedFlashcards } from '../fixtures/test-data';

/**
 * Mock successful flashcard generation response
 */
export async function mockSuccessfulGeneration(page: Page) {
  await page.route('/api/generate', async (route: Route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        job_id: 'test-job-uuid-12345',
        status: 'completed',
        cards: MockGeneratedFlashcards,
        suggested_name: 'Fotosynteza i Oddychanie Komórkowe',
      }),
    });
  });
}

/**
 * Mock API timeout error
 */
export async function mockTimeoutError(page: Page) {
  await page.route('/api/generate', async (route: Route) => {
    // Simulate timeout by delaying response
    await new Promise(resolve => setTimeout(resolve, 31000));
    await route.abort('timedout');
  });
}

/**
 * Mock rate limit error (429)
 */
export async function mockRateLimitError(page: Page) {
  await page.route('/api/generate', async (route: Route) => {
    await route.fulfill({
      status: 429,
      contentType: 'application/json',
      body: JSON.stringify({
        error: 'Rate limit exceeded',
        message: 'Za dużo żądań. Spróbuj za chwilę.',
      }),
    });
  });
}

/**
 * Mock authentication error (401)
 */
export async function mockAuthenticationError(page: Page) {
  await page.route('/api/generate', async (route: Route) => {
    await route.fulfill({
      status: 401,
      contentType: 'application/json',
      body: JSON.stringify({
        error: 'Authentication failed',
        message: 'Błąd konfiguracji. Skontaktuj się z administratorem.',
      }),
    });
  });
}

/**
 * Mock set save success
 */
export async function mockSetSaveSuccess(page: Page) {
  await page.route('/api/sets', async (route: Route) => {
    if (route.request().method() === 'POST') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          set_id: 'test-set-uuid-67890',
          message: 'Zestaw zapisany pomyślnie',
        }),
      });
    }
  });
}

/**
 * Mock duplicate set name error
 */
export async function mockDuplicateSetNameError(page: Page) {
  await page.route('/api/sets', async (route: Route) => {
    if (route.request().method() === 'POST') {
      await route.fulfill({
        status: 422,
        contentType: 'application/json',
        body: JSON.stringify({
          error: 'Duplicate set name',
          message: 'Zestaw o tej nazwie już istnieje',
        }),
      });
    }
  });
}
