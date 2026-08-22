import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Appearance: custom theme', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('custom theme is editable, theme changes apply', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);

        await page.goto('/stdparteitag/std-parteitag/admin/appearance');
        await page.locator('.editThemeLink').click();

        const menuLinkColor = await page.evaluate(() => {
            return getComputedStyle(document.querySelector('#motionListLink')!).color;
        });
        expect(menuLinkColor).toBe('rgb(75, 112, 0)');
        const borderRadius = await page.evaluate(() => {
            return getComputedStyle(
                document.querySelector('.antragsgruen-width-main.well')!,
            ).borderTopLeftRadius;
        });
        expect(borderRadius).toBe('10px');

        await expect(page.locator('#stylesheet-menuLink')).toHaveValue('#4B7000');

        await page.goto('/stdparteitag/std-parteitag/admin/appearance');
        await page.evaluate(() => {
            const el = document.querySelector(
                '.thumbnailedLayoutSelector .layout.layout-dbjr',
            );
            if (el) {
                el.dispatchEvent(
                    new MouseEvent('click', { bubbles: true, cancelable: true, view: window }),
                );
            }
        });
        await page.locator('.editThemeLink').click();
        await expect(page.locator('#stylesheet-menuLink')).toHaveValue('#646464');

        await page.locator('#stylesheet-contentBorderRadius').fill('5');
        await page.evaluate(() => {
            const el = document.querySelector('#stylesheet-menuLink') as HTMLInputElement;
            el.value = '#FF0000';
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        });
        await page.locator('.themingForm [name="save"]').click();

        const newMenuLinkColor = await page.evaluate(() => {
            return getComputedStyle(document.querySelector('#motionListLink')!).color;
        });
        expect(newMenuLinkColor).toBe('rgb(255, 0, 0)');
        const newBorderRadius = await page.evaluate(() => {
            return getComputedStyle(
                document.querySelector('.antragsgruen-width-main.well')!,
            ).borderTopLeftRadius;
        });
        expect(newBorderRadius).toBe('5px');

        await page.goto('/stdparteitag/std-parteitag/admin/appearance');
        await expect(page.locator('.customThemeSelector input')).toBeChecked();
        await page.evaluate(() => {
            const el = document.querySelector("input[value='layout-classic']") as HTMLElement;
            if (el && el.parentElement) {
                el.parentElement.click();
            }
        });
        await page
            .locator("input[name='siteSettings[siteLayout]'][value='layout-classic']")
            .check();
        await page.locator('#consultationAppearanceForm [name="save"]').click();

        const classicMenuLinkColor = await page.evaluate(() => {
            return getComputedStyle(document.querySelector('#motionListLink')!).color;
        });
        expect(classicMenuLinkColor).toBe('rgb(75, 112, 0)');
        const classicBorderRadius = await page.evaluate(() => {
            return getComputedStyle(
                document.querySelector('.antragsgruen-width-main.well')!,
            ).borderTopLeftRadius;
        });
        expect(classicBorderRadius).toBe('10px');

        await page.locator('.editThemeLink').click();
        await expect(page.locator('.bootbox-prompt')).toHaveCount(0);
        await page.locator('.btnResetTheme').click();
        await page.waitForTimeout(1000);
        await expect(page.locator('.bootbox-prompt')).toBeVisible();
        await page
            .locator("input[name='bootbox-radio'][value='layout-dbjr']")
            .check();
        await page.evaluate(() => {
            const el = document.querySelector('.bootbox-accept');
            if (el) {
                el.dispatchEvent(
                    new MouseEvent('click', { bubbles: true, cancelable: true, view: window }),
                );
            }
        });
        await page.waitForTimeout(1000);
        await expect(page.locator('#stylesheet-menuLink')).toHaveValue('#646464');
        await expect(page.locator('#stylesheet-contentBorderRadius')).toHaveValue('10');
        const dbjrMenuLinkColor = await page.evaluate(() => {
            return getComputedStyle(document.querySelector('#motionListLink')!).color;
        });
        expect(dbjrMenuLinkColor).toBe('rgb(100, 100, 100)');
        const dbjrBorderRadius = await page.evaluate(() => {
            return getComputedStyle(
                document.querySelector('.antragsgruen-width-main.well')!,
            ).borderTopLeftRadius;
        });
        expect(dbjrBorderRadius).toBe('10px');

        await page.locator('.btnResetTheme').click();
        await page.waitForTimeout(1000);
        await expect(page.locator('.bootbox-prompt')).toBeVisible();
        await page
            .locator("input[name='bootbox-radio'][value='layout-classic']")
            .check();
        await page.evaluate(() => {
            const el = document.querySelector('.bootbox-accept');
            if (el) {
                el.dispatchEvent(
                    new MouseEvent('click', { bubbles: true, cancelable: true, view: window }),
                );
            }
        });
        await page.waitForTimeout(1000);
        await expect(page.locator('#stylesheet-menuLink')).toHaveValue('#4B7000');
        await expect(page.locator('#stylesheet-contentBorderRadius')).toHaveValue('10');

        const finalMenuLinkColor = await page.evaluate(() => {
            return getComputedStyle(document.querySelector('#motionListLink')!).color;
        });
        expect(finalMenuLinkColor).toBe('rgb(75, 112, 0)');
        const finalBorderRadius = await page.evaluate(() => {
            return getComputedStyle(
                document.querySelector('.antragsgruen-width-main.well')!,
            ).borderTopLeftRadius;
        });
        expect(finalBorderRadius).toBe('10px');
    });
});
