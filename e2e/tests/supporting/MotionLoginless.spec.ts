import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';

test.describe('Supporting: MotionLoginless', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('enable supporting motions without login', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoMotionView(2);
        await expect(page.locator('body')).not.toContainText('Unterstützer*innen', { useInnerText: true });

        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        const motionTypePage = new AdminMotionTypePage(page);
        await motionTypePage.open({ motionTypeId: 1 });
        await test.step('enably supporting without login', async () => {
            await page.locator('#typePolicySupportMotions').first().selectOption('1');
            await page.locator('.motionSupportPolicy .motionSupport').first().check();
            await page.locator('.adminTypeForm [name="save"]').first().click();

            await new ConsultationHomePage(page).open();
            await home.gotoMotionView(2);
            await logout(page);
            await expect(page.locator('body')).toContainText('Unterstützer*innen');
            await expect(page.locator('.supporters').getByText('Du!').filter({ visible: true })).toHaveCount(0);

            await page.locator('input[name=motionSupportName]').first().fill('My name');
            await page.locator('input[name=motionSupportOrga]').first().fill('Orga');
            await page.locator('.motionSupportForm [name="motionSupport"]').click();

            await expect(page.locator('.supporters')).toContainText('Du!');

            await page.locator('.motionSupportForm [name="motionSupportRevoke"]').click();
            await expect(page.locator('body')).toContainText('Du stehst diesem Antrag wieder neutral gegenüber.');
            await expect(page.locator('.supporters').getByText('Du!').filter({ visible: true })).toHaveCount(0);
        });
    });
});