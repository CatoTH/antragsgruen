import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';

test.describe('Supporting: MotionMinGender', () => {
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
        await expect(page.locator('#typeMinSupportersFemaleRow')).toHaveCount(0);
        await page.locator("input[name='motionInitiatorSettings[contactGender]'][value='2']").check();
        await expect(page.locator('#typeMinSupportersFemaleRow')).toHaveCount(0);
        await page.locator('#typeSupportType').selectOption('2');
        await page.locator('#typePolicySupportMotions').selectOption('2');
        await page.locator("input[name='type[motionLikesDislikes][]'][value='4']").check();
        await expect(page.locator('#typeMinSupportersFemaleRow')).toBeVisible();
        await page.locator('#typeMinSupporters').fill('1');
        await page.locator('#typeMinSupportersFemale').fill('1');
        await page.locator('#typeAllowMoreSupporters').check();
        await page.locator('.adminTypeForm [name="save"]').first().click();

        await home.gotoMotionCreatePage();
        await page.locator("input[name='tags[]'][value='1']").check();
        await page.locator("[name='sections[1]']").fill('Testantrag 1');
        await page.locator('#initiatorGender').selectOption('Männlich');
        await page.locator('#motionEditForm [name="save"]').click();
        await page.locator('#motionConfirmForm [name="confirm"]').click();
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
        await expect(page.locator('body')).toContainText('Du unterstützt diesen Antrag nun.');
        await expect(page.locator('body')).toContainText('aktueller Stand: 1 / 0');

        await page.locator('.motionSupportForm [name="motionSupportRevoke"]').click();
        await expect(page.locator('body')).toContainText('aktueller Stand: 0 / 0');
        await page.locator("input[name='motionSupportOrga']").fill('TestOrga');
        await page.locator('#motionSupportGender').selectOption('Weiblich');
        await page.locator('.motionSupportForm [name="motionSupport"]').click();
        await expect(page.locator('body')).toContainText('Du unterstützt diesen Antrag nun.');
        await expect(page.locator('body')).toContainText('Die Mindestzahl an Unterstützer*innen (1) wurde erreicht');
    });
});