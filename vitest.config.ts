import { defineConfig } from 'vitest/config';
import path from 'path';

export default defineConfig({
  test: {
    // Use jsdom for DOM testing (Stimulus controllers)
    environment: 'jsdom',

    // Global test setup
    setupFiles: ['./tests/frontend/setup.ts'],

    // Include pattern for test files
    include: ['tests/frontend/**/*.{test,spec}.{js,ts}'],

    // Coverage configuration
    coverage: {
      provider: 'v8',
      reporter: ['text', 'html', 'lcov'],
      reportsDirectory: './var/coverage/frontend',
      include: ['assets/controllers/**/*.js'],
      exclude: [
        'node_modules/',
        'tests/',
        '**/*.config.{js,ts}',
        '**/bootstrap.js'
      ],
      thresholds: {
        lines: 80,
        functions: 80,
        branches: 80,
        statements: 80
      }
    },

    // Watch mode configuration
    watch: false,

    // Globals (if needed)
    globals: true,

    // Reporter
    reporters: ['verbose']
  },

  resolve: {
    alias: {
      '@': path.resolve(__dirname, './assets'),
      '@controllers': path.resolve(__dirname, './assets/controllers')
    }
  }
});
