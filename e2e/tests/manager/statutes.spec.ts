import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { FIRST_FREE_MOTION_TYPE } from '../../utils/constants';
import { dispatchClick } from '../../utils/dom';

test.describe('Manager: statutes motion type wizard', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('wizard: create statutes congress and see the to-do entry', async ({ page }) => {
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
            await expect(page.locator('.checkbox-label.value-statute.active').filter({ visible: true })).toHaveCount(0);
            await dispatchClick(page, '.checkbox-label.value-motion');
            await dispatchClick(page, '.checkbox-label.value-statute');
            await page.waitForTimeout(200);
            await expect(page.locator('.checkbox-label.value-motion.active').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.checkbox-label.value-statute.active').first()).toBeVisible();
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

        await test.step('see the statutes in the to do', async () => {
            await page.locator('.createdForm [type="submit"]').click();

            await expect(page.locator('body')).toContainText('Hallo auf Antragsgrün');
            await expect(page.locator('h1')).toContainText('Test-Congress');

            await expect(page.locator('#adminTodo').first()).toBeVisible();
            await page.locator('#adminTodo').click();
            await expect(
                page.locator(`.statutesCreate${FIRST_FREE_MOTION_TYPE}`),
            ).toContainText('Satzungsänderungsanträge');

            await page.locator(`.statutesCreate${FIRST_FREE_MOTION_TYPE} a`).click();
            await expect(page.locator('.statuteCreateLnk').first()).toBeVisible();
            await expect(page.locator('#typeMotionPrefix')).toHaveValue('S');
        });
    });
});
