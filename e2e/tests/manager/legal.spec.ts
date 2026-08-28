import { test, expect } from '../../fixtures';
import { loginAsGlobalAdmin } from '../../utils/auth';
import { setCkEditorContent } from '../../utils/dom';

test.describe('Manager: legal page editing', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('admin can edit legal page content', async ({ page }) => {
        await page.goto('/antragsgruen_sites/manager/index');
        await expect(page.locator('h1')).toContainText(/antragsgrün - das grüne antragstool/i);

        await page.locator('#legalLink').click();
        await expect(page.locator('h1')).toContainText('Impressum');
        await expect(page.locator('.editCaller')).toHaveCount(0);

        await loginAsGlobalAdmin(page);
        await expect(page.locator('.editCaller')).toBeVisible();

        await page.evaluate(() => {
            const el = document.querySelector('.contentPage .editCaller');
            if (el) {
                el.dispatchEvent(
                    new MouseEvent('click', { bubbles: true, cancelable: true, view: window }),
                );
            }
        });
        await page.waitForTimeout(2000);

        await expect(page.locator('.contentPage .textSaver button')).toBeVisible();
        await setCkEditorContent(page, 'stdTextHolder', '<b>Bold test</b>');

        await page.evaluate(() => {
            const btn = document.querySelector('.contentPage .textSaver button');
            if (btn) {
                btn.dispatchEvent(
                    new MouseEvent('click', { bubbles: true, cancelable: true, view: window }),
                );
            }
        });
        await page.waitForTimeout(1000);

        await expect(page.locator('.contentPage .textSaver button')).toHaveCount(0);
        await expect(page.locator('body')).toContainText('Bold test');

        await page.locator('#legalLink').click();
        await expect(page.locator('body')).toContainText('Bold test');

        await page.locator('#privacyLink').click();
        await expect(page.locator('h1')).toContainText('Datenschutz');
    });
});
