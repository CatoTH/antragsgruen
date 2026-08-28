import { test, expect } from '../../fixtures';
import { MotionPage } from '../../pages/MotionPage';
import { loginAsStdAdmin, logout } from '../../utils/auth';

test.describe('Amendments: CreateLogin', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('enforce login when creating an amendment', async ({ page }) => {
        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('.motionType1').click();
        await page.locator('#typePolicyAmendments').selectOption('2');
        await page.locator('.adminTypeForm [name="save"]').first().click();
        await logout(page);

        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await expect(page.locator('.sidebarActions .amendmentCreate')).toBeVisible();
        await page.locator('.sidebarActions .amendmentCreate a').click();
        await expect(page.locator('h1')).toContainText('Login');
    });
});