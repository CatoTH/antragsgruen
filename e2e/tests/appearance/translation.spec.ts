import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Appearance: translation', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('change and revert a translation string', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);

        await test.step('Go to admin administration', async () => {
            await page.locator('#adminLink').click();
            await page.locator('#translationLink').click();
        });

        await test.step('Change the home link', async () => {
            await expect(page.locator('#homeLink')).toContainText('Start');
            await expect(page.locator("textarea[name='string[Home]']").first()).toBeVisible();

            await page.locator("textarea[name='string[Home]']").first().fill('Home');
        });

        await test.step('Revert the change', async () => {
            await page.locator('#translationForm [name="save"]').click();

            await expect(page.locator('#homeLink').getByText('Start').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#homeLink')).toContainText('Home');
            await expect(page.locator("textarea[name='string[Home]']")).toHaveValue('Home');

            await page.locator("textarea[name='string[Home]']").first().fill('');
            await page.locator('#translationForm [name="save"]').click();

            await expect(page.locator('#homeLink')).toContainText('Start');
            await expect(page.locator('#homeLink').getByText('Home').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator("textarea[name='string[Home]']")).toHaveValue('');
        });
    });
});
