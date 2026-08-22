import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';

test.describe('Supporting: MotionNonPublicSupport', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('enable non-public supports', async ({ page }) => {
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        const motionTypePage = new AdminMotionTypePage(page);
        await motionTypePage.open({ motionTypeId: 1 });
        await expect(page.locator('#typeOfferNonPublicSupports')).toHaveCount(0);
        await page.locator('#typeSupportType').selectOption('2');
        await expect(page.locator('#typeOfferNonPublicSupports')).toBeVisible();
        await page.locator('#typeOfferNonPublicSupports').check();
        await page.locator('#typePolicySupportMotions').selectOption('2');
        await page.locator('#typeMinSupporters').fill('3');
        await page.locator('.motionSupport').check();

        await motionTypePage.saveForm();

        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoMotionCreatePage();
        await page.locator("input[name='tags[]'][value='1']").check();
        await page.locator("[name='sections[1]']").fill('Testantrag 1');
        await page.locator('#motionEditForm [name="save"]').click();
        await page.locator('#motionConfirmForm [name="confirm"]').click();
        const url = await page.locator('#urlSharing').inputValue();

        await new ConsultationHomePage(page).open();
        await logout(page);
        await loginAsStdUser(page);
        await page.goto(url);

        await expect(page.locator('.supportBlock')).toBeVisible();
        await expect(page.locator('.nonPublicBlock')).toBeVisible();
        await expect(page.locator('.nonPublicBlock input')).toBeChecked();
        await page.locator('.nonPublicBlock input').uncheck();
        await page.locator('.supportBlock .colOrga input').fill('Testorga');
        await page.locator('.motionSupportForm [name="motionSupport"]').click();

        await expect(page.locator('#supporters')).toContainText('Testuser (Testorga)');
        await expect(page.locator('#supporters')).toContainText('(Nur für eingeloggte sichtbar)');

        await logout(page);
        await expect(page.locator('#supporters')).not.toContainText('Testuser (Testorga)');
        await expect(page.locator('#supporters')).toContainText('1 Unterstützer*in');
    });
});