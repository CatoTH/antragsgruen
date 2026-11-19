import { defineConfig } from '@playwright/test';
import { getFixtureBaseURL } from './tests/playwright/fixture-target.mjs';

export default defineConfig({
    testDir: './tests/playwright',
    testMatch: '**/*.spec.ts',
    outputDir: './tests/_output/playwright',
    use: {
        browserName: 'chromium',
        baseURL: getFixtureBaseURL(process.env.ANTRAGSGRUEN_PLAYWRIGHT_URL),
    },
});
