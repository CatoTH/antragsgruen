import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { FIRST_FREE_AMENDMENT_ID } from '../../utils/constants';

test.describe('Amendments: ScreeningVisibleForUser', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('activate screening', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('.motionType1').click();
        await page.locator('#screeningAmendments').first().check();
        await page.locator('.adminTypeForm [name="save"]').first().click();
        await logout(page);
        await loginAsStdUser(page);
    });

    test('create an unscreened amendment and only see it in my list', async ({ page }) => {
        await new ConsultationHomePage(page).gotoAmendmentCreatePage('321-o-zapft-is');
        await page.locator("input[name='tags[]'][value='1']").first().check();
        await page.locator("[name='sections[1]']").first().fill('Unscreened amendment');
        await page.locator('#initiatorPrimaryName').first().fill('Testuser');
        await page.locator('#initiatorEmail').first().fill('testuser@example.org');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();

        await new ConsultationHomePage(page).open();
        await test.step('create an amendment', async () => {
            await expect(page.locator('.motionListStd').first()).toBeVisible();
            await expect(
                page.locator(`.motionListStd .amendment${FIRST_FREE_AMENDMENT_ID}`),
            ).not.toBeVisible();
        });

        await test.step('check that other users don\\\'t see it', async () => {
            await expect(
                page.locator(`.myAmendmentList .amendment${FIRST_FREE_AMENDMENT_ID}`),
            ).toBeVisible();
        });
    });

    test('other users do not see it', async ({ page }) => {
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