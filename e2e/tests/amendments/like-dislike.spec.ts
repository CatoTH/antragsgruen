import { test, expect } from '../../fixtures';

import { AmendmentPage } from '../../pages/AmendmentPage';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';

test.describe('Amendments: LikeDislike', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('verify that supporting amendments is disabled by default', async ({ page }) => {
        await test.step('verify that supporting amendments is disabled by default', async () => {
            await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
            await expect(page.locator('section.likes').filter({ visible: true })).toHaveCount(0);
        });

        await test.step('enable supporting amendments for logged in users', async () => {
            await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
            await loginAsStdAdmin(page);
            await page.locator('#adminLink').click();
            await page.locator('.motionType1').click();
            await page.locator('#typePolicySupportAmendments').first().selectOption('2');
            await page.locator('.amendmentLike').first().check();
            await page.locator('.amendmentDislike').first().check();
            await page.locator('.adminTypeForm [name="save"]').click();
            await logout(page);
        });

        await test.step('check that only logged in users can support amendments', async () => {
            await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
            await test.step('check if only logged in users can support amendments', async () => {
                await expect(page.locator('body')).toContainText(
                    'Du musst dich einloggen, um Anträge unterstützen zu können.',
                );
            });
        });

        await test.step('support the amendment as a logged in user', async () => {
            await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
            await loginAsStdUser(page);
            await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
            await expect(page.locator('body')).not.toContainText(
                'Du musst dich einloggen, um Anträge unterstützen zu können.',
                { useInnerText: true },
            );
            await test.step('object to this motion', async () => {
                await page.locator('section.likes form [name="motionLike"]').click();
                await expect(page.locator('body')).toContainText(
                    'Du stimmst diesem Änderungsantrag nun zu.',
                );
                await expect(page.locator('section.likes')).toContainText('Testuser');
                await expect(page.locator('section.likes')).toContainText('Du!');
                await expect(page.locator('section.likes').getByText('Ablehnung:').filter({ visible: true })).toHaveCount(0);
                await expect(page.locator('section.likes')).toContainText('Zustimmung:');
            });
        });

        await test.step('watch this page logged out', async () => {
            await logout(page);
            await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
            await expect(page.locator('section.likes')).toContainText('Testuser');
            await expect(page.locator('section.likes').getByText('Du!').filter({ visible: true })).toHaveCount(0);
        });

        await test.step('withdraw my support', async () => {
            await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
            await loginAsStdUser(page);
            await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
            await page.locator('section.likes form [name="motionSupportRevoke"]').click();
            await expect(page.locator('body')).toContainText(
                'Du stehst diesem Änderungsantrag wieder neutral gegenüber.',
            );
            await expect(page.locator('section.likes').getByText('Testuser').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('section.likes').getByText('Ablehnung:').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('section.likes').getByText('Zustimmung:').filter({ visible: true })).toHaveCount(0);
        });

        await test.step('object to this amendment', async () => {
            await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
            await page.locator('section.likes form [name="motionDislike"]').click();
            await expect(page.locator('body')).toContainText(
                'Du lehnst diesen Änderungsantrag nun ab.',
            );
            await expect(page.locator('section.likes')).toContainText('Testuser');
            await expect(page.locator('section.likes')).toContainText('Du!');
            await expect(page.locator('section.likes')).toContainText('Ablehnung:');
            await expect(page.locator('section.likes').getByText('Zustimmung:').filter({ visible: true })).toHaveCount(0);
        });

        await test.step('withdraw my objection', async () => {
            await page.locator('section.likes form [name="motionSupportRevoke"]').click();
            await expect(page.locator('body')).toContainText(
                'Du stehst diesem Änderungsantrag wieder neutral gegenüber.',
            );
            await expect(page.locator('section.likes').getByText('Testuser').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('section.likes').getByText('Ablehnung:').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('section.likes').getByText('Zustimmung:').filter({ visible: true })).toHaveCount(0);
        });
    });
});