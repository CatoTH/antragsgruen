import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';

test.describe('Manager: speaking list quotas wizard', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('wizard: create congress with speaking list quotas', async ({ page }) => {
        await page.goto('/antragsgruen_sites/manager/index');
        await loginAsStdAdmin(page);

        await expect(page.locator('.siteCreateForm')).toBeVisible();
        await page.locator('.siteCreateForm [type="submit"]').click();

        await expect(page.locator('#panelFunctionality')).toContainText(
            'Welche Bestandteile soll die Seite haben?',
        );
        await expect(page.locator('.checkbox-label.value-motion.active')).toBeVisible();
        await expect(page.locator('.checkbox-label.value-speech.active')).toHaveCount(0);
        await page.locator('.checkbox-label.value-speech').click();
        await page.waitForTimeout(200);
        await expect(page.locator('.checkbox-label.value-speech.active')).toBeVisible();
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

        await page.locator('#siteTitle').fill('Test-Congress');
        await page.locator('#siteOrganization').fill('My party');
        await page.locator('#siteSubdomain').fill('testcongress');
        await page.locator('#siteSubdomain').dispatchEvent('change');
        await page.waitForTimeout(500);
        await page.locator('#siteContact').fill('I myself\nMy address');

        await page.locator('form.siteCreate [name="create"]').click();

        await expect(page.locator('body')).toContainText('Die Veranstaltung wurde angelegt.');

        await page.locator('.createdForm [type="submit"]').click();

        await expect(page.locator('body')).toContainText('Hallo auf Antragsgrün');
        await expect(page.locator('h1')).toContainText('Test-Congress');

        await expect(page.locator('.currentSpeechInline')).toContainText('Redeliste');
        await expect(page.locator('.waitingSubqueues')).toContainText('Frauen');
        await expect(page.locator('.waitingSubqueues')).toContainText('Offen / Männer');

        await expect(page.locator('#sidebar')).toContainText('Antrag stellen');
    });
});
