import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Merging: marking conflicts as handled', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('hide and show collisions in merge draft', async ({ page }) => {
        await page.goto('/stdparteitag/std-parteitag');
        await loginAsStdAdmin(page);
        await page.goto('/stdparteitag/std-parteitag/motion/321-o-zapft-is');

        await page.locator('#sidebar .mergeamendments a').click();
        await expect(page.locator('.draftExistsAlert')).toHaveCount(0);
        await page.waitForTimeout(200);
        await page.evaluate(() => {
            const btn = document.querySelector('.toMergeAmendments .selectAll') as HTMLElement | null;
            if (btn) {
                btn.dispatchEvent(new MouseEvent('click', { bubbles: true }));
            }
        });
        await page.locator('.mergeAllRow .btn-primary').click();
        await expect(
            page.locator('#paragraphWrapper_2_4 .collidingParagraph3'),
        ).toContainText('Woibbadinga noch da Giasinga');

        await page.evaluate(() => {
            const panel = document.querySelector('#draftSavingPanel') as HTMLElement | null as HTMLElement | null;
            if (panel) panel.style.display = 'none';
        });
        await page.locator('#paragraphWrapper_2_4 .collidingParagraph3 .hideCollision').click();
        await page.evaluate(() => {
            const panel = document.querySelector('#draftSavingPanel') as HTMLElement | null as HTMLElement | null;
            if (panel) panel.style.display = '';
        });

        await page.locator('#draftSavingPanel .saveDraft').click();
        await page.evaluate(() => {
            window.removeEventListener('beforeunload', () => undefined);
        });

        await page.waitForTimeout(1000);
        await expect(page.locator('#paragraphWrapper_2_4 .collidingParagraph3')).toHaveCount(0);

        await page.goto('/stdparteitag/std-parteitag/motion/321-o-zapft-is');
        await test.step('show the conflict again and save the draft', async () => {
            await page.locator('#sidebar .mergeamendments a').click();
            await page.locator('.draftExistsAlert .btn').click();
            await page.evaluate(() => {
                const panel = document.querySelector('#draftSavingPanel') as HTMLElement | null as HTMLElement | null;
                if (panel) panel.style.display = 'none';
            });
            await expect(page.locator('#paragraphWrapper_2_4 .collidingParagraph3')).toHaveCount(0);
            await page.locator('#paragraphWrapper_2_4 .amendmentStatus3 .toggleAmendment').click();
            await page.waitForTimeout(300);
            await page.locator('#paragraphWrapper_2_4 .amendmentStatus3 .toggleAmendment').click();
            await page.waitForTimeout(300);
            await expect(page.locator('#paragraphWrapper_2_4 .collidingParagraph3').first()).toBeVisible();
            await page.evaluate(() => {
                const panel = document.querySelector('#draftSavingPanel') as HTMLElement | null as HTMLElement | null;
                if (panel) panel.style.display = '';
            });

            await page.locator('#draftSavingPanel .saveDraft').click();
            await page.evaluate(() => {
                window.removeEventListener('beforeunload', () => undefined);
            });

            await page.goto('/stdparteitag/std-parteitag/motion/321-o-zapft-is');
            await page.locator('#sidebar .mergeamendments a').click();
            await page.locator('.draftExistsAlert .btn').click();
            await expect(page.locator('#paragraphWrapper_2_4 .collidingParagraph3').first()).toBeVisible();
        });

    });
});
