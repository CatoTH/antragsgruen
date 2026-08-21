import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/BasePage';
import { loginAsFixedDataUser } from '../../utils/auth';

test.describe('Amendments: FixedUserData', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('check that the basic functionality works', async ({ page }) => {
        await new ConsultationHomePage(page).gotoAmendmentCreatePage('321-o-zapft-is');
        await loginAsFixedDataUser(page);

        await expect(page.locator('#initiatorPrimaryName')).toHaveValue('Fixed Data');
        await expect(page.locator('#initiatorOrga')).toHaveValue('MotionTools');
        let readonly = await page.evaluate(
            () => (document.getElementById('initiatorPrimaryName') as HTMLInputElement).readOnly,
        );
        expect(readonly).toEqual(true);

        await page.locator('#personTypeOrga').check();
        readonly = await page.evaluate(
            () => (document.getElementById('initiatorPrimaryName') as HTMLInputElement).readOnly,
        );
        expect(readonly).toEqual(false);

        await page.locator('#personTypeNatural').check();
        readonly = await page.evaluate(
            () => (document.getElementById('initiatorPrimaryName') as HTMLInputElement).readOnly,
        );
        expect(readonly).toEqual(true);
    });

    test('submit an amendment with a fake name (server should reject)', async ({ page }) => {
        await new ConsultationHomePage(page).gotoAmendmentCreatePage('321-o-zapft-is');
        await loginAsFixedDataUser(page);

        await page.evaluate(() => {
            (document.getElementById('initiatorPrimaryName') as HTMLInputElement).value = 'Some fake name';
        });
        await expect(page.locator('#initiatorPrimaryName')).toHaveValue('Some fake name');

        await page.locator('#amendmentEditForm [name="save"]').click();

        await expect(page.locator('body')).toContainText('Änderungsantrag bestätigen');
        await expect(page.locator('body')).not.toContainText('Some fake name');
        await expect(page.locator('body')).toContainText('Fixed Data (MotionTools)');
    });
});