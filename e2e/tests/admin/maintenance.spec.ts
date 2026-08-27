import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';

test.describe('Admin: Maintenance', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('change the site into maintenance mode and verify it', async ({ page }) => {
        await test.step('change the site into maintenance mode and verify it', async () => {
            await new ConsultationHomePage(page).open();
            await loginAsStdAdmin(page);
            await expect(page.locator('.consultationwideWarning').getByText('Der Wartungsmodus ist aktiv.').filter({ visible: true })).toHaveCount(0);

            await page.locator('#adminLink').click();
            await page.locator('#consultationLink').click();
            await expect(page.locator('#maintenanceMode')).not.toBeChecked();
            await page.locator('#maintenanceMode').first().check();
            await page.locator('#consultationSettingsForm [name="save"]').click();
            await expect(page.locator('.consultationwideWarning')).toContainText(
                'Der Wartungsmodus ist aktiv.',
            );

            await logout(page);
            await page.goto('/stdparteitag/std-parteitag');
            await test.step('see the maintenance message', async () => {
                await expect(page.locator('h1').getByText('TEST2').filter({ visible: true })).toHaveCount(0);
                await expect(page.locator('h1')).toContainText('Wartungsmodus');
                await expect(page.locator('.consultationwideWarning').getByText('Der Wartungsmodus ist aktiv.').filter({ visible: true })).toHaveCount(0);
            });
        });

        await test.step('try to see the site as a regular user during maintenance', async () => {
            await page.goto('/stdparteitag/std-parteitag');
            await test.step('verify that the maintenance mode is deactivated', async () => {
                await expect(page.locator('h1').getByText('TEST2').filter({ visible: true })).toHaveCount(0);
                await expect(page.locator('h1')).toContainText('Wartungsmodus');
                await expect(page.locator('.consultationwideWarning').getByText('Der Wartungsmodus ist aktiv.').filter({ visible: true })).toHaveCount(0);
            });
        });

        await test.step('deactivate the maintenance mode and verify it', async () => {
            await logout(page);
            await loginAsStdAdmin(page);
            await expect(page.locator('.consultationwideWarning')).toContainText(
                'Der Wartungsmodus ist aktiv.',
            );
            await page.locator('#adminLink').click();
            await page.locator('#consultationLink').click();
            await expect(page.locator('#maintenanceMode')).toBeChecked();
            await page.locator('#maintenanceMode').first().uncheck();
            await page.locator('#consultationSettingsForm [name="save"]').click();
            await expect(page.locator('.consultationwideWarning').getByText('Der Wartungsmodus ist aktiv.').filter({ visible: true })).toHaveCount(0);

            await logout(page);
            await new ConsultationHomePage(page).open();
            await expect(page.locator('h1')).toContainText('TEST2');
            await expect(page.locator('h1').getByText('Wartungsmodus').filter({ visible: true })).toHaveCount(0);
        });
    });
});