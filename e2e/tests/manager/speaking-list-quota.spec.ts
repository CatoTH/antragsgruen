import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { dispatchClick } from '../../utils/dom';
import { gotoConsultationHome } from '../../utils/navigation';

test.describe('Manager: speaking list quotas wizard', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('wizard: create congress with speaking list quotas', async ({ page }) => {
        await page.goto('/antragsgruen_sites/manager/index');
        await loginAsStdAdmin(page);

        await test.step('go to creation form', async () => {
            await expect(page.locator('.siteCreateForm').first()).toBeVisible();
            await page.locator('.siteCreateForm [type="submit"]').click();
        });

        await test.step('click through the wizard', async () => {
            await expect(page.locator('#panelFunctionality')).toContainText(
                'Welche Bestandteile soll die Seite haben?',
            );
            await expect(page.locator('.checkbox-label.value-motion.active').first()).toBeVisible();
            // Speaking lists are set up as part of the "Currently debated" feature; they have no
            // wizard option of their own
            await expect(page.locator('.checkbox-label.value-debate.active').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '.checkbox-label.value-debate');
            await expect(page.locator('.checkbox-label.value-debate.active').first()).toBeVisible();
            await page.locator('#panelFunctionality button.btn-next').click();

            await page.locator('#panelSingleMotion .value-0').click();
            await page.locator('#panelSingleMotion button.btn-next').click();

            await page.locator('#panelMotionWho .value-3').click();
            await page.locator('#panelMotionWho button.btn-next').click();

            await page.locator('#panelMotionDeadline .value-0').click();
            await page.locator('#panelMotionDeadline button.btn-next').click();

            await page.locator('#panelMotionScreening .value-1').click();
            await page.locator('#panelMotionScreening button.btn-next').click();

            await page.locator('#panelNeedsSupporters .value-0').click();
            await page.locator('#panelNeedsSupporters button.btn-next').click();

            await page.locator('#panelHasAmendments .value-0').click();
            await page.locator('#panelHasAmendments button.btn-next').click();

            await page.locator('#panelComments .value-1').click();
            await page.locator('#panelComments button.btn-next').click();

            await page.locator('#panelSpeechLogin .value-0').click();
            await page.locator('#panelSpeechLogin button.btn-next').click();

            await page.locator('#panelSpeechQuotas .value-1').click();
            await page.locator('#panelSpeechQuotas button.btn-next').click();

            await page.locator('#panelOpenNow .value-0').click();
            await page.locator('#panelOpenNow button.btn-next').click();

            await page.locator('#siteTitle').first().fill('Test-Congress');
            await page.locator('#siteOrganization').first().fill('My party');
            await page.locator('#siteSubdomain').first().fill('testcongress');
            await page.locator('#siteSubdomain').dispatchEvent('change');
            await page.waitForTimeout(500);
            await page.locator('#siteContact').first().fill('I myself\nMy address');

            await page.locator('form.siteCreate [name="create"]').click();

            await expect(page.locator('body')).toContainText('Die Veranstaltung wurde angelegt.');
        });

        await test.step('open the consultation', async () => {
            await page.locator('.createdForm [type="submit"]').click();

            await expect(page.locator('body')).toContainText('Hallo auf Antragsgrün');
            await expect(page.locator('h1')).toContainText('Test-Congress');
            await expect(page.locator('.currentDebateAdmin').first()).toBeVisible();
        });

        await test.step('replace the debate widget by a standalone speaking list', async () => {
            await page.locator('#adminLink').click();
            await page.locator('#appearanceLink').click();
            await page.locator('#hasCurrentlyDebated').first().uncheck();
            await page.locator('#hasSpeechLists').first().check();
            // The quotas configured in the wizard are the ones the list is created with
            await expect(page.locator('#activateFirstSpeechList')).toBeChecked();
            await expect(page.locator('#hasMultipleSpeechLists')).toBeChecked();
            await page.locator('#consultationAppearanceForm [name="save"]').first().click();

            await gotoConsultationHome(page, true, 'testcongress', 'testcongress');
            await expect(page.locator('.currentDebateAdmin').filter({ visible: true })).toHaveCount(0);

            await expect(page.locator('.currentSpeechInline')).toContainText('Redeliste');
            await expect(page.locator('.waitingSubqueues')).toContainText('Frauen');
            await expect(page.locator('.waitingSubqueues')).toContainText('Offen / Männer');

            await expect(page.locator('#sidebar')).toContainText('Antrag stellen');
        });
    });
});
