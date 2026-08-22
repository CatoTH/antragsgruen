import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: 1,
    reporter: process.env.CI
        ? [
              ['github'],
              ['html', { open: 'never', outputFolder: 'playwright-report' }],
              ['junit', { outputFile: 'test-results/junit.xml' }],
          ]
        : [['list'], ['html', { open: 'never', outputFolder: 'playwright-report' }]],
    outputDir: 'test-results',

    use: {
        baseURL: process.env.E2E_BASE_URL || 'http://test.antragsgruen.test',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        viewport: { width: 1280, height: 1024 },
        actionTimeout: 10_000,
        navigationTimeout: 30_000,
    },

    expect: {
        timeout: 5_000,
    },

    timeout: 60_000,

    projects: [
        {
            name: 'setup',
            testDir: '.',
            testMatch: /.*\.setup\.ts/,
        },
        {
            name: 'chromium',
            use: {
                ...devices['Desktop Chrome'],
                channel: undefined,
                storageState: '.auth/std-admin.json',
            },
            dependencies: ['setup'],
        },
        {
            name: 'firefox',
            use: {
                ...devices['Desktop Firefox'],
                storageState: '.auth/std-admin.json',
            },
            dependencies: ['setup'],
        },
        {
            name: 'webkit',
            use: {
                ...devices['Desktop Safari'],
                storageState: '.auth/std-admin.json',
            },
            dependencies: ['setup'],
        },
    ],
});