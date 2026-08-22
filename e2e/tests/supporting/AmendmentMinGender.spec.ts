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
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        const motionTypePage = new AdminMotionTypePage(page);
        await motionTypePage.open({ motionTypeId: 1 });
        await expect(page.locator('#amendmentSupportersForm')).toHaveCount(0);
        await page.locator('#sameInitiatorSettingsForAmendments input').uncheck();
        await expect(page.locator('#amendmentSupportersForm')).toBeVisible();
        await expect(page.locator('#typeMinSupportersFemaleRowAmendment')).toHaveCount(0);
        await page.locator("input[name='amendmentInitiatorSettings[contactGender]'][value='2']").check();
        await expect(page.locator('#typeMinSupportersFemaleRowAmendment')).toHaveCount(0);
        await page.locator('#typeSupportTypeAmendment').selectOption('2');
        await page.locator('#typePolicySupportAmendments').selectOption('2');
        await page.locator("input[name='type[amendmentLikesDislikes][]'][value='4']").check();
        await expect(page.locator('#typeMinSupportersFemaleRowAmendment')).toBeVisible();
        await page.locator('#typeMinSupportersAmendment').fill('1');
        await page.locator('#typeMinSupportersFemaleAmendment').fill('1');
        await page.locator('#typeAllowMoreSupportersAmendment').check();
        await motionTypePage.saveForm();

        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoAmendmentCreatePage();
        await page.locator('#sections_30').fill('New amendment');
        await page.locator("input[name='Initiator[primaryName]']").fill('Mein Name');
        await page.locator('#amendmentEditForm [name="save"]').click();
        await page.locator('#amendmentConfirmForm [name="confirm"]').click();
        const url = await page.locator('#urlSharing').inputValue();

        await logout(page);
        await loginAsStdUser(page);
        await page.goto(url);

        await expect(page.locator('body')).toContainText('1 Unterstützer*innen, davon 1 Frau');
        await expect(page.locator('body')).toContainText('aktueller Stand: 0 / 0');
        await expect(page.locator('.motionSupportForm')).toBeVisible();
        await page.locator("input[name='motionSupportOrga']").fill('TestOrga');
        await page.locator('#motionSupportGender').selectOption('Männlich');
        await page.locator('.motionSupportForm [name="motionSupport"]').click();
        await expect(page.locator('body')).toContainText('Du unterstützt diesen Änderungsantrag nun.');
        await expect(page.locator('body')).toContainText('aktueller Stand: 1 / 0');

        await page.locator('.motionSupportForm [name="motionSupportRevoke"]').click();
        await expect(page.locator('body')).toContainText('aktueller Stand: 0 / 0');
        await page.locator("input[name='motionSupportOrga']").fill('TestOrga');
        await page.locator('#motionSupportGender').selectOption('Weiblich');
        await page.locator('.motionSupportForm [name="motionSupport"]').click();
        await expect(page.locator('body')).toContainText('Du unterstützt diesen Änderungsantrag nun.');
        await expect(page.locator('body')).toContainText('Die Mindestzahl an Unterstützer*innen (1) wurde erreicht');
    });
});