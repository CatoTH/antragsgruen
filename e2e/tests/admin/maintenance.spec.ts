import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';

test.describe('Admin: Maintenance', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('change the site into maintenance mode and verify it', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await expect(page.locator('.consultationwideWarning')).not.toContainText(
            'Der Wartungsmodus ist aktiv.',
        );

        await page.locator('#adminLink').click();
        await page.locator('#consultationLink').click();
        await expect(page.locator('#maintenanceMode')).not.toBeChecked();
        await page.locator('#maintenanceMode').check();
        await page.locator('#consultationSettingsForm [name="save"]').click();
        await expect(page.locator('.consultationwideWarning')).toContainText(
            'Der Wartungsmodus ist aktiv.',
        );

        await logout(page);
        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('h1')).not.toContainText('TEST2');
        await expect(page.locator('h1')).toContainText('Wartungsmodus');
        await expect(page.locator('.consultationwideWarning')).not.toContainText(
            'Der Wartungsmodus ist aktiv.',
        );
    });

    test('try to see the site as a regular user during maintenance', async ({ page }) => {
        await loginAsStdUser(page);
        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('h1')).not.toContainText('TEST2');
        await expect(page.locator('h1')).toContainText('Wartungsmodus');
        await expect(page.locator('.consultationwideWarning')).not.toContainText(
            'Der Wartungsmodus ist aktiv.',
        );
    });

    test('deactivate the maintenance mode and verify it', async ({ page }) => {
        await logout(page);
        await loginAsStdAdmin(page);
        await expect(page.locator('.consultationwideWarning')).toContainText(
            'Der Wartungsmodus ist aktiv.',
        );
        await page.locator('#adminLink').click();
        await page.locator('#consultationLink').click();
        await expect(page.locator('#maintenanceMode')).toBeChecked();
        await page.locator('#maintenanceMode').uncheck();
        await page.locator('#consultationSettingsForm [name="save"]').click();
        await expect(page.locator('.consultationwideWarning')).not.toContainText(
            'Der Wartungsmodus ist aktiv.',
        );

        await logout(page);
        await new ConsultationHomePage(page).open();
        await expect(page.locator('h1')).toContainText('TEST2');
        await expect(page.locator('h1')).not.toContainText('Wartungsmodus');
    });
});