import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';

const POLICY_LOGGED_IN = '2';

async function enableSupporting(page: import('@playwright/test').Page): Promise<void> {
    const motionType = new AdminMotionTypePage(page);
    await motionType.open({ motionTypeId: 1 });
    await page.locator('#typePolicySupportMotions').selectOption(POLICY_LOGGED_IN);
    await page.locator('.motionLike').check();
    await page.locator('.motionDislike').check();
    await page.locator('.adminTypeForm [name="save"]').first().click();
}

test.describe('Motion likes and dislikes', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('supporting motions is disabled by default', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoMotionView(3);
        await expect(page.locator('section.likes')).toHaveCount(0);
    });

    test('only logged in users may support motions', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoMotionView(3);
        await loginAsStdAdmin(page);
        await enableSupporting(page);

        await home.open();
        await logout(page);
        await home.gotoMotionView(3);
        await expect(page.locator('body')).toContainText(
            'Du musst dich einloggen, um Anträge unterstützen zu können.',
        );
    });

    test('a user can support, revoke, object and revoke again', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoMotionView(3);
        await loginAsStdAdmin(page);
        await enableSupporting(page);

        await home.open();
        await logout(page);
        await home.gotoMotionView(3);

        await loginAsStdUser(page);
        await expect(page.locator('body')).not.toContainText(
            'Du musst dich einloggen, um Anträge unterstützen zu können.',
        );

        await page.locator('section.likes form [name="motionLike"]').click();
        await expect(page.locator('body')).toContainText('Du stimmst diesem Antrag nun zu.');
        await expect(page.locator('section.likes')).toContainText('Testuser');
        await expect(page.locator('section.likes')).toContainText('Du!');
        await expect(page.locator('section.likes')).not.toContainText('Ablehnung:');
        await expect(page.locator('section.likes')).toContainText('Zustimmung:');

        await logout(page);
        await expect(page.locator('section.likes')).toContainText('Testuser');
        await expect(page.locator('section.likes')).not.toContainText('Du!');

        await loginAsStdUser(page);
        await page.locator('section.likes form [name="motionSupportRevoke"]').click();
        await expect(page.locator('body')).toContainText(
            'Du stehst diesem Antrag wieder neutral gegenüber.',
        );
        await expect(page.locator('section.likes')).not.toContainText('Testuser');

        await page.locator('section.likes form [name="motionDislike"]').click();
        await expect(page.locator('body')).toContainText('Du lehnst diesen Antrag nun ab.');
        await expect(page.locator('section.likes')).toContainText('Testuser');
        await expect(page.locator('section.likes')).toContainText('Du!');
        await expect(page.locator('section.likes')).toContainText('Ablehnung:');
        await expect(page.locator('section.likes')).not.toContainText('Zustimmung:');

        await page.locator('section.likes form [name="motionSupportRevoke"]').click();
        await expect(page.locator('body')).toContainText(
            'Du stehst diesem Antrag wieder neutral gegenüber.',
        );
        await expect(page.locator('section.likes')).not.toContainText('Testuser');
        await expect(page.locator('section.likes')).not.toContainText('Ablehnung:');
        await expect(page.locator('section.likes')).not.toContainText('Zustimmung:');
    });
});
