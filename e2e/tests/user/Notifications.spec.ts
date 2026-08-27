import { test, expect } from '../../fixtures';
import { loginAsStdUser } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';

test.describe('User: notifications', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('change notification settings', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await test.step('go to the notifications', async () => {
            await page.locator('#sidebar .notifications a').click();
            await expect(page.locator('h1')).toContainText('Login');

            await new ConsultationHomePage(page).open();
            await loginAsStdUser(page);
            await page.locator('#sidebar .notifications a').click();
            await expect(page.locator('h1')).toContainText('Benachrichtigungen');
        });

        await test.step('change my settings', async () => {
            await expect(page.locator('.notiMotion input')).not.toBeChecked();
            await expect(page.locator('.notiAmendment input')).toBeChecked();
            await expect(
                page.locator("input[name='notifications[amendmentsettings]'][value='0']"),
            ).toBeChecked();
            await expect(page.locator('.notiComment input')).not.toBeChecked();
            await expect(page.locator('.commentSettings').filter({ visible: true })).toHaveCount(0);

            await page.locator('.notiMotion input').first().check();
            await page.locator('.notiComment input').first().check();
            await expect(page.locator('.commentSettings').first()).toBeVisible();
            await expect(
                page.locator("input[name='notifications[commentsetting]'][value='1']"),
            ).toBeChecked();
            await page
                .locator("input[name='notifications[commentsetting]'][value='2']")
                .check();

            await page.locator('.notificationForm [name="save"]').click();

            await expect(page.locator('.notiMotion input')).toBeChecked();
            await expect(page.locator('.notiComment input')).toBeChecked();
            await expect(page.locator('.commentSettings').first()).toBeVisible();
            await expect(
                page.locator("input[name='notifications[commentsetting]'][value='2']"),
            ).toBeChecked();

            await page.locator('.notiAmendment input').first().uncheck();
            await page.locator('.notiComment input').first().uncheck();
            await page.locator('.notificationForm [name="save"]').click();

            await expect(page.locator('.notiMotion input')).toBeChecked();
            await expect(page.locator('.notiAmendment input')).not.toBeChecked();
            await expect(page.locator('.amendmentSettings').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.notiComment input')).not.toBeChecked();
            await expect(page.locator('.commentSettings').filter({ visible: true })).toHaveCount(0);
        });
    });
});