import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';

test.describe('Supporting: AmendmentMinGender', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('enable collecting supporters, min. 1 female', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        const motionTypePage = new AdminMotionTypePage(page);
        await motionTypePage.open({ motionTypeId: 1 });
        await expect(page.locator('#amendmentSupportersForm').filter({ visible: true })).toHaveCount(0);
        await page.locator('#sameInitiatorSettingsForAmendments input').first().uncheck();
        await expect(page.locator('#amendmentSupportersForm').first()).toBeVisible();
        await expect(page.locator('#typeMinSupportersFemaleRowAmendment').filter({ visible: true })).toHaveCount(0);
        await page.locator("input[name='amendmentInitiatorSettings[contactGender]'][value='2']").first().check();
        await expect(page.locator('#typeMinSupportersFemaleRowAmendment').filter({ visible: true })).toHaveCount(0);
        await page.locator('#typeSupportTypeAmendment').first().selectOption('2');
        await page.locator('#typePolicySupportAmendments').first().selectOption('2');
        await page.locator("input[name='type[amendmentLikesDislikes][]'][value='4']").first().check();
        await expect(page.locator('#typeMinSupportersFemaleRowAmendment').first()).toBeVisible();
        await page.locator('#typeMinSupportersAmendment').first().fill('1');
        await page.locator('#typeMinSupportersFemaleAmendment').first().fill('1');
        await page.locator('#typeAllowMoreSupportersAmendment').first().check();
        await motionTypePage.saveForm();

        await home.gotoAmendmentCreatePage();
        await page.locator('#sections_30').first().fill('New amendment');
        await page.locator("input[name='Initiator[primaryName]']").first().fill('Mein Name');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();
        const url = await page.locator('#urlSharing').inputValue();

        await logout(page);
        await loginAsStdUser(page);
        await page.goto(url);

        await expect(page.locator('body')).toContainText('1 Unterstützer*innen, davon 1 Frau');
        await expect(page.locator('body')).toContainText('aktueller Stand: 0 / 0');
        await expect(page.locator('.motionSupportForm').first()).toBeVisible();
        await page.locator("input[name='motionSupportOrga']").first().fill('TestOrga');
        await test.step('create an amendment', async () => {
            await page.locator('#motionSupportGender').first().selectOption('Männlich');
            await page.locator('.motionSupportForm [name="motionSupport"]').click();
            await expect(page.locator('body')).toContainText('Du unterstützt diesen Änderungsantrag nun.');
            await expect(page.locator('body')).toContainText('aktueller Stand: 1 / 0');

            await page.locator('.motionSupportForm [name="motionSupportRevoke"]').click();
        });

        await test.step('support it as a second man', async () => {
            await expect(page.locator('body')).toContainText('aktueller Stand: 0 / 0');
            await page.locator("input[name='motionSupportOrga']").first().fill('TestOrga');
            await page.locator('#motionSupportGender').first().selectOption('Weiblich');
        });

        await test.step('support it as woman', async () => {
            await page.locator('.motionSupportForm [name="motionSupport"]').click();
            await expect(page.locator('body')).toContainText('Du unterstützt diesen Änderungsantrag nun.');
            await expect(page.locator('body')).toContainText('Die Mindestzahl an Unterstützer*innen (1) wurde erreicht');
        });
    });
});