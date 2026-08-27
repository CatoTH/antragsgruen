import { test as setup } from '@playwright/test';

const AUTH_FILE = '.auth/std-admin.json';

setup('authenticate admin', async ({ page }) => {
    await page.goto('/stdparteitag/std-parteitag');
    await page.locator('#loginLink').waitFor({ state: 'visible' });
    await page.locator('#loginLink').click();
    await page.locator('h1').filter({ hasText: /LOGIN/i }).waitFor();
    await page.locator('#username').fill('testadmin@example.org');
    await page.locator('#passwordInput').fill('testadmin');
    await page.locator('#usernamePasswordForm [name="loginusernamepassword"]').click();
    await page.locator('#logoutLink').waitFor({ state: 'visible' });
    await page.context().storageState({ path: AUTH_FILE });
});