import { test, expect } from '../../fixtures';

import { AmendmentPage } from '../../pages/AmendmentPage';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';

test.describe('Amendments: LikeDislike', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('verify that supporting amendments is disabled by default', async ({ page }) => {
        await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
        await expect(page.locator('section.likes')).not.toBeVisible();
    });

    test('enable supporting amendments for logged in users', async ({ page }) => {
        await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('.motionType1').click();
        await page.locator('#typePolicySupportAmendments').selectOption('2');
        await page.locator('.amendmentLike').check();
        await page.locator('.amendmentDislike').check();
        await page.locator('.adminTypeForm [name="save"]').click();
        await logout(page);
    });

    test('check that only logged in users can support amendments', async ({ page }) => {
        await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
        await expect(page.locator('body')).toContainText(
            'Du musst dich einloggen, um Anträge unterstützen zu können.',
        );
    });

    test('support the amendment as a logged in user', async ({ page }) => {
        await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
        await loginAsStdUser(page);
        await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
        await expect(page.locator('body')).not.toContainText(
            'Du musst dich einloggen, um Anträge unterstützen zu können.',
        );
        await page.locator('section.likes form [name="motionLike"]').click();
        await expect(page.locator('body')).toContainText(
            'Du stimmst diesem Änderungsantrag nun zu.',
        );
        await expect(page.locator('section.likes')).toContainText('Testuser');
        await expect(page.locator('section.likes')).toContainText('Du!');
        await expect(page.locator('section.likes')).not.toContainText('Ablehnung:');
        await expect(page.locator('section.likes')).toContainText('Zustimmung:');
    });

    test('watch this page logged out', async ({ page }) => {
        await logout(page);
        await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
        await expect(page.locator('section.likes')).toContainText('Testuser');
        await expect(page.locator('section.likes')).not.toContainText('Du!');
    });

    test('withdraw my support', async ({ page }) => {
        await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
        await loginAsStdUser(page);
        await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
        await page.locator('section.likes form [name="motionSupportRevoke"]').click();
        await expect(page.locator('body')).toContainText(
            'Du stehst diesem Änderungsantrag wieder neutral gegenüber.',
        );
        await expect(page.locator('section.likes')).not.toContainText('Testuser');
        await expect(page.locator('section.likes')).not.toContainText('Ablehnung:');
        await expect(page.locator('section.likes')).not.toContainText('Zustimmung:');
    });

    test('object to this amendment', async ({ page }) => {
        await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
        await page.locator('section.likes form [name="motionDislike"]').click();
        await expect(page.locator('body')).toContainText(
            'Du lehnst diesen Änderungsantrag nun ab.',
        );
        await expect(page.locator('section.likes')).toContainText('Testuser');
        await expect(page.locator('section.likes')).toContainText('Du!');
        await expect(page.locator('section.likes')).toContainText('Ablehnung:');
        await expect(page.locator('section.likes')).not.toContainText('Zustimmung:');
    });

    test('withdraw my objection', async ({ page }) => {
        await page.locator('section.likes form [name="motionSupportRevoke"]').click();
        await expect(page.locator('body')).toContainText(
            'Du stehst diesem Änderungsantrag wieder neutral gegenüber.',
        );
        await expect(page.locator('section.likes')).not.toContainText('Testuser');
        await expect(page.locator('section.likes')).not.toContainText('Ablehnung:');
        await expect(page.locator('section.likes')).not.toContainText('Zustimmung:');
    });
});