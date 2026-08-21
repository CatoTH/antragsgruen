import { test, expect } from '../../fixtures';
import { MotionPage } from '../../pages/MotionPage';
import {ConsultationHomePage} from '../../pages/BasePage';
import { AmendmentPage } from '../../pages/AmendmentPage';
import { loginAsStdUser } from '../../utils/auth';

test.describe('Amendments: Withdraw', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('withdraw the motion I created before', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdUser(page);

        await new MotionPage(page).open({ motionSlug: 3 });
        await expect(page.locator('section.amendments .amendment2')).toBeVisible();
        await expect(page.locator('.bookmarks .amendment2')).toBeVisible();

        await new AmendmentPage(page).open({
            motionSlug: 3,
            amendmentId: 2,
        });

        await page.locator('.sidebarActions .withdraw a').click();
        await expect(page.locator('body')).toContainText(
            'Willst du diesen Änderungsantrag wirklich zurückziehen?',
        );
        await page.locator('.withdrawForm [name="withdraw"]').click();
        await expect(page.locator('body')).toContainText(
            'Der Änderungsantrag wurde zurückgezogen.',
        );
        await expect(page.locator('.motionDataTable .statusRow')).toContainText('Zurückgezogen');
        await expect(page.locator('.sidebarActions .withdraw a')).not.toBeVisible();

        await new ConsultationHomePage(page).open();
        await expect(page.locator('.amendmentRow2.withdrawn')).toBeVisible();

        await new MotionPage(page).open({ motionSlug: 3 });
        await expect(page.locator('section.amendments .amendment2')).toBeVisible();
        await expect(page.locator('.bookmarks .amendment2')).not.toBeVisible();
    });
});