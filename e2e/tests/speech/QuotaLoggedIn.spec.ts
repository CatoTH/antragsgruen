import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminAppearancePage } from '../../pages/AdminAppearancePage';

test.describe('Speech: quota, login required', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('enable quota speech lists and test as admin', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await expect(page.locator('.currentSpeechInline')).toHaveCount(0);

        await loginAsStdAdmin(page);
        const appearancePage = new AdminAppearancePage(page);
        await appearancePage.open();

        await expect(page.locator('#hasSpeechLists')).not.toBeChecked();
        await expect(page.locator('.quotas')).not.toBeVisible();
        await page.locator('#hasSpeechLists').check();
        await expect(page.locator('.quotas')).toBeVisible();
        await page.locator('#hasMultipleSpeechLists').check();
        await page.locator('#speechRequiresLogin').check();
        await expect(page.locator('.quotaName1 input')).toHaveValue('Offen / Männer');
        await page.locator('.quotaName1 input').fill('Offener Platz');
        await appearancePage.saveForm();

        await new ConsultationHomePage(page).open();

        await expect(page.locator('.currentSpeechInline')).toBeVisible();
        await expect(page.locator('.currentSpeechInline')).toContainText('Redeliste');
        await expect(page.locator('.waitingSubqueues')).toContainText('Frauen');
        await expect(page.locator('.waitingSubqueues')).not.toContainText('Offen / Männer');
        await expect(page.locator('.waitingSubqueues')).toContainText('Offener Platz');
        await expect(page.locator('.currentSpeechInline .notPossible')).toBeVisible();
        await expect(page.locator('.currentSpeechInline .speechAdminLink')).toBeVisible();

        await page.locator(`.motionLink2`).click();
        await expect(page.locator('.currentSpeechFooter')).toBeVisible();
        await expect(page.locator('.currentSpeechFooter')).toContainText('Redeliste');
        await expect(page.locator('.waitingMultiple')).toContainText('Frauen');
        await expect(page.locator('.waitingMultiple')).not.toContainText('Offen / Männer');
        await expect(page.locator('.waitingMultiple')).toContainText('Offener Platz');

        await page.locator('.currentSpeechFooter .speechAdminLink').click();

        await expect(page.locator('.slotActive.inactive .nameNobody')).toBeVisible();
        await expect(page.locator('.slotPlaceholder.inactive .nameNobody')).toBeVisible();
        await expect(page.locator('.subqueues')).toContainText('Frauen');
        await expect(page.locator('.subqueues')).toContainText('Offener Platz');

        await page.locator('.toolbarBelowTitle.settings .settingsOpen input').first().check();

        await new ConsultationHomePage(page).open();
        await expect(page.locator('.currentSpeechInline')).toBeVisible();
        await expect(page.locator('.currentSpeechInline .appliedMe')).toHaveCount(0);
        await expect(page.locator('.currentSpeechInline')).toContainText('Redeliste');
await expect(page.locator('.currentSpeechInline .number').first()).toContainText('0');
await expect(page.locator('.currentSpeechInline .notPossible')).toHaveCount(0);
        await expect(page.locator('.waitingSubqueues .applied button').first()).toContainText('Bewerben');

        await page.locator('.waitingSubqueues .applied button').nth(1).click();
        await expect(page.locator('#speechRegisterName2')).toHaveValue('Testadmin');

        await page.locator('.waitingSubqueues form').nth(0).locator('button').click();

        await expect(page.locator('.currentSpeechInline .appliedMe')).toBeVisible();
        await expect(page.locator('.currentSpeechInline .number')).toContainText('1');
        await expect(page.locator('.currentSpeechInline .nameList')).toContainText('Testadmin');

await page.locator('.waitingSubqueues .btnWithdraw').nth(0).click();
await expect(page.locator('.currentSpeechInline .number').first()).toContainText('0');
await expect(page.locator('.currentSpeechInline')).not.toContainText('Testadmin');

        await page.locator('.currentSpeechInline .speechAdminLink').click();

        await expect(page.locator('.subqueueAdder form')).toHaveCount(0);
        await page.locator('.subqueues .adderOpener').nth(0).click();
        await expect(page.locator('.subqueueAdder form')).toBeVisible();
        await page.locator('#subqueueAdderName1').fill('Testperson');
        await page.locator('.subqueues .subqueueAdder form').nth(0).locator('button').click();

        await expect(page.locator('.slotPlaceholder.active')).toContainText('Testperson');
        await expect(page.locator('.slotActive.inactive')).toBeVisible();

        await page.locator('.slotPlaceholder.active').nth(0).click();

        await expect(page.locator('.slotEntry.slotActive')).toContainText('Testperson');
        await expect(page.locator('.slotPlaceholder.inactive')).toBeVisible();

        await new ConsultationHomePage(page).open();
        await expect(page.locator('.currentSpeechInline .activeSpeaker')).toContainText('Testperson');

        await page.locator(`.motionLink2`).click();
        await expect(page.locator('.currentSpeechFooter .activeSpeaker')).toContainText('Testperson');
    });
});
