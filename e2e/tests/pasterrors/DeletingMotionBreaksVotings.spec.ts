import { test, expect } from '../../fixtures';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionListPage } from '../../pages/AdminMotionListPage';
import { loginAsStdAdmin } from '../../utils/auth';
import { acceptBootbox, expectBootboxDialog } from '../../utils/dom';

test.describe('DeletingMotionBreaksVotings', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('delete a motion does not break the votings page', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);

        const motionList = new AdminMotionListPage(page);
        await new AdminIndexPage(page).open();
        await motionList.open();
        await page.locator('.motion2 .edit, .motion2 [href*="edit"]').first().click();

        await page.locator('.motionDeleteForm button').click();
        await page.waitForTimeout(1000);
        await expectBootboxDialog(page, /Antrag wurde gelöscht|gelöscht/i);
        await acceptBootbox(page);
        await expect(page.locator('body')).toContainText('Der Antrag wurde gelöscht.');

        await new AdminIndexPage(page).open();
        await page.locator('#votingsLink').click();
        await expect(page.locator('body')).toContainText('eine Einführung und Anleitung');
    });
});