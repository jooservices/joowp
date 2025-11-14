import { defineConfig } from 'vitest/config';
import path from 'node:path';

export default defineConfig({
    test: {
        environment: 'jsdom',
        globals: true,
        coverage: {
            reporter: ['text', 'json-summary'],
            provider: 'v8',
            statements: 0.9,
            branches: 0.9,
            functions: 0.9,
            lines: 0.9,
            include: [
                'resources/js/sdk/lmStudio/store.ts',
                'resources/js/sdk/lmStudio/composables/**/*.ts',
            ],
        },
        include: ['resources/js/sdk/lmStudio/__tests__/**/*.test.ts'],
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
});
