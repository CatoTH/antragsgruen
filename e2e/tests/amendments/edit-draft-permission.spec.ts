import { test, expect } from '../../fixtures';
import { AmendmentPage } from '../../pages/AmendmentPage';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdUser } from '../../utils/auth';
import { FIRST_FREE_AMENDMENT_ID } from '../../utils/constants';

test.describe('Amendments: EditDraftPermission', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create and edit a draft logged out', async ({ page }) => {
        await new ConsultationHomePage(page).gotoAmendmentCreatePage('3');
        await page.locator("input[name='tags[]'][value='1']").check();
        await page.locator("[name='sections[1]']").fill('Neuer Testantrag 1');
        await page.locator('#initiatorPrimaryName').fill('Mein Name');
        await page.locator('#initiatorEmail').fill('test@example.org');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await expect(page.locator('h1')).toContainText('ÄNDERUNGSANTRAG BESTÄTIGEN');

        await new ConsultationHomePage(page).open();
        await new AmendmentPage(page).open({
            motionSlug: '3',
            amendmentId: FIRST_FREE_AMENDMENT_ID,
        });
        await expect(page.locator('h1')).toContainText(
            'ÄNDERUNGSANTRAG ZU A3: TEXTFORMATIERUNGEN STELLEN',
        );
        await expect(page.locator("input[name='sections[1]']")).toHaveValue('Neuer Testantrag 1');
    });

    test('create and edit a draft logged in', async ({ page }) => {
        await new ConsultationHomePage(page).gotoAmendmentCreatePage('3');
        await loginAsStdUser(page);
        await page.locator("input[name='tags[]'][value='1']").check();
        await page.locator("[name='sections[1]']").fill('Neuer Testantrag 2');
        await page.locator('#initiatorPrimaryName').fill('My Name');
        await page.locator('#initiatorEmail').fill('test@example.org');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await expect(page.locator('h1')).toContainText('ÄNDERUNGSANTRAG BESTÄTIGEN');

        await new ConsultationHomePage(page).open();
        await new AmendmentPage(page).open({
            motionSlug: '3',
            amendmentId: FIRST_FREE_AMENDMENT_ID + 1,
        });
        await expect(page.locator('h1')).toContainText(
            'ÄNDERUNGSANTRAG ZU A3: TEXTFORMATIERUNGEN STELLEN',
        );
        await expect(page.locator("input[name='sections[1]']")).toHaveValue('Neuer Testantrag 2');
    });

    test('edit the draft logged out (should not work)', async ({ page }) => {
        await new AmendmentPage(page).open({
            motionSlug: '3',
            amendmentId: FIRST_FREE_AMENDMENT_ID + 1,
        });
        await expect(page.locator('h1')).not.toContainText(
            'ÄNDERUNGSANTRAG ZU A3: TEXTFORMATIERUNGEN STELLEN',
        );
        await expect(page.locator("input[name='sections[1]']")).not.toBeVisible();
    });
});