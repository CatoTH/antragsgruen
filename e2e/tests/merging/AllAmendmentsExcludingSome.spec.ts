import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/BasePage';

test.describe('Merging: All amendments excluding some', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('select subset of amendments for merging', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.gotoMotionView(2);

        await loginAsStdAdmin(page);
        await page.locator('.sidebarActions .mergeamendments a').click();

        await expect(page.locator('body')).toContainText('Einpflegen beginnen');
        await page.locator('.toMergeAmendments #markAmendment1').check();
        await page.locator('.toMergeAmendments #markAmendment3').uncheck();
        await page.locator('.toMergeAmendments #markAmendment270').check();
        await page.locator('.toMergeAmendments #markAmendment272').check();
        await page.locator('.toMergeAmendments #markAmendment273').check();
        await page.locator('.toMergeAmendments #markAmendment274').check();
        await page.locator('.toMergeAmendments #markAmendment276').check();

        await page.locator('.mergeAllRow .btn-primary').click();
        await expect(page.locator('body')).toContainText('annehmen oder ablehnen');

        await page.waitForTimeout(1000);

        await expect(page.locator('.ice-ins')).toContainText('Neue Zeile');
        await expect(page.locator('body')).not.toContainText('Neuer Punkt');

        await page.evaluate(() => {
            window.removeEventListener('beforeunload', () => undefined);
        });

        await home.gotoMotionView(2);
        await page.locator('.sidebarActions .mergeamendments a').click();
        await page.locator('.toMergeAmendments #markAmendment1').check();
        await page.locator('.toMergeAmendments #markAmendment3').check();
        await page.locator('.toMergeAmendments #markAmendment270').uncheck();
        await page.locator('.toMergeAmendments #markAmendment272').check();
        await page.locator('.toMergeAmendments #markAmendment273').check();
        await page.locator('.toMergeAmendments #markAmendment274').check();
        await page.locator('.toMergeAmendments #markAmendment276').check();
        await page.locator('.mergeAllRow .btn-primary').click();

        await expect(page.locator('body')).toContainText('annehmen oder ablehnen');

        await page.waitForTimeout(1000);

        await expect(page.locator('body')).not.toContainText('Neue Zeile');
        await expect(page.locator('.ice-ins')).toContainText('Neuer Punkt');
    });
});
