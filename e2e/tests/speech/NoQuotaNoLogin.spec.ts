import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminAppearancePage } from '../../pages/AdminAppearancePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';

test.describe('Speech: non-quota, loginless', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('enable non-quota speech lists and test as admin', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        await expect(page.locator('.currentSpeechInline').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#speechLink').filter({ visible: true })).toHaveCount(0);

        await loginAsStdAdmin(page);
        const appearancePage = new AdminAppearancePage(page);
        await new ConsultationHomePage(page).open();
        const adminIndexUrl = await new AdminIndexPage(page).getUrl();
        await page.goto(adminIndexUrl);
        await appearancePage.open();

        await expect(page.locator('#hasSpeechLists')).not.toBeChecked();
        await expect(page.locator('.quotas').filter({ visible: true })).toHaveCount(0);
        await page.locator('#hasSpeechLists').first().check();
        await expect(page.locator('.quotas').first()).toBeVisible();
        await page.locator('#activateFirstSpeechList').first().uncheck();
        await page.locator('#speechPage').first().check();
        // The fixture has the "Currently debated" module on, which would take the place of this widget
        await page.locator('#hasCurrentlyDebated').first().uncheck();
        await appearancePage.saveForm();

        await new ConsultationHomePage(page).open();
        await expect(page.locator('.currentSpeechInline').filter({ visible: true })).toHaveCount(0);

        await page.locator(`.motionLink2`).click();
        await expect(page.locator('.currentSpeechFooter').filter({ visible: true })).toHaveCount(0);

        await page.locator('#speechLink').click();
        await expect(page.locator('body')).toContainText('Die Redeliste ist nicht geöffnet');

        await page.locator('.speechAdminLink').click();

        await expect(page.locator('.slotActive.inactive .nameNobody').first()).toBeVisible();
        await expect(page.locator('.slotPlaceholder.inactive .nameNobody').first()).toBeVisible();
        await expect(page.locator('.subqueues')).toContainText('Warteliste');

        await expect(page.locator('.toolbarBelowTitle .settingsActive .inactive').first()).toBeVisible();

        await page.locator('.toolbarBelowTitle .settingsActive button').click();

        await page.locator('.toolbarBelowTitle.settings .settingsOpen input').first().check();

        await new ConsultationHomePage(page).open();
        await expect(page.locator('.currentSpeechInline').first()).toBeVisible();
        await expect(page.locator('.currentSpeechInline .appliedMe').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.currentSpeechInline')).toContainText('Redeliste');
        await expect(page.locator('.currentSpeechInline .number')).toContainText('0');
        await expect(page.locator('.currentSpeechInline .notPossible').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.waitingSingle .apply button')).toContainText('Bewerben');

        await page.locator('.waitingSingle .apply button').click();

        await expect(page.locator('#speechRegisterName-1')).toHaveValue('Testadmin');

        await page.locator('.waitingSingle form button').click();

        await expect(page.locator('.currentSpeechInline .appliedMe').first()).toBeVisible();
        await expect(page.locator('.currentSpeechInline .number')).toContainText('1');
        await expect(page.locator('.currentSpeechInline .nameList')).toContainText('Testadmin');

        await page.locator('.waitingSingle .btnWithdraw').click();

await expect(page.locator('.currentSpeechInline .number')).toContainText('0');
{
    const nameListExists = await page.locator('.currentSpeechInline .nameList').count();
    const nameListText = nameListExists > 0
        ? (await page.locator('.currentSpeechInline .nameList').textContent()) ?? ''
        : '';
    expect(nameListText).not.toContain('Testadmin');
}

        await page.locator('.currentSpeechInline .speechAdminLink').click();

        await expect(page.locator('.subqueueAdder form').filter({ visible: true })).toHaveCount(0);
        await page.locator('.subqueues .adderOpener').click();
        await expect(page.locator('.subqueueAdder form').first()).toBeVisible();
        await page.locator('#subqueueAdderName-1').first().fill('Testperson');
        await page.locator('.subqueues .subqueueAdder form button').click();

        await expect(page.locator('.slotPlaceholder.active')).toContainText('Testperson');
        await expect(page.locator('.slotActive.inactive').first()).toBeVisible();

        await page.locator('.slotPlaceholder.active').click();

        await expect(page.locator('.slotEntry.slotActive')).toContainText('Testperson');
        await expect(page.locator('.slotPlaceholder.inactive').first()).toBeVisible();

        await new ConsultationHomePage(page).open();
        await expect(page.locator('.currentSpeechInline .activeSpeaker')).toContainText('Testperson');

        await page.locator(`.motionLink2`).click();
        await expect(page.locator('.currentSpeechFooter .activeSpeaker')).toContainText('Testperson');
    });
});
