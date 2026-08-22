import { test, expect } from '../../fixtures';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionListPage } from '../../pages/AdminMotionListPage';
import { loginAsStdAdmin, logout } from '../../utils/auth';

test.describe('AmendmentEditDeleteText', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('ensure the text does not get deleted', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);

        const motionList = new AdminMotionListPage(page);
        await new AdminIndexPage(page).open();
        await motionList.open();
        await page
            .locator('.motion1 .edit, .motion1 [href*="edit"]')
            .first()
            .click();
        await page.locator('#amendmentUpdateForm [name="save"]').click();
        await page.locator('.sidebarActions .view').click();
        await expect(page.locator('del')).toHaveCount(0);
        await expect(page.locator('ul.inserted')).toBeVisible();

        await logout(page);
    });
});