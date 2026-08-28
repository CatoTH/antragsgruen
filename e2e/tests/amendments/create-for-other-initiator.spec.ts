import { test, expect } from '../../fixtures';
import { AmendmentPage } from '../../pages/AmendmentPage';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin } from '../../utils/auth';
import { FIRST_FREE_AMENDMENT_ID } from '../../utils/constants';

test.describe('Amendments: CreateForOtherInitiator', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('check the option for normal users vs admins', async ({ page }) => {
        await new ConsultationHomePage(page).gotoAmendmentCreatePage('321-o-zapft-is');
        await expect(page.locator('input[name=otherInitiator]')).not.toBeVisible();

        await loginAsStdAdmin(page);
        await expect(page.locator('input[name=otherInitiator]')).toBeVisible();
        await expect(page.locator('input[name=otherInitiator]')).toBeChecked();
        await expect(page.locator('#initiatorPrimaryName')).toHaveValue('');
        await expect(page.locator('#initiatorEmail')).toHaveValue('');
    });

    test('create an amendment as another user', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new ConsultationHomePage(page).gotoAmendmentCreatePage('321-o-zapft-is');

        await page.locator("input[name='tags[]'][value='1']").check();
        await page.locator("[name='sections[1]']").fill('Neuer Testantrag 1');
        await page.locator('#initiatorPrimaryName').fill('Mein Name');
        await page.locator('#initiatorEmail').fill('test@example.org');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();
        await expect(page.locator('h1')).toContainText('ÄNDERUNGSANTRAG VERÖFFENTLICHT');
        await page.locator('#motionConfirmedForm [type="submit"]').click();

        await new AmendmentPage(page).open({
            motionSlug: '321-o-zapft-is',
            amendmentId: FIRST_FREE_AMENDMENT_ID,
        });
        await expect(page.locator('.sidebarActions .withdraw')).not.toBeVisible();
        await expect(page.locator('.sidebarActions .edit')).not.toBeVisible();
    });

    test('create an amendment as myself', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await new ConsultationHomePage(page).gotoAmendmentCreatePage('321-o-zapft-is');
        await page.locator('input[name=otherInitiator]').uncheck();
        await page.locator("input[name='tags[]'][value='1']").check();
        await page.locator("[name='sections[1]']").fill('Neuer Testantrag 2');
        await page.locator('#initiatorPrimaryName').fill('My Name');
        await page.locator('#initiatorEmail').fill('test@example.org');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();
        await expect(page.locator('h1')).toContainText('ÄNDERUNGSANTRAG VERÖFFENTLICHT');
        await page.locator('#motionConfirmedForm [type="submit"]').click();

        await new AmendmentPage(page).open({
            motionSlug: '321-o-zapft-is',
            amendmentId: FIRST_FREE_AMENDMENT_ID + 1,
        });
        await expect(page.locator('.sidebarActions .withdraw')).toBeVisible();
        await expect(page.locator('.sidebarActions .edit')).not.toBeVisible();
    });

    test('enable amendment editing for initiators', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await loginAsStdAdmin(page);
        await page.locator('#adminLink').click();
        await page.locator('#consultationLink').click();
        await page.locator('#iniatorsMayEdit').check();
        await page.locator('#consultationSettingsForm [name="save"]').click();

        await new AmendmentPage(page).open({
            motionSlug: '321-o-zapft-is',
            amendmentId: FIRST_FREE_AMENDMENT_ID,
        });
        await expect(page.locator('.sidebarActions .withdraw')).not.toBeVisible();
        await expect(page.locator('.sidebarActions .edit')).not.toBeVisible();

        await new AmendmentPage(page).open({
            motionSlug: '321-o-zapft-is',
            amendmentId: FIRST_FREE_AMENDMENT_ID + 1,
        });
        await expect(page.locator('.sidebarActions .withdraw')).toBeVisible();
        await expect(page.locator('.sidebarActions .edit')).toBeVisible();

        await page.locator('.sidebarActions .edit a').click();
        await expect(page.locator('input[name=otherInitiator]')).not.toBeChecked();
    });
});