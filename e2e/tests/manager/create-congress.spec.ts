import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { dispatchClick } from '../../utils/dom';

test.describe('Manager: create congress (full wizard)', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('wizard: create full congress with all features', async ({ page }) => {
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
            await expect(page.locator('.checkbox-label.value-agenda.active').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.checkbox-label.value-votings.active').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.checkbox-label.value-documents.active').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '.checkbox-label.value-agenda');
            await dispatchClick(page, '.checkbox-label.value-votings');
            await dispatchClick(page, '.checkbox-label.value-documents');
            await page.waitForTimeout(200);
            await expect(page.locator('.checkbox-label.value-agenda.active').first()).toBeVisible();
            await expect(page.locator('.checkbox-label.value-votings.active').first()).toBeVisible();
            await expect(page.locator('.checkbox-label.value-documents.active').first()).toBeVisible();
            await page.locator('#panelFunctionality button.btn-next').click();

            await page.locator('#panelSingleMotion .value-0').click();
            await page.locator('#panelSingleMotion button.btn-next').click();

            await page.locator('#panelMotionWho .value-3').click();
            await page.locator('#panelMotionWho button.btn-next').click();

            await page.locator('#panelMotionDeadline .value-1').click();
            await page.locator('#panelMotionDeadline .value-1 .date input').first().fill('30.12.2028 20:00');
            await page.locator('#panelMotionDeadline button.btn-next').click();

            await page.locator('#panelMotionScreening .value-1').click();
            await page.locator('#panelMotionScreening button.btn-next').click();

            await page.locator('#panelNeedsSupporters .value-1').click();
            await page.locator('#panelNeedsSupporters .value-1 .description input').first().fill('1');
            await page.locator('#panelNeedsSupporters button.btn-next').click();

            await page.locator('#panelHasAmendments .value-1').click();
            await page.locator('#panelHasAmendments button.btn-next').click();

            await page.locator('#panelAmendSinglePara .value-1').click();
            await page.locator('#panelAmendSinglePara button.btn-next').click();

            await page.locator('#panelAmendWho .value-3').click();
            await page.locator('#panelAmendWho button.btn-next').click();

            await page.locator('#panelAmendDeadline .value-1').click();
            await page.locator('#panelAmendDeadline .value-1 .date input').first().fill('30.11.2026 20:00');
            await page.locator('#panelAmendDeadline button.btn-next').click();

            await page.locator('#panelAmendScreening .value-1').click();
            await page.locator('#panelAmendScreening button.btn-next').click();

            await page.locator('#panelComments .value-1').click();
            await page.locator('#panelComments button.btn-next').click();

            await page.locator('#panelOpenNow .value-0').click();
            await page.locator('#panelOpenNow button.btn-next').click();

            await page.locator('#siteTitle').first().fill('Test-Congress');
            await page.locator('#siteOrganization').first().fill('My party');
            await expect(page.locator('.subdomainError').filter({ visible: true })).toHaveCount(0);
            await page.locator('#siteSubdomain').first().fill('stdparteitag');
            await page.locator('#siteSubdomain').dispatchEvent('change');
            await page.waitForTimeout(500);
            await expect(page.locator('.subdomainError').first()).toBeVisible();
            await expect(page.locator('.subdomainError')).toContainText('stdparteitag');
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
            await expect(page.locator('.agendaItem')).toContainText('Tagesordnung');
            await expect(page.locator('.deadlineCircle')).toContainText('Änderungs&shy;anträge');
            await expect(page.locator('.deadlineCircle')).toContainText('30.11.2026 20:00');

            await expect(page.locator('#documentsLink').first()).toBeVisible();
            await expect(page.locator('#votingsLink').first()).toBeVisible();

            await logout(page);

            await expect(page.locator('body')).not.toContainText('Hallo auf Antragsgrün', { useInnerText: true });
            await expect(page.locator('h1').getByText('Test-Congress').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('h1')).toContainText('Wartungsmodus');

            await page.goto('/testcongress/testcongress');
        });

        await test.step('check the imprint', async () => {
            await page.locator('#legalLink').click();
            await expect(page.locator('body')).toContainText('I myself');
            await expect(page.locator('body')).toContainText('My address');
        });
    });
});
