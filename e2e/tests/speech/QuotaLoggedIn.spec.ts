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
        await expect(page.locator('.currentSpeechInline').filter({ visible: true })).toHaveCount(0);

        await loginAsStdAdmin(page);
        const appearancePage = new AdminAppearancePage(page);
        await appearancePage.open();

        await expect(page.locator('#hasSpeechLists')).not.toBeChecked();
        await expect(page.locator('.quotas').filter({ visible: true })).toHaveCount(0);
        await page.locator('#hasSpeechLists').first().check();
        await expect(page.locator('.quotas').first()).toBeVisible();
        await page.locator('#hasMultipleSpeechLists').first().check();
        await page.locator('#speechRequiresLogin').first().check();
        await expect(page.locator('.quotaName1 input')).toHaveValue('Offen / Männer');
        await page.locator('.quotaName1 input').first().fill('Offener Platz');
        // The fixture has the "Currently debated" module on, which would take the place of this widget
        await page.locator('#hasCurrentlyDebated').first().uncheck();
        await appearancePage.saveForm();

        await new ConsultationHomePage(page).open();

        await expect(page.locator('.currentSpeechInline').first()).toBeVisible();
        await expect(page.locator('.currentSpeechInline')).toContainText('Redeliste');
        await expect(page.locator('.waitingSubqueues')).toContainText('Frauen');
        await expect(page.locator('.waitingSubqueues').getByText('Offen / Männer').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.waitingSubqueues')).toContainText('Offener Platz');
        await expect(page.locator('.currentSpeechInline .notPossible').first()).toBeVisible();
        await expect(page.locator('.currentSpeechInline .speechAdminLink').first()).toBeVisible();

        await page.locator(`.motionLink2`).click();
        await expect(page.locator('.currentSpeechFooter').first()).toBeVisible();
        await expect(page.locator('.currentSpeechFooter')).toContainText('Redeliste');
        await expect(page.locator('.waitingMultiple')).toContainText('Frauen');
        await expect(page.locator('.waitingMultiple').getByText('Offen / Männer').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.waitingMultiple')).toContainText('Offener Platz');

        await page.locator('.currentSpeechFooter .speechAdminLink').click();

        await expect(page.locator('.slotActive.inactive .nameNobody').first()).toBeVisible();
        await expect(page.locator('.slotPlaceholder.inactive .nameNobody').first()).toBeVisible();
        await expect(page.locator('.subqueues')).toContainText('Frauen');
        await expect(page.locator('.subqueues')).toContainText('Offener Platz');

        await page.locator('.toolbarBelowTitle.settings .settingsOpen input').first().check();

        await new ConsultationHomePage(page).open();
        await expect(page.locator('.currentSpeechInline').first()).toBeVisible();
        await expect(page.locator('.currentSpeechInline .appliedMe').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.currentSpeechInline')).toContainText('Redeliste');
await expect(page.locator('.currentSpeechInline .number').first()).toContainText('0');
await expect(page.locator('.currentSpeechInline .notPossible').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.waitingSubqueues .applied button').first()).toContainText('Bewerben');

        await page.locator('.waitingSubqueues .applied button').nth(1).click();
        await expect(page.locator('#speechRegisterName2')).toHaveValue('Testadmin');

        await page.locator('.waitingSubqueues form').nth(0).locator('button').click();

        await expect(page.locator('.currentSpeechInline .appliedMe').first()).toBeVisible();
        await expect(page.locator('.currentSpeechInline .number')).toContainText('1');
        await expect(page.locator('.currentSpeechInline .nameList')).toContainText('Testadmin');

await page.locator('.waitingSubqueues .btnWithdraw').nth(0).click();
await expect(page.locator('.currentSpeechInline .number').first()).toContainText('0');
await expect(page.locator('.currentSpeechInline').getByText('Testadmin').filter({ visible: true })).toHaveCount(0);

        await page.locator('.currentSpeechInline .speechAdminLink').click();

        await expect(page.locator('.subqueueAdder form').filter({ visible: true })).toHaveCount(0);
        await page.locator('.subqueues .adderOpener').nth(0).click();
        await expect(page.locator('.subqueueAdder form').first()).toBeVisible();
        await page.locator('#subqueueAdderName1').first().fill('Testperson');
        await page.locator('.subqueues .subqueueAdder form').nth(0).locator('button').click();

        await expect(page.locator('.slotPlaceholder.active')).toContainText('Testperson');
        await expect(page.locator('.slotActive.inactive').first()).toBeVisible();

        await page.locator('.slotPlaceholder.active').nth(0).click();

        await expect(page.locator('.slotEntry.slotActive')).toContainText('Testperson');
        await expect(page.locator('.slotPlaceholder.inactive').first()).toBeVisible();

        await new ConsultationHomePage(page).open();
        await expect(page.locator('.currentSpeechInline .activeSpeaker')).toContainText('Testperson');

        await page.locator(`.motionLink2`).click();
        await expect(page.locator('.currentSpeechFooter .activeSpeaker')).toContainText('Testperson');
    });
});
