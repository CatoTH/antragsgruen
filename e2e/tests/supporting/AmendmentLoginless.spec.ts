import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';

test.describe('Supporting: AmendmentLoginless', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('enable supporting amendments without login', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoAmendmentView(1);
        await expect(page.locator('body')).not.toContainText('Unterstützer*innen');

        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        const motionTypePage = new AdminMotionTypePage(page);
        await motionTypePage.open({ motionTypeId: 1 });
        await page.locator('#typePolicySupportAmendments').selectOption('1');
        await page.locator('.amendmentSupportPolicy .amendmentSupport').check();
        await motionTypePage.saveForm();

        await new ConsultationHomePage(page).open();
        await home.gotoAmendmentView(1);
        await logout(page);
        await expect(page.locator('body')).toContainText('Unterstützer*innen');
        await expect(page.locator('.supporters')).not.toContainText('Du!');

        await page.locator('input[name=motionSupportName]').fill('My name');
        await page.locator('input[name=motionSupportOrga]').fill('Orga');
        await page.locator('.motionSupportForm [name="motionSupport"]').click();

        await expect(page.locator('.supporters')).toContainText('Du!');

        await page.locator('.motionSupportForm [name="motionSupportRevoke"]').click();
        await expect(page.locator('body')).toContainText('Du stehst diesem Änderungsantrag wieder neutral gegenüber.');
        await expect(page.locator('.supporters')).not.toContainText('Du!');
    });
});