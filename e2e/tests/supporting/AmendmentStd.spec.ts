import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';
import { AdminMotionListPage } from '../../pages/AdminMotionListPage';
import { FIRST_FREE_AMENDMENT_ID } from '../../utils/constants';

test.describe('Supporting: AmendmentStd', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('enable supporters for amendments, but not motions', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        const motionTypePage = new AdminMotionTypePage(page);
        await motionTypePage.open({ motionTypeId: 1 });

        await expect(page.locator('section.amendmentSupporters').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#typeSupportTypeAmendment').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#sameInitiatorSettingsForAmendments input')).toBeChecked();
        await page.locator('#sameInitiatorSettingsForAmendments input').first().uncheck();
        await expect(page.locator('section.amendmentSupporters').first()).toBeVisible();

        await page.locator('#typeSupportTypeAmendment').first().selectOption('1');
        await page.locator('#typeMinSupportersAmendment').first().fill('19');
        await page.locator('.adminTypeForm [name="save"]').first().click();

        await expect(page.locator('#typeSupportType').locator('option:checked')).toHaveText(
            /Nur die Antragsteller\*in/,
        );
        await expect(page.locator('#typeMinSupporters').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#typeSupportTypeAmendment').locator('option:checked')).toHaveText(
            /Von der Antragsteller\*in angegeben/,
        );
        await expect(page.locator('#typeMinSupportersAmendment')).toHaveValue('19');

        await new ConsultationHomePage(page).open();
        await test.step('confirm supporters can not be entered for motions', async () => {
            await page.locator('#sidebar .createMotion1').click();
            await expect(page.locator('.initiatorData').first()).toBeVisible();
            await expect(page.locator('.supporterData').filter({ visible: true })).toHaveCount(0);

            await new ConsultationHomePage(page).open();
        });

        await test.step('test the settings for amendments', async () => {
            await page.locator('.motionLink58').click();
            await page.locator('.sidebarActions .amendmentCreate a').click();

            await expect(page.locator('.supporterData').first()).toBeVisible();
            await expect(page.locator('.fullTextAdder').first()).toBeVisible();
            await expect(page.locator('#supporterFullTextHolder').filter({ visible: true })).toHaveCount(0);

            await page.locator('.fullTextAdder button').click();
            await expect(page.locator('#supporterFullTextHolder').first()).toBeVisible();

            const supporters = Array.from({ length: 19 }, (_, s) => `Person ${s}, KV ${s}`);
            await page.locator('#supporterFullTextHolder textarea').first().fill(supporters.join('; '));
            await page.locator('#supporterFullTextHolder .fullTextAdd').click();

            await page.locator('#initiatorPrimaryName').first().fill('Mein Name');
            await page.locator('#initiatorEmail').first().fill('test@example.org');

            await page.locator('#amendmentEditForm [name="save"]').click();
            await page.locator('#amendmentConfirmForm [name="confirm"]').click();

            await new AdminIndexPage(page).open();
            const motionList = new AdminMotionListPage(page);
            await motionList.open();
            await page.locator(`.amendment${FIRST_FREE_AMENDMENT_ID} .prefixCol a`).click();

            await expect(page.locator('.supporters')).toContainText('Person 13');
            await expect(page.locator('.supporters')).toContainText('KV 1');
        });
    });
});