import { test, expect } from '../../fixtures';
import {ConsultationHomePage} from '../../pages/ConsultationHomePage';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import {
    FIRST_FREE_CONSULTATION_ID,
    FIRST_FREE_MOTION_TYPE,
} from '../../utils/constants';
import { dispatchClick } from '../../utils/dom';

test.describe('Admin: CreateConsultationByWizard', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('create a new consultation with the wizard', async ({ page }) => {
        await test.step('create a new consultation with the wizard', async () => {
            await new ConsultationHomePage(page).open();
            await loginAsStdAdmin(page);
            await new ConsultationHomePage(page).open();
            await page.locator('#adminLink').click();
            await page.locator('.siteConsultationsLink').click();

            await expect(page.locator('.consultation1')).toContainText('Standard-Veranstaltung');

            await page.locator('#newTitle').first().fill('Neue Veranstaltung 1');
            await page.locator('#newShort').first().fill('NeuKurz');
            await page.locator('#newPath').first().fill('neukurz');
            await page.locator('#newSetStandard').first().uncheck();

            await expect(page.locator('.settingsTypeWizard').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.settingsTypeTemplate').first()).toBeVisible();
            await page.locator('#settingsTypeWizard').first().check();
            await expect(page.locator('.settingsTypeTemplate').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.settingsTypeWizard').first()).toBeVisible();

            await expect(page.locator('#panelFunctionality')).toContainText(
                'Welche Bestandteile soll die Seite haben?',
            );

            await expect(page.locator('.checkbox-label.value-motion.active').first()).toBeVisible();
            await dispatchClick(page, '.checkbox-label.value-agenda');
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

            await page.locator('#panelSiteData button.btn-primary').click();

            await expect(page.locator('body')).toContainText('Die neue Veranstaltung wurde angelegt.');
            await expect(
                page.locator(`.consultation${FIRST_FREE_CONSULTATION_ID}`),
            ).toContainText('Neue Veranstaltung 1');
            await expect(page.locator('.consultation1')).toContainText('Standard-Veranstaltung');
        });

        await test.step('check that the settings were set correctly', async () => {
            await page.locator('#adminLink').click();
            await page.locator(`.motionType${FIRST_FREE_MOTION_TYPE}`).click();

            await expect(page.locator('#deadlineFormTypeComplex')).not.toBeChecked();
            await expect(page.locator('#typeSimpleDeadlineMotions')).toHaveValue('30.12.2028 20:00');
            await expect(page.locator('#typeSimpleDeadlineAmendments')).toHaveValue('30.11.2026 20:00');

            await page.locator('#adminLink').click();
            await page.locator('#consultationLink').click();
            await expect(page.locator('#maintenanceMode')).toBeChecked();
        });

        await test.step('set the new consultation as standard', async () => {
            await page.locator('#adminLink').click();
            await page.locator('.siteConsultationsLink').click();

            await dispatchClick(
                page,
                `.consultation${FIRST_FREE_CONSULTATION_ID} .stdbox button`,
            );
            await expect(page.locator('body')).toContainText(
                'Die Veranstaltung wurde als Standard-Veranstaltung festgelegt.',
            );

            await page.goto('/stdparteitag');
            await expect(page.locator('h1')).toContainText('Neue Veranstaltung 1');
            await expect(page.locator('body')).toContainText('Wahl: 1. Vorsitzende');
            await expect(page.locator('body')).toContainText('3. Sonstiges');

            await logout(page);
            await expect(page.locator('h1').getByText('Neue Veranstaltung 1').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('h1')).toContainText('Wartungsmodus');
        });
    });
});