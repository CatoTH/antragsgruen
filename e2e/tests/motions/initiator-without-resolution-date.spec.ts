import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';

const RESOLUTION_NONE = '0';
const RESOLUTION_OPTIONAL = '1';
const RESOLUTION_REQUIRED = '2';
const PERSON_ORGANIZATION = '1';

test.describe('Initiator without resolution date', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('an optional resolution date may be left empty', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);

        const motionType = new AdminMotionTypePage(page);
        await motionType.open({ motionTypeId: 1 });
        await expect(
            page.locator(
                `input[name="motionInitiatorSettings[hasResolutionDate]"][value="${RESOLUTION_REQUIRED}"]`,
            ),
        ).toBeChecked();
        await page
            .locator(
                `input[name="motionInitiatorSettings[hasResolutionDate]"][value="${RESOLUTION_OPTIONAL}"]`,
            )
            .check();
        await page.locator('.adminTypeForm [name="save"]').first().click();
        await expect(
            page.locator(
                `input[name="motionInitiatorSettings[hasResolutionDate]"][value="${RESOLUTION_OPTIONAL}"]`,
            ),
        ).toBeChecked();

        await home.open();
        const createPage = await home.gotoMotionCreatePage();
        await createPage.fillInValidSampleData();
        await test.step('set the resolution date as optional', async () => {
            await page.locator(`#personTypeOrga[value="${PERSON_ORGANIZATION}"]`).first().check();
            await expect(page.locator('#resolutionDate').first()).toBeVisible();
            await page.locator('#resolutionDate').first().fill('');
            await page.locator('#initiatorPrimaryName').first().fill('My party');

            await page.locator('#motionEditForm button[name=save]').click();
            await expect(page.locator('#motionConfirmForm').first()).toBeVisible();
        });
    });

    test('a deactivated resolution date hides the field entirely', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);

        const motionType = new AdminMotionTypePage(page);
        await motionType.open({ motionTypeId: 1 });
        await page
            .locator(
                `input[name="motionInitiatorSettings[hasResolutionDate]"][value="${RESOLUTION_NONE}"]`,
            )
            .check();
        await page.locator('.adminTypeForm [name="save"]').first().click();
        await expect(
            page.locator(
                `input[name="motionInitiatorSettings[hasResolutionDate]"][value="${RESOLUTION_NONE}"]`,
            ),
        ).toBeChecked();

        await home.open();
        const createPage = await home.gotoMotionCreatePage();
        await createPage.fillInValidSampleData();
        await test.step('see the field being optional', async () => {
            await page.locator(`#personTypeOrga[value="${PERSON_ORGANIZATION}"]`).first().check();
        });

        await test.step('deactivate the resolution date', async () => {
            await expect(page.locator('#resolutionDate').filter({ visible: true })).toHaveCount(0);
        });

        await test.step('see the field being optional', async () => {
            await page.locator('#initiatorPrimaryName').first().fill('My party');

            await page.locator('#motionEditForm button[name=save]').click();
            await expect(page.locator('#motionConfirmForm').first()).toBeVisible();
        });
    });
});
