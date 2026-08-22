import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';
import { AdminMotionListPage } from '../../pages/AdminMotionListPage';
import { setUserFixedData } from '../../utils/test-api';
import { FIRST_FREE_MOTION_ID } from '../../utils/constants';

test.describe('Supporting: MotionSupportAsOrganization', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('support as organization', async ({ page, request }) => {
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        const motionTypePage = new AdminMotionTypePage(page);
        await motionTypePage.open({ motionTypeId: 1 });
        await expect(page.locator('#typeSupporterCanBeOrga')).toHaveCount(0);
        await page.locator('#typeSupportType').selectOption('2');
        await expect(page.locator('#typeSupporterCanBeOrga')).toBeVisible();
        await expect(page.locator('#typeSupporterCanBePerson')).toBeChecked();
        await page.locator('#typeSupporterCanBeOrga').check();
        await page.locator('#typeHasOrga').uncheck();
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
        await expect(page.locator('.supportPersonTypeSelection')).toBeVisible();
        await page.locator('.supportPersonTypeSelection .supportAsOrga input').check();
        await page.locator('.supportBlock .colOrga input').fill('Testorga');
        await page.locator('.motionSupportForm [name="motionSupport"]').click();

        await expect(page.locator('#supporters')).toContainText('Testorga');
        await expect(page.locator('#supporters')).not.toContainText('Testuser (Testorga)');

        await logout(page);
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        const motionList = new AdminMotionListPage(page);
        await motionList.open();
        await expect(page.locator('.adminMotionTable')).toContainText('Unterstützer*innen sammeln (1 Organisation)');

        await page.goto(url);
        await page.locator('#sidebar .adminEdit a').click();
        await expect(page.locator('#motionSupporterHolder .supporterRow .supporterOrga')).toHaveValue('Testorga');
        await expect(
            page.locator('#motionSupporterHolder .supporterRow input[type=radio][value="1"]'),
        ).toBeChecked();
        await expect(
            page.locator('#motionSupporterHolder .supporterRow input[type=radio][value="0"]'),
        ).not.toBeChecked();
        await page.locator('#motionUpdateForm [name="save"]').click();

        await page.goto(url);
        await expect(page.locator('#supporters')).toContainText('Testorga');
        await expect(page.locator('#supporters')).not.toContainText('Testuser (Testorga)');

        await page.locator('#sidebar .adminEdit a').click();
        await page.locator('#motionSupporterHolder .supporterRow input[type=radio][value="0"]').check();
        await page.locator('#motionSupporterHolder .supporterRowAdder').click();
        await page
            .locator('#motionSupporterHolder .supporterList > li:last-child .supporterName')
            .fill('Second Supporter');
        await page
            .locator('#motionSupporterHolder .supporterList > li:last-child .supporterOrga')
            .fill('Second Orga');
        await page
            .locator('#motionSupporterHolder .supporterList > li:last-child input[type=radio][value="1"]')
            .check();
        await page.locator('#motionUpdateForm [name="save"]').click();

        await page.goto(url);
        await expect(page.locator('#supporters')).toContainText('Testuser (Testorga)');
        await expect(page.locator('#supporters')).toContainText('Second Orga');
        await expect(page.locator('#supporters')).not.toContainText('Second Supporter');

        await new AdminIndexPage(page).open();
        await motionList.open();
        await expect(page.locator('.adminMotionTable')).toContainText(
            'Unterstützer*innen sammeln (1 + 1 Organisation)',
        );

        await page.goto(url);
        await page.locator('#sidebar .adminEdit a').click();
        await page.locator('#motionSupporterHolder .supporterRowAdder').click();
        await page
            .locator('#motionSupporterHolder .supporterList > li:last-child input[type=radio][value="1"]')
            .check();
        await page
            .locator('#motionSupporterHolder .supporterList > li:last-child .supporterOrga')
            .fill('Third Orga');
        await page.locator('#motionUpdateForm [name="save"]').click();

        await expect(
            page.locator('#motionSupporterHolder .supporterList > li:last-child .supporterOrga'),
        ).toHaveValue('Third Orga');
        await expect(
            page.locator('#motionSupporterHolder .supporterList > li:last-child input[type=radio][value="1"]'),
        ).toBeChecked();

        await page.goto(url);
        await expect(page.locator('#supporters')).toContainText('Third Orga');
    });
});