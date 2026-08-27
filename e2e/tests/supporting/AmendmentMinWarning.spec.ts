import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';
import { expectBootboxDialog, acceptBootbox } from '../../utils/dom';

test.describe('Supporting: AmendmentMinWarning', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('supporter warning appears for natural persons but not for organizations', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        const motionTypePage = new AdminMotionTypePage(page);
        await motionTypePage.open({ motionTypeId: 1 });

        await test.step('make sure the supporter-warning appears for natural persons', async () => {
            await expect(page.locator('section.amendmentSupporters').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#sameInitiatorSettingsForAmendments input')).toBeChecked();
            await page.locator('#sameInitiatorSettingsForAmendments input').first().uncheck();
            await expect(page.locator('section.amendmentSupporters').first()).toBeVisible();

            await page.locator('#typeSupportTypeAmendment').first().selectOption('1');
            await page.locator('#typeMinSupportersAmendment').first().fill('19');

            await page.locator('.adminTypeForm [name="save"]').first().click();

            await new ConsultationHomePage(page).open();
            await page.locator(`.motionLink2`).click();
            await page.locator('.sidebarActions .amendmentCreate a').click();

            await expect(page.locator('.supporterData').first()).toBeVisible();

            await page.locator('#initiatorPrimaryName').first().fill('Mein Name');
            await page.locator('#initiatorEmail').first().fill('test@example.org');
            await page.evaluate(() => {
                document.querySelectorAll('[required]').forEach((el) => el.removeAttribute('required'));
            });
            await page.locator('#amendmentEditForm [name="save"]').click();

            await expectBootboxDialog(page, /Es müssen mindestens 19 Unterstützer\*innen angegeben werden/);
            await acceptBootbox(page);
        });

        await test.step('make sure it does not appear for organizations', async () => {
            await page.locator('#personTypeOrga').first().check();
            await page.locator('#initiatorPrimaryName').first().fill('Mein Name');
            await page.locator('#amendmentEditForm [name="save"]').click();

            await expectBootboxDialog(page, /Es muss ein Beschlussdatum angegeben werden/);
            await acceptBootbox(page);

            await page.locator('#resolutionDate').first().fill('01.01.2000');
            await page.locator('#amendmentEditForm [name="save"]').click();

            await expect(page.locator('.bootbox').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('body')).not.toContainText('Not enough supporters.', { useInnerText: true });
            await expect(page.locator('h1')).toContainText(/änderungsantrag bestätigen/i);

            await new ConsultationHomePage(page).open();
        });

        await test.step('make sure the changes are not active for motions', async () => {
            await page.locator('#sidebar .createMotion1').click();
            await expect(page.locator('.supporterData').filter({ visible: true })).toHaveCount(0);
        });
    });
});