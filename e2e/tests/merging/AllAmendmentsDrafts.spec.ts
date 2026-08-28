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
        await expect(page.locator('.motionDataTable .mergingDraft')).toHaveCount(0);

        await loginAsStdAdmin(page);
        await page.locator('.sidebarActions .mergeamendments a').click();
        await expect(page.locator('.draftExistsAlert .btn')).toHaveCount(0);
        await expect(page.locator('body')).toContainText('Einpflegen beginnen');
        await page.locator('#markAmendment3').check();
        await page.locator('#markAmendment1').check();
        await page.locator('#markAmendment270').check();
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

        await expect(page.locator('#draftSavingPanel')).toBeVisible();
        await expect(page.locator('#draftSavingPanel .publicLink')).toHaveCount(0);
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
        await expect(page.locator('#draftSavingPanel .publicLink')).toBeVisible();

        await page.evaluate(() => {
            window.removeEventListener('beforeunload', () => undefined);
        });
        await logout(page);

        await home.gotoMotionView(2);
        await expect(page.locator('.motionDataTable .mergingDraft')).toBeVisible();
        await page.locator('.motionDataTable .mergingDraft a').click();

        await expect(page.locator('.alert')).toContainText('Dies ist kein beschlossener Antrag');
        await expect(page.locator('#updateBtn')).toBeVisible();
        await expect(page.locator('.ice-ins')).toContainText('Neue Zeile');
        await expect(page.locator('.ice-ins')).not.toContainText('Neuer Punkt');

        await expect(page.locator('.popover-amendment-ajax')).toHaveCount(0);
        await page.locator('#sections_2 ul:nth-child(2) li').first().click();
        await page.waitForTimeout(1000);
        await expect(page.locator('.popover-amendment-ajax')).toBeVisible();
        await expect(page.locator('.popover-amendment-ajax')).toContainText('Tester');

        await page.locator('#sections_2 p:nth-of-type(3) ins').first().click();
        await page.waitForTimeout(1000);
        await expect(page.locator('.popover-amendment-ajax')).toBeVisible();
        await expect(page.locator('.popover-amendment-ajax')).toContainText('Testadmin');

        await home.gotoMotionView(2);

        await loginAsStdAdmin(page);
        await page.locator('.sidebarActions .mergeamendments a').click();
        await expect(page.locator('.draftExistsAlert .btn')).toBeVisible();

        await expect(page.locator('.draftExistsAlert')).toBeVisible();
        await page.locator('.draftExistsAlert .btn-primary').click();

        await page.waitForTimeout(1000);
        await expect(page.locator('.ice-ins')).toContainText('Neue Zeile');
        await expect(page.locator('.ice-ins')).not.toContainText('Neuer Punkt');

        await page.evaluate(() => {
            const hint = document.querySelector('[data-cid="1"] .appendHint') as HTMLElement | null;
            if (hint) {
                hint.dispatchEvent(new MouseEvent('mouseover', { bubbles: true }));
            }
            const btn = document.querySelector('button.accept') as HTMLElement | null;
            if (btn) btn.click();
        });
        await expect(page.locator('body')).toContainText('Neue Zeile');
        await expect(page.locator('.ice-ins')).not.toContainText('Neue Zeile');

        await page.locator('#draftSavingPanel .saveDraft').click();
        await page.waitForTimeout(1000);

        await page.evaluate(() => {
            window.removeEventListener('beforeunload', () => undefined);
        });

        await home.gotoMotionView(2);
        await page.locator('.sidebarActions .mergeamendments a').click();
        await expect(page.locator('.draftExistsAlert .btn')).toBeVisible();

        await expect(page.locator('.draftExistsAlert')).toBeVisible();
        await page.locator('.draftExistsAlert .btn-primary').click();

        await page.waitForTimeout(1000);

        await expect(page.locator('body')).toContainText('Neue Zeile');
        await expect(page.locator('.ice-ins')).not.toContainText('Neue Zeile');
        await expect(page.locator('body')).not.toContainText('Neuer Punkt');

        await page.evaluate(() => {
            window.removeEventListener('beforeunload', () => undefined);
        });
        await home.gotoMotionView(2);
        await page.locator('.sidebarActions .mergeamendments a').click();
        await expect(page.locator('.draftExistsAlert .btn')).toBeVisible();

        await page.locator('#markAmendment3').check();
        await page.locator('#markAmendment1').check();
        await page.locator('#markAmendment270').check();

        await page.locator('button.discard').click();
        await page.waitForTimeout(1000);
        await expect(page.locator('.ice-ins')).toContainText('Neuer Punkt');
        await expect(page.locator('.ice-ins')).toContainText('Neue Zeile');
    });
});
