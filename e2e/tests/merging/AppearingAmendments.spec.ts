import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { setAmendmentStatus } from '../../utils/test-api';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';

const SUBDOMAIN = 'stdparteitag';
const CONSULTATION = 'std-parteitag';

test.describe('Merging: appearing amendments after status change', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('newly appearing amendments show up in the merge UI', async ({ page, request }) => {
        await setAmendmentStatus(request, 274, 2, SUBDOMAIN, CONSULTATION);
        await setAmendmentStatus(request, 276, 2, SUBDOMAIN, CONSULTATION);

        const home = new ConsultationHomePage(page);
        await home.gotoMotionView(2);
        await loginAsStdAdmin(page);
        await page.locator('.sidebarActions .mergeamendments a').click();
        await page.waitForTimeout(500);

        await expect(page.locator('.amendment272'.first())).toBeVisible();
        await expect(page.locator('.amendment274'.first())).toHaveCount(0);
        await expect(page.locator('.amendment276'.first())).toHaveCount(0);
        await page.locator('.selectAll').first().click();
        await page.waitForTimeout(300);
        await page.locator('.mergeAllRow .btn-primary').click();

        await page.waitForTimeout(500);
        await expect(page.locator('.toggleAmendment3')).toBeVisible();
        await expect(page.locator('.toggleAmendment272')).toBeVisible();
        await expect(page.locator('.toggleAmendment274')).toHaveCount(0);
        await expect(page.locator('.toggleAmendment276')).toHaveCount(0);
        await expect(page.locator('#newAmendmentAlert')).toHaveCount(0);

        await setAmendmentStatus(request, 274, 3, SUBDOMAIN, CONSULTATION);
        await setAmendmentStatus(request, 276, 3, SUBDOMAIN, CONSULTATION);

        await page.waitForTimeout(4000);

        await expect(page.locator('.toggleAmendment274.btn-default')).toBeVisible();
        await expect(page.locator('.toggleAmendment276.btn-default')).toBeVisible();
        await expect(page.locator('.toggleAmendment274.toggleActive')).toHaveCount(0);
        await expect(page.locator('#newAmendmentAlert')).toBeVisible();

        await page.evaluate(() => {
            const link = document.querySelector('#newAmendmentAlert .closeLink') as HTMLElement | null;
            if (link) link.click();
        });
        await page.waitForTimeout(1000);
        await expect(page.locator('#newAmendmentAlert')).toHaveCount(0);

        await expect(page.locator('#paragraphWrapper_4_0')).not.toContainText('Schooe');
        await page.evaluate(() => {
            const btn = document.querySelector(
                '#paragraphWrapper_4_0 .amendmentStatus274 .toggleAmendment',
            ) as HTMLElement | null;
            if (btn) btn.click();
        });
        await page.waitForTimeout(500);
        await expect(page.locator('#paragraphWrapper_4_0 ins')).toContainText('Schooe');
        await page.evaluate(() => {
            const hint = document.querySelector('#paragraphWrapper_4_0 [data-cid="1"]') as HTMLElement | null;
            if (hint) {
                hint.dispatchEvent(new MouseEvent('mouseover', { bubbles: true }));
            }
            const btn = document.querySelector('button.accept') as HTMLElement | null;
            if (btn) btn.click();
        });
        await page.waitForTimeout(200);
        await expect(page.locator('#paragraphWrapper_4_0')).toContainText('Schooe');
        await expect(page.locator('#paragraphWrapper_4_0 ins')).not.toContainText('Schooe');

        await setAmendmentStatus(request, 274, 2, SUBDOMAIN, CONSULTATION);
        await setAmendmentStatus(request, 276, 2, SUBDOMAIN, CONSULTATION);

        await page.waitForTimeout(4000);

        await expect(page.locator('.toggleAmendment274')).toHaveCount(0);
        await expect(page.locator('.toggleAmendment276')).toHaveCount(0);

        await expect(page.locator('#paragraphWrapper_4_0')).toContainText('Schooe');

        await page.evaluate(() => {
            window.removeEventListener('beforeunload', () => undefined);
        });

        await home.gotoMotionView(2);
        await page.locator('.sidebarActions .mergeamendments a').click();
        await page.locator('.draftExistsAlert a.btn').click();

        await expect(page.locator('.toggleAmendment274')).toHaveCount(0);
        await expect(page.locator('.toggleAmendment276')).toHaveCount(0);

        await expect(page.locator('#paragraphWrapper_4_0')).toContainText('Schooe');
    });
});
