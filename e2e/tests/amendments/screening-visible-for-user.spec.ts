import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { FIRST_FREE_AMENDMENT_ID } from '../../utils/constants';

test.describe('Amendments: ScreeningVisibleForUser', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('activate screening', async ({ page }) => {
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('.motionType1').click();
        await page.locator('#screeningAmendments').check();
        await page.locator('.adminTypeForm [name="save"]').click();
        await logout(page);
        await loginAsStdUser(page);
    });

    test('create an unscreened amendment and only see it in my list', async ({ page }) => {
        await new ConsultationHomePage(page).gotoAmendmentCreatePage('321-o-zapft-is');
        await page.locator("input[name='tags[]'][value='1']").check();
        await page.locator("[name='sections[1]']").fill('Unscreened amendment');
        await page.locator('#initiatorPrimaryName').fill('Testuser');
        await page.locator('#initiatorEmail').fill('testuser@example.org');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();

        await new ConsultationHomePage(page).open();
        await expect(page.locator('.motionListStd')).toBeVisible();
        await expect(
            page.locator(`.motionListStd .amendment${FIRST_FREE_AMENDMENT_ID}`),
        ).not.toBeVisible();
        await expect(
            page.locator(`.myAmendmentList .amendment${FIRST_FREE_AMENDMENT_ID}`),
        ).toBeVisible();
    });

    test('other users do not see it', async ({ page }) => {
        await logout(page);
        await new ConsultationHomePage(page).open();
        await expect(
            page.locator(`.myAmendmentList .amendment${FIRST_FREE_AMENDMENT_ID}`),
        ).not.toBeVisible();

        await loginAsStdAdmin(page);
        await new ConsultationHomePage(page).open();
        await expect(
            page.locator(`.myAmendmentList .amendment${FIRST_FREE_AMENDMENT_ID}`),
        ).not.toBeVisible();
    });
});