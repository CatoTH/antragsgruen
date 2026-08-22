import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminAppearancePage } from '../../pages/AdminAppearancePage';

test.describe('Speech: non-quota, loginless', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('enable non-quota speech lists and test as admin', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await expect(page.locator('.currentSpeechInline')).toHaveCount(0);
        await expect(page.locator('#speechLink')).toHaveCount(0);

        await loginAsStdAdmin(page);
        const appearancePage = new AdminAppearancePage(page);
        await new ConsultationHomePage(page).open();
        const adminIndexUrl = await new (await import('../../pages/AdminIndexPage')).AdminIndexPage(page).getUrl();
        await page.goto(adminIndexUrl);
        await appearancePage.open();

        await expect(page.locator('#hasSpeechLists')).not.toBeChecked();
        await expect(page.locator('.quotas')).toHaveCount(0);
        await page.locator('#hasSpeechLists').check();
        await expect(page.locator('.quotas')).toBeVisible();
        await page.locator('#activateFirstSpeechList').uncheck();
        await page.locator('#speechPage').check();
        await appearancePage.saveForm();

        await new ConsultationHomePage(page).open();
        await expect(page.locator('.currentSpeechInline')).toHaveCount(0);

        await page.locator(`.motionLink2`).click();
        await expect(page.locator('.currentSpeechFooter')).toHaveCount(0);

        await page.locator('#speechLink').click();
        await expect(page.locator('body')).toContainText('Die Redeliste ist nicht geöffnet');

        await page.locator('.speechAdminLink').click();

        await expect(page.locator('.slotActive.inactive .nameNobody')).toBeVisible();
        await expect(page.locator('.slotPlaceholder.inactive .nameNobody')).toBeVisible();
        await expect(page.locator('.subqueues')).toContainText('Warteliste');

        await expect(page.locator('.toolbarBelowTitle .settingsActive .inactive')).toBeVisible();

        await page.evaluate(() => {
            const btn = document.querySelector('.toolbarBelowTitle .settingsActive button') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });

        await page.evaluate(() => {
            const chkbox = document.querySelector('.toolbarBelowTitle.settings .settingsOpen input') as HTMLInputElement;
            chkbox.checked = true;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('change', false, true);
            chkbox.dispatchEvent(evt);
        });

        await new ConsultationHomePage(page).open();
        await expect(page.locator('.currentSpeechInline')).toBeVisible();
        await expect(page.locator('.currentSpeechInline .appliedMe')).toHaveCount(0);
        await expect(page.locator('.currentSpeechInline')).toContainText('Redeliste');
        await expect(page.locator('.currentSpeechInline .number')).toContainText('0');
        await expect(page.locator('.currentSpeechInline .notPossible')).toHaveCount(0);
        await expect(page.locator('.waitingSingle .apply button')).toContainText('Bewerben');

        await page.evaluate(() => {
            const btn = document.querySelector('.waitingSingle .apply button') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });

        await expect(page.locator('#speechRegisterName-1')).toHaveValue('Testadmin');

        await page.evaluate(() => {
            const form = document.querySelector('.waitingSingle form') as HTMLFormElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('submit', false, true);
            form.dispatchEvent(evt);
        });

        await expect(page.locator('.currentSpeechInline .appliedMe')).toBeVisible();
        await expect(page.locator('.currentSpeechInline .number')).toContainText('1');
        await expect(page.locator('.currentSpeechInline .nameList')).toContainText('Testadmin');

        await page.evaluate(() => {
            const btn = document.querySelector('.waitingSingle .btnWithdraw') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });

        await expect(page.locator('.currentSpeechInline .number')).toContainText('0');
        await expect(page.locator('.currentSpeechInline .nameList')).not.toContainText('Testadmin');

        await page.locator('.currentSpeechInline .speechAdminLink').click();

        await expect(page.locator('.subqueueAdder form')).toHaveCount(0);
        await page.evaluate(() => {
            const btn = document.querySelector('.subqueues .adderOpener') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });
        await expect(page.locator('.subqueueAdder form')).toBeVisible();
        await page.locator('#subqueueAdderName-1').fill('Testperson');
        await page.evaluate(() => {
            const form = document.querySelector('.subqueues .subqueueAdder form') as HTMLFormElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('submit', false, true);
            form.dispatchEvent(evt);
        });

        await expect(page.locator('.slotPlaceholder.active')).toContainText('Testperson');
        await expect(page.locator('.slotActive.inactive')).toBeVisible();

        await page.evaluate(() => {
            const btn = document.querySelector('.slotPlaceholder.active') as HTMLElement;
            const evt = document.createEvent('HTMLEvents');
            evt.initEvent('click', false, true);
            btn.dispatchEvent(evt);
        });

        await expect(page.locator('.slotEntry.slotActive')).toContainText('Testperson');
        await expect(page.locator('.slotPlaceholder.inactive')).toBeVisible();

        await new ConsultationHomePage(page).open();
        await expect(page.locator('.currentSpeechInline .activeSpeaker')).toContainText('Testperson');

        await page.locator(`.motionLink2`).click();
        await expect(page.locator('.currentSpeechFooter .activeSpeaker')).toContainText('Testperson');
    });
});