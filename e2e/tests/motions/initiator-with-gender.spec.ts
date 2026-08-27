import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { expectBootboxDialog, acceptBootbox } from '../../utils/dom';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';

const GENDER_NONE = '0';
const GENDER_OPTIONAL = '1';
const GENDER_REQUIRED = '2';

test.describe('Initiator gender field', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('no gender selection is shown by default', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);
        await home.open();
        await home.gotoMotionCreatePage();

        await test.step('see no gender selection at first', async () => {
            await expect(page.locator('#personTypeNatural')).toBeChecked();
        });

        await test.step('set the gender as required', async () => {
            await expect(page.locator('.genderRow').filter({ visible: true })).toHaveCount(0);
        });
    });

    test('a required gender field blocks submitting until it is filled', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);

        const motionType = new AdminMotionTypePage(page);
        await motionType.open({ motionTypeId: 1 });
        await expect(
            page.locator(
                `input[name="motionInitiatorSettings[contactGender]"][value="${GENDER_NONE}"]`,
            ),
        ).toBeChecked();
        await page
            .locator(
                `input[name="motionInitiatorSettings[contactGender]"][value="${GENDER_REQUIRED}"]`,
            )
            .check();
        await page.locator('.adminTypeForm [name="save"]').first().click();

        await home.open();
        const createPage = await home.gotoMotionCreatePage();
        await test.step('see the field being required', async () => {
            await expect(page.locator('.genderRow').first()).toBeVisible();
        });

        await test.step('save the form', async () => {
            await expect(page.locator('#initiatorGender')).toHaveValue('');

            await createPage.fillInValidSampleData();
            await page.locator('#motionEditForm button[name=save]').click();

            await expectBootboxDialog(page, /Bitte gib etwas im Gender-Feld an/);
            await acceptBootbox(page);

            await page.locator('#initiatorGender').first().selectOption('diverse');
            await expect(page.locator('#initiatorGender')).toHaveValue('diverse');

            await page.locator('#motionEditForm button[name=save]').click();
            await expect(page.locator('#motionConfirmForm').first()).toBeVisible();
        });

        await test.step('make a change', async () => {
            await page.locator('#motionConfirmForm button[name=modify]').click();
            await expect(page.locator('#initiatorGender')).toHaveValue('diverse');

            await page.locator('#initiatorGender').first().selectOption('female');
            await page.locator('#motionEditForm button[name=save]').click();
            await expect(page.locator('#motionConfirmForm').first()).toBeVisible();
        });
    });

    test('an optional gender field allows submitting without a value', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);

        const motionType = new AdminMotionTypePage(page);
        await motionType.open({ motionTypeId: 1 });
        await page
            .locator(
                `input[name="motionInitiatorSettings[contactGender]"][value="${GENDER_OPTIONAL}"]`,
            )
            .check();
        await page.locator('.adminTypeForm [name="save"]').first().click();

        await home.open();
        const createPage = await home.gotoMotionCreatePage();
        await test.step('make the selection optional', async () => {
            await expect(page.locator('.genderRow').first()).toBeVisible();
            await expect(page.locator('#initiatorGender')).toHaveValue('');

            await createPage.fillInValidSampleData();
        });

        await test.step('see the field being optional', async () => {
            await page.locator('#motionEditForm button[name=save]').click();
            await expect(page.locator('#motionConfirmForm').first()).toBeVisible();
        });
    });
});
