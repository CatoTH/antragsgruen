import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { setCkEditorContent } from '../../utils/dom';
import { FIRST_FREE_CONTENT_ID } from '../../utils/constants';
import { validateHTML } from '../../utils/validators';

test.describe('Appearance: edit help content', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create help page and edit home page welcome text', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');

        await expect(page.locator('#adminLink')).not.toContainText('Einstellungen');
        await expect(page.locator('.editCaller')).toHaveCount(0);
        await expect(
            page.locator(`#mainmenu .page${FIRST_FREE_CONTENT_ID}`),
        ).toHaveCount(0);

        await loginAsStdAdmin(page);

        await page.goto('/stdparteitag/std-parteitag/admin');
        await page.locator('#contentPages').click();
        await expect(page.locator('.editPage.help')).toHaveCount(0);

        await page.locator('.createPage.help').click();
        await page.locator('.editCaller').click();
        await page.waitForTimeout(500);

        await setCkEditorContent(page, 'stdTextHolder', '<p>New text</p>');
        await page.locator('.submitBtn').click();
        await page.waitForTimeout(100);

        await page.goto('/stdparteitag/std-parteitag');

        await expect(
            page.locator(`#mainmenu .page${FIRST_FREE_CONTENT_ID}`),
        ).toBeVisible();
        await expect(page.locator('#adminLink')).toContainText('Einstellungen');
        await expect(page.locator('.editCaller').first()).toContainText('Bearbeiten');
        await expect(page.locator('body')).toContainText('Hallo auf Antragsgrün');

        await page.evaluate(() => {
            const el = document.querySelector('.contentPageWelcome .editCaller');
            if (el) {
                el.dispatchEvent(
                    new MouseEvent('click', { bubbles: true, cancelable: true, view: window }),
                );
            }
        });
        await page.waitForTimeout(1000);

        await setCkEditorContent(page, 'stdTextHolder', '<b>Bold test</b>');

        await page.evaluate(() => {
            const btn = document.querySelector('.contentPageWelcome .textSaver button');
            if (btn) {
                btn.dispatchEvent(
                    new MouseEvent('click', { bubbles: true, cancelable: true, view: window }),
                );
            }
        });
        await page.waitForTimeout(1000);

        await expect(page.locator('body')).toContainText('Bold test');

        await page.goto('/stdparteitag/std-parteitag');
        await expect(page.locator('body')).not.toContainText('Hallo auf Antragsgrün');
        await expect(page.locator('body')).toContainText('Bold test');

        await page.locator(`#mainmenu .page${FIRST_FREE_CONTENT_ID}`).click();
        await expect(page.locator('#adminLink')).toContainText('Einstellungen');
        await expect(page.locator('.editCaller').first()).toContainText('Bearbeiten');
        await expect(page.locator('h1')).toContainText('HILFE');

        await page.evaluate(() => {
            const el = document.querySelector('.contentPage .editCaller');
            if (el) {
                el.dispatchEvent(
                    new MouseEvent('click', { bubbles: true, cancelable: true, view: window }),
                );
            }
        });
        await page.waitForTimeout(2000);

        await setCkEditorContent(page, 'stdTextHolder', '<b>Some arbitrary text</b>');

        await page.evaluate(() => {
            const btn = document.querySelector('.contentPage .textSaver button');
            if (btn) {
                btn.dispatchEvent(
                    new MouseEvent('click', { bubbles: true, cancelable: true, view: window }),
                );
            }
        });

        await expect(page.locator('body')).toContainText('Some arbitrary text');

        await page.reload();
        await expect(page.locator('body')).toContainText('Some arbitrary text');

        await logout(page);
        await expect(page.locator('body')).toContainText('Some arbitrary text');
        await expect(page.locator('.editCaller')).toHaveCount(0);

        await validateHTML(page);
    });
});
