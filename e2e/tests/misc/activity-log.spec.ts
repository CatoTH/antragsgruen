import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { gotoConsultationHome, gotoMotionList } from '../../utils/navigation';

test.describe('Misc: activity log', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('consultation-wide and per-motion activity log entries', async ({ page }) => {
        await test.step('go to the regular activity log', async () => {
            await gotoConsultationHome(page);
            await loginAsStdAdmin(page);

            await page.locator('#sidebar .activitylog a').click();
            await expect(page.locator('body')).toContainText(
                'Änderungsantrag Ä2 veröffentlicht',
            );
            await expect(page.locator('body')).not.toContainText(
                'Testadmin hat den Verfahrensvorschlag (Version -) bearbeitet',
                { useInnerText: true },
            );
            await expect(page.locator('body')).toContainText(
                'Testuser hat den Änderungsantrag Ä3',
            );

            const motionList = await gotoMotionList(page);
            await motionList.gotoMotionEdit(118);
            await page.locator('.sidebarActions .activity').click();
            await expect(page.locator('body')).toContainText(
                'Testuser hat den Antrag veröffentlicht',
            );
            await expect(page.locator('body')).toContainText(
                'Testadmin hat den Verfahrensvorschlag (Version -) bearbeitet',
            );

            const motionList2 = await gotoMotionList(page);
            await motionList2.gotoAmendmentEdit(281);
            await page.locator('.sidebarActions .activity').click();
            await expect(page.locator('body')).toContainText(
                'Testuser hat den Änderungsantrag Ä3',
            );
            await expect(page.locator('body')).toContainText(
                'Testadmin hat den Verfahrensvorschlag (Version -) bearbeitet',
            );
        });
    });
});
