import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Appearance: translation', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('change and revert a translation string', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);

        await page.locator('#adminLink').click();
        await page.locator('#translationLink').click();

        await expect(page.locator('#homeLink')).toContainText('Start');
        await expect(page.locator("textarea[name='string[Home]']")).toBeVisible();

        await page.locator("textarea[name='string[Home]']").fill('Home');
        await page.locator('#translationForm [name="save"]').click();

        await expect(page.locator('#homeLink')).not.toContainText('Start');
        await expect(page.locator('#homeLink')).toContainText('Home');
        await expect(page.locator("textarea[name='string[Home]']")).toHaveValue('Home');

        await page.locator("textarea[name='string[Home]']").fill('');
        await page.locator('#translationForm [name="save"]').click();

        await expect(page.locator('#homeLink')).toContainText('Start');
        await expect(page.locator('#homeLink')).not.toContainText('Home');
        await expect(page.locator("textarea[name='string[Home]']")).toHaveValue('');
    });
});
