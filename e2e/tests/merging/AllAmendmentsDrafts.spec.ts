import { test, expect } from '../../fixtures';
import { setAmendmentStatus } from '../../utils/test-api';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';

const SUBDOMAIN = 'stdparteitag';
const CONSULTATION = 'std-parteitag';

test.describe('Merging: All amendments drafts', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('merge amendments, save draft as public, reject amendment, restore draft', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.gotoMotionView(2);
        await expect(page.locator('.motionDataTable .mergingDraft').filter({ visible: true })).toHaveCount(0);

        await loginAsStdAdmin(page);
        await page.locator('.sidebarActions .mergeamendments a').click();
        await expect(page.locator('.draftExistsAlert .btn').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('body')).toContainText('Einpflegen beginnen');
        await page.locator('#markAmendment3').first().check();
        await page.locator('#markAmendment1').first().check();
        await page.locator('#markAmendment270').first().check();
        await page.locator('.mergeAllRow .btn-primary').click();
        await expect(page.locator('body')).toContainText('annehmen oder ablehnen');
        await page.waitForTimeout(1000);

        await page.evaluate(() => {
            const hint = document.querySelector('[data-cid="2"] .appendHint') as HTMLElement | null;
            if (hint) {
                hint.dispatchEvent(new MouseEvent('mouseover', { bubbles: true }));
            }
            const btn = document.querySelector('button.reject') as HTMLElement | null;
            if (btn) btn.click();
        });

        await expect(page.locator('#draftSavingPanel').first()).toBeVisible();
        await test.step('enable public drafts', async () => {
            await expect(page.locator('#draftSavingPanel .publicLink').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#draftSavingPanel input[name=public]')).not.toBeChecked();

            await page.waitForTimeout(1000);
            await page.evaluate(() => {
                const input = document.querySelector(
                    '#draftSavingPanel input[name=public]',
                ) as HTMLElement | null as HTMLInputElement | null;
                if (input) {
                    input.checked = true;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
            await page.waitForTimeout(1000);
            await expect(page.locator('#draftSavingPanel .publicLink').first()).toBeVisible();

            await page.evaluate(() => {
                window.removeEventListener('beforeunload', () => undefined);
            });
            await logout(page);

            await home.gotoMotionView(2);
            await expect(page.locator('.motionDataTable .mergingDraft').first()).toBeVisible();
            await page.locator('.motionDataTable .mergingDraft a').click();

            await expect(page.locator('.alert')).toContainText('Dies ist kein beschlossener Antrag');
            await expect(page.locator('#updateBtn').first()).toBeVisible();
            await expect(page.locator('.ice-ins')).toContainText('Neue Zeile');
            await expect(page.locator('.ice-ins').getByText('Neuer Punkt').filter({ visible: true })).toHaveCount(0);
        });

        await test.step('see the info windows', async () => {
            await expect(page.locator('.popover-amendment-ajax').filter({ visible: true })).toHaveCount(0);
            await page.locator('#sections_2 ul:nth-child(2) li').first().click();
            await page.waitForTimeout(1000);
            await expect(page.locator('.popover-amendment-ajax').first()).toBeVisible();
            await expect(page.locator('.popover-amendment-ajax')).toContainText('Tester');

            await page.locator('#sections_2 p:nth-of-type(3) ins').first().click();
            await page.waitForTimeout(1000);
            await expect(page.locator('.popover-amendment-ajax').first()).toBeVisible();
            await expect(page.locator('.popover-amendment-ajax')).toContainText('Testadmin');

            await home.gotoMotionView(2);

            await loginAsStdAdmin(page);
        });

        await page.locator('.sidebarActions .mergeamendments a').click();
        await expect(page.locator('.draftExistsAlert .btn').first()).toBeVisible();

        await expect(page.locator('.draftExistsAlert').first()).toBeVisible();
        await page.locator('.draftExistsAlert .btn-primary').click();

        await page.waitForTimeout(1000);
        await expect(page.locator('.ice-ins')).toContainText('Neue Zeile');
        await expect(page.locator('.ice-ins').getByText('Neuer Punkt').filter({ visible: true })).toHaveCount(0);

        await page.evaluate(() => {
            const hint = document.querySelector('[data-cid="1"] .appendHint') as HTMLElement | null;
            if (hint) {
                hint.dispatchEvent(new MouseEvent('mouseover', { bubbles: true }));
            }
            const btn = document.querySelector('button.accept') as HTMLElement | null;
            if (btn) btn.click();
        });
        await expect(page.locator('body')).toContainText('Neue Zeile');
        await expect(page.locator('.ice-ins').getByText('Neue Zeile').filter({ visible: true })).toHaveCount(0);

        await page.locator('#draftSavingPanel .saveDraft').click();
        await page.waitForTimeout(1000);

        await page.evaluate(() => {
            window.removeEventListener('beforeunload', () => undefined);
        });

        await home.gotoMotionView(2);
        await test.step('merge the amendments', async () => {
            await page.locator('.sidebarActions .mergeamendments a').click();
            await expect(page.locator('.draftExistsAlert .btn').first()).toBeVisible();

            await expect(page.locator('.draftExistsAlert').first()).toBeVisible();
            await page.locator('.draftExistsAlert .btn-primary').click();

            await page.waitForTimeout(1000);

            await expect(page.locator('body')).toContainText('Neue Zeile');
            await expect(page.locator('.ice-ins').getByText('Neue Zeile').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('body')).not.toContainText('Neuer Punkt', { useInnerText: true });

            await page.evaluate(() => {
                window.removeEventListener('beforeunload', () => undefined);
            });
            await home.gotoMotionView(2);
        });

        await test.step('restore the second draft', async () => {
            await page.locator('.sidebarActions .mergeamendments a').click();
        });

        await test.step('begin anew', async () => {
            await expect(page.locator('.draftExistsAlert .btn').first()).toBeVisible();

            await page.locator('#markAmendment3').first().check();
            await page.locator('#markAmendment1').first().check();
            await page.locator('#markAmendment270').first().check();

            await page.locator('button.discard').click();
            await page.waitForTimeout(1000);
            await expect(page.locator('.ice-ins')).toContainText('Neuer Punkt');
            await expect(page.locator('.ice-ins')).toContainText('Neue Zeile');
        });

    });
});
