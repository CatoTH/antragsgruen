import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { dispatchClick, setCkEditorContent } from '../../utils/dom';
import { FIRST_FREE_CONTENT_ID } from '../../utils/constants';
import { validateHTML } from '../../utils/validators';

test.describe('Appearance: edit help content', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create help page and edit home page welcome text', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');

        await test.step('Login as regular user', async () => {
            await expect(page.locator('#adminLink').getByText('Einstellungen').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.editCaller').filter({ visible: true })).toHaveCount(0);
            await expect(
                page.locator(`#mainmenu .page${FIRST_FREE_CONTENT_ID}`),
            ).not.toBeVisible();

            await loginAsStdAdmin(page);

            await page.goto('/stdparteitag/std-parteitag/admin');
        });

        await test.step('create the help page', async () => {
            await page.locator('#contentPages').click();
            await expect(page.locator('.editPage.help').filter({ visible: true })).toHaveCount(0);

            await page.locator('.createPage.help').click();
            await page.locator('.editCaller').click();
            await page.waitForTimeout(500);

            await setCkEditorContent(page, 'stdTextHolder', '<p>New text</p>');
            await dispatchClick(page, '.submitBtn');
            await page.waitForTimeout(100);

            await page.goto('/stdparteitag/std-parteitag');
        });

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
        await expect(page.locator('body')).not.toContainText('Hallo auf Antragsgrün', { useInnerText: true });
        await expect(page.locator('body')).toContainText('Bold test');

        await test.step('Go to the help page', async () => {
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
        });

        await test.step('Edit the content', async () => {
            await setCkEditorContent(page, 'stdTextHolder', '<b>Some arbitrary text</b>');

            await page.evaluate(() => {
                const btn = document.querySelector('.contentPage .textSaver button');
                if (btn) {
                    btn.dispatchEvent(
                        new MouseEvent('click', { bubbles: true, cancelable: true, view: window }),
                    );
                }
            });
        });

        await test.step('See the page as a normal user now', async () => {
            await expect(page.locator('body')).toContainText('Some arbitrary text');

            await page.reload();
            await expect(page.locator('body')).toContainText('Some arbitrary text');

            await logout(page);
            await expect(page.locator('body')).toContainText('Some arbitrary text');
            await expect(page.locator('.editCaller').filter({ visible: true })).toHaveCount(0);

            await validateHTML(page);
        });

    });
});
