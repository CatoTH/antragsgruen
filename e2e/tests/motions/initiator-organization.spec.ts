import { test, expect } from '../../fixtures';
import { FIRST_FREE_MOTION_ID } from '../../utils/constants';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';

const PERSON_ORGANIZATION = '1';

test.describe('Motion from an organization', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('organization data including the resolution date is stored and shown', async ({
        page,
    }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        const createPage = await home.gotoMotionCreatePage();

        await createPage.fillInValidSampleData('Sample motion from an organization');
        await page.locator(`#personTypeOrga[value="${PERSON_ORGANIZATION}"]`).first().check();
        await expect(page.locator('.supporterDataHead').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.supporterData').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#initiatorOrga').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#resolutionDate').first()).toBeVisible();

        await expect(page.locator('.bootstrap-datetimepicker-widget').filter({ visible: true })).toHaveCount(0);
        await page.locator('#resolutionDateHolder .input-group-addon').click();
        await expect(page.locator('.bootstrap-datetimepicker-widget').first()).toBeVisible();
        await page.locator('#resolutionDateHolder .input-group-addon').click();
        await expect(page.locator('.bootstrap-datetimepicker-widget').filter({ visible: true })).toHaveCount(0);

        await page.locator('#initiatorPrimaryName').first().fill('My party');
        await page.locator('#initiatorContactName').first().fill('Myself');
        await page.locator('#resolutionDate').first().fill('09.09.1999');

        await createPage.saveForm();

        await expect(page.locator('body')).toContainText('My party');
        await expect(page.locator('body')).toContainText('Beschlossen am: 09.09.1999');

        await page.locator('#motionConfirmForm [name="confirm"]').click();
        await page.locator('#motionConfirmedForm [type="submit"]').click();

        await expect(page.locator('body')).toContainText('My party');
        await expect(page.locator('body')).toContainText('Beschlossen am: 09.09.1999');

        await page.locator(`.motionLink${FIRST_FREE_MOTION_ID}`).click();

        await expect(page.locator('body')).toContainText('My party');
        await expect(page.locator('body')).toContainText('Beschlossen am: 09.09.1999');
    });
});
