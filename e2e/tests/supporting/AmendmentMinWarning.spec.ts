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
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        const motionTypePage = new AdminMotionTypePage(page);
        await motionTypePage.open({ motionTypeId: 1 });

        await expect(page.locator('section.amendmentSupporters')).toHaveCount(0);
        await expect(page.locator('#sameInitiatorSettingsForAmendments input')).toBeChecked();
        await page.locator('#sameInitiatorSettingsForAmendments input').uncheck();
        await expect(page.locator('section.amendmentSupporters')).toBeVisible();

        await page.locator('#typeSupportTypeAmendment').selectOption('1');
        await page.locator('#typeMinSupportersAmendment').fill('19');

        await motionTypePage.saveForm();

        await new ConsultationHomePage(page).open();
        await page.locator(`.motionLink2`).click();
        await page.locator('.sidebarActions .amendmentCreate a').click();

        await expect(page.locator('.supporterData')).toBeVisible();

        await page.locator('#initiatorPrimaryName').fill('Mein Name');
        await page.locator('#initiatorEmail').fill('test@example.org');
        await page.evaluate(() => {
            document.querySelectorAll('[required]').forEach((el) => el.removeAttribute('required'));
        });
        await page.locator('#amendmentEditForm [name="save"]').click();

        await expectBootboxDialog(page, /Es müssen mindestens 19 Unterstützer\*innen angegeben werden/);
        await acceptBootbox(page);

        await page.locator('#personTypeOrga').selectOption('1');
        await page.locator('#initiatorPrimaryName').fill('Mein Name');
        await page.locator('#amendmentEditForm [name="save"]').click();

        await expectBootboxDialog(page, /Es muss ein Beschlussdatum angegeben werden/);
        await acceptBootbox(page);

        await page.locator('#resolutionDate').fill('01.01.2000');
        await page.locator('#amendmentEditForm [name="save"]').click();

        await expect(page.locator('.bootbox')).toHaveCount(0);
        await expect(page.locator('body')).not.toContainText('Not enough supporters.');
        await expect(page.locator('h1')).toContainText(/änderungsantrag bestätigen/i);

        await new ConsultationHomePage(page).open();
        await page.locator('#sidebar .createMotion1').click();
        await expect(page.locator('.supporterData')).toHaveCount(0);
    });
});