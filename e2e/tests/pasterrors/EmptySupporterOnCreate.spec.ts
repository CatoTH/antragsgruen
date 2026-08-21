import { test, expect } from '../../fixtures';
import { ConsultationHomePage } from '../../pages/BasePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';
import { MotionPage } from '../../pages/MotionPage';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';

test.describe('EmptySupporterOnCreate', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('initiators do not appear as supporters in amendment/motion creation forms', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);

        const motionTypePage = new AdminMotionTypePage(page);
        await new AdminIndexPage(page).open();
        await motionTypePage.open({ motionTypeId: 1 });
        await page.locator('#typeSupportType').selectOption('1');
        await page.locator('#typeMinSupporters').fill('0');
        await motionTypePage.saveForm();

        await new ConsultationHomePage(page).open();
        await logout(page);
        await loginAsStdUser(page);

        await new MotionPage(page).open({ motionSlug: '321-o-zapft-is' });
        await page.locator('.amendmentCreate a').click();

        await expect(page.locator('#initiatorPrimaryName')).toHaveValue('Testuser');
        expect(
            await page.locator('input[name="supporters[name][]"]').count(),
        ).toBeGreaterThanOrEqual(0);
        await expect(
            page.locator('input[name="supporters[name][]"]').first(),
        ).not.toHaveValue('Testuser');

        await new ConsultationHomePage(page).open();
        await page.locator('.createMotion').click();

        await expect(page.locator('#initiatorPrimaryName')).toHaveValue('Testuser');
        await expect(
            page.locator('input[name="supporters[name][]"]').first(),
        ).not.toHaveValue('Testuser');
    });
});