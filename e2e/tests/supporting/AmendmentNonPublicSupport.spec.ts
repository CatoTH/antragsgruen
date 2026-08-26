import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';

test.describe('Supporting: AmendmentNonPublicSupport', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('enable non-public supports', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        const motionTypePage = new AdminMotionTypePage(page);
        await motionTypePage.open({ motionTypeId: 1 });
        await expect(page.locator('#typeOfferNonPublicSupports').filter({ visible: true })).toHaveCount(0);
        await page.locator('#typeSupportType').first().selectOption('2');
        await expect(page.locator('#typeOfferNonPublicSupports').first()).toBeVisible();
        await page.locator('#typeOfferNonPublicSupports').first().check();
        await page.locator('#typePolicySupportAmendments').first().selectOption('2');
        await page.locator('#typeMinSupporters').first().fill('3');
        await page.locator('.amendmentSupport').first().check();

        await page.locator('.adminTypeForm [name="save"]').first().click();

        await home.gotoAmendmentCreatePage();
        await page.locator('#sections_30').first().fill('New amendment');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();
        const url = await page.locator('#urlSharing').inputValue();

        await new ConsultationHomePage(page).open();
        await logout(page);
        await loginAsStdUser(page);
        await page.goto(url);

        await test.step('support this amendment non-publically', async () => {
            await expect(page.locator('.supportBlock').first()).toBeVisible();
            await expect(page.locator('.nonPublicBlock').first()).toBeVisible();
            await expect(page.locator('.nonPublicBlock input')).toBeChecked();
            await page.locator('.nonPublicBlock input').first().uncheck();
            await page.locator('.supportBlock .colOrga input').first().fill('Testorga');
            await page.locator('.motionSupportForm [name="motionSupport"]').click();

            await expect(page.locator('#supporters')).toContainText('Testuser (Testorga)');
            await expect(page.locator('#supporters')).toContainText('(Nur für eingeloggte sichtbar)');

            await logout(page);
            await expect(page.locator('#supporters').getByText('Testuser (Testorga)').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#supporters')).toContainText('1 Unterstützer*in');
        });
    });
});