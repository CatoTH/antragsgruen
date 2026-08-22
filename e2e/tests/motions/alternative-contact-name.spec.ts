import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';
import { MotionCreatePage } from '../../pages/MotionCreatePage';

const CONTACT_NONE = '0';
const CONTACT_REQUIRED = '2';

test.describe('Alternative contact name', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('contact name field can be made required and is preserved', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoMotionCreatePage();
        await expect(page.locator('#initiatorContactName')).toHaveCount(0);

        await home.open();
        await loginAsStdAdmin(page);

        const motionType = new AdminMotionTypePage(page);
        await motionType.open({ motionTypeId: 1 });
        await expect(
            page.locator(`input[name="motionInitiatorSettings[contactName]"][value="${CONTACT_NONE}"]`),
        ).toBeChecked();
        await page
            .locator(`input[name="motionInitiatorSettings[contactName]"][value="${CONTACT_REQUIRED}"]`)
            .check();
        await motionType.saveForm();
        await expect(
            page.locator(`input[name="motionInitiatorSettings[contactName]"][value="${CONTACT_REQUIRED}"]`),
        ).toBeChecked();

        await home.open();
        const createPage = await home.gotoMotionCreatePage();
        await expect(page.locator('#initiatorContactName')).toBeVisible();
        await expect(page.locator('#initiatorContactName')).toHaveAttribute('required', '');

        await createPage.fillInValidSampleData();
        await page.locator('#initiatorContactName').fill('Alternative contact person');
        await createPage.saveForm();

        await page.locator('#motionConfirmForm [name="modify"]').click();
        await expect(page.locator('#initiatorContactName')).toHaveValue('Alternative contact person');
    });
});
