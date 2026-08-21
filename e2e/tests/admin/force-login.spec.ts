import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/BasePage';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';

test.describe('Admin: ForceLogin', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('enforce login and log in as a regular user', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('h1')).toContainText('Test2');

        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('#consultationLink').click();
        await page.locator('.forceLogin input').check();
        await page.locator('#consultationSettingsForm [name="save"]').click();
        await logout(page);

        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('h1')).toContainText('Login');

        await loginAsStdUser(page);
        await expect(page.locator('h1')).toContainText('Test2');
    });

    test('disable it again', async ({ page }) => {
        await logout(page);
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('#consultationLink').click();
        await page.locator('.forceLogin input').uncheck();
        await page.locator('#consultationSettingsForm [name="save"]').click();
        await logout(page);

        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('h1')).toContainText('Test2');
    });
});