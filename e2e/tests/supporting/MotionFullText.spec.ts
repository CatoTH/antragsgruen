import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';

test.describe('Supporting: MotionFullText', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('full text supporter adder visible for admins only and works correctly', async ({ page }) => {
        await new ConsultationHomePage(page).open({ subdomain: 'bdk', consultationPath: 'bdk' });
        await loginAsStdUser(page);
        await page.locator('.createMotion').click();
        await expect(page.locator('.supporterData')).toBeVisible();
        await expect(page.locator('.fullTextAdder')).toHaveCount(0);
        await expect(page.locator('#supporterFullTextHolder')).toHaveCount(0);

        await new ConsultationHomePage(page).open({ subdomain: 'bdk', consultationPath: 'bdk' });
        await logout(page);
        await loginAsStdAdmin(page);
        await page.locator('.createMotion').click();
        await expect(page.locator('.supporterData')).toBeVisible();
        await expect(page.locator('.fullTextAdder')).toBeVisible();
        await expect(page.locator('#supporterFullTextHolder')).toHaveCount(0);
        await page.locator('.fullTextAdder button').click();
        await expect(page.locator('#supporterFullTextHolder')).toBeVisible();

        await page.locator('#supporterFullTextHolder textarea').fill('Tobias Hößl, KV München; Test 2');
        await page.locator('#supporterFullTextHolder .fullTextAdd').click();

        const name1 = await page.locator('.supporterRow').nth(0).locator('input.name').inputValue();
        const orga1 = await page.locator('.supporterRow').nth(0).locator('input.organization').inputValue();
        const name2 = await page.locator('.supporterRow').nth(1).locator('input.name').inputValue();
        expect(name1).toBe('Tobias Hößl');
        expect(orga1).toBe('KV München');
        expect(name2).toBe('Test 2');
    });
});