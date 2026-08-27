import { test, expect } from '../../fixtures';
import { MotionPage } from '../../pages/MotionPage';
import { loginAsStdAdmin, logout } from '../../utils/auth';

test.describe('Amendments: DisableEditorialAmendments', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('disable editorial amendments', async ({ page }) => {
        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await test.step('check if editorial amendments are allowed', async () => {
            await page.locator('#sidebar .amendmentCreate a').click();
            await expect(page.locator('.editorialChange').first()).toBeVisible();

            await loginAsStdAdmin(page);
            await page.locator('#adminLink').click();
            await page.locator('#consultationLink').click();
            await expect(page.locator('#editorialAmendments')).toBeChecked();
            await page.locator('#editorialAmendments').first().uncheck();
            await page.locator('#consultationSettingsForm [name="save"]').click();
            await expect(page.locator('#editorialAmendments')).not.toBeChecked();

            await logout(page);
            await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
            await page.locator('#sidebar .amendmentCreate a').click();
            await expect(page.locator('.editorialChange').filter({ visible: true })).toHaveCount(0);
        });
    });
});