import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';

test.describe('Merging: All amendments excluding some', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('select subset of amendments for merging', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.gotoMotionView(2);

        await loginAsStdAdmin(page);
        await test.step('merge the amendments', async () => {
            await page.locator('.sidebarActions .mergeamendments a').click();

            await expect(page.locator('body')).toContainText('Einpflegen beginnen');
            await page.locator('.toMergeAmendments #markAmendment1').first().check();
            await page.locator('.toMergeAmendments #markAmendment3').first().uncheck();
            await page.locator('.toMergeAmendments #markAmendment270').first().check();
            await page.locator('.toMergeAmendments #markAmendment272').first().check();
            await page.locator('.toMergeAmendments #markAmendment273').first().check();
            await page.locator('.toMergeAmendments #markAmendment274').first().check();
            await page.locator('.toMergeAmendments #markAmendment276').first().check();

            await page.locator('.mergeAllRow .btn-primary').click();
            await expect(page.locator('body')).toContainText('annehmen oder ablehnen');

            await page.waitForTimeout(1000);

            await expect(page.locator('.ice-ins')).toContainText('Neue Zeile');
            await expect(page.locator('body')).not.toContainText('Neuer Punkt', { useInnerText: true });

            await page.evaluate(() => {
                window.removeEventListener('beforeunload', () => undefined);
            });

            await home.gotoMotionView(2);
        });

        await test.step('try another combination', async () => {
            await page.locator('.sidebarActions .mergeamendments a').click();
            await page.locator('.toMergeAmendments #markAmendment1').first().check();
            await page.locator('.toMergeAmendments #markAmendment3').first().check();
            await page.locator('.toMergeAmendments #markAmendment270').first().uncheck();
            await page.locator('.toMergeAmendments #markAmendment272').first().check();
            await page.locator('.toMergeAmendments #markAmendment273').first().check();
            await page.locator('.toMergeAmendments #markAmendment274').first().check();
            await page.locator('.toMergeAmendments #markAmendment276').first().check();
            await page.locator('.mergeAllRow .btn-primary').click();

            await expect(page.locator('body')).toContainText('annehmen oder ablehnen');

            await page.waitForTimeout(1000);

            await expect(page.locator('body')).not.toContainText('Neue Zeile', { useInnerText: true });
            await expect(page.locator('.ice-ins')).toContainText('Neuer Punkt');
        });
    });
});
