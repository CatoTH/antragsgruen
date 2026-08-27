import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin } from '../../utils/auth';
import { AdminMotionListPage } from '../../pages/AdminMotionListPage';
import { acceptBootbox } from '../../utils/dom';

test.describe('Admin: DeleteMotionAmendment', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('delete a motion and an amendment', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new ConsultationHomePage(page).open();
        const motionList = new AdminMotionListPage(page);
        await page.locator('#motionListLink').click();

        await expect(page.locator('body')).toContainText('A2');
        await expect(page.locator('body')).toContainText('A3');
        await expect(page.locator('.amendment3').first()).toBeVisible();

        await motionList.gotoMotionEdit(3);
        await page.locator('.motionDeleteForm button').click();
        await acceptBootbox(page);
        await expect(page.locator('body')).toContainText('Der Antrag wurde gelöscht.');
        await expect(page.locator('body')).toContainText('A2');
        await expect(page.locator('body')).not.toContainText('A3', { useInnerText: true });
        await expect(page.locator('.amendment3').first()).toBeVisible();

        await page
            .locator('.adminMotionTable .amendment3 .titleCol a')
            .first()
            .click();
        await test.step('delete an amendment', async () => {
            await page.locator('.amendmentDeleteForm button').click();
            await acceptBootbox(page);
            await expect(page.locator('body')).toContainText('Der Änderungsantrag wurde gelöscht.');
            await expect(page.locator('body')).toContainText('A2');
            await expect(page.locator('body')).not.toContainText('A3', { useInnerText: true });
            await expect(page.locator('.amendment3').first()).not.toBeVisible();
        });
    });
});