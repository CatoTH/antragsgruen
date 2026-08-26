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
        await expect(page.locator('#typeMinSupportersFemaleRow').filter({ visible: true })).toHaveCount(0);
        await page.locator("input[name='motionInitiatorSettings[contactGender]'][value='2']").first().check();
        await expect(page.locator('#typeMinSupportersFemaleRow').filter({ visible: true })).toHaveCount(0);
        await page.locator('#typeSupportType').first().selectOption('2');
        await page.locator('#typePolicySupportMotions').first().selectOption('2');
        await page.locator("input[name='type[motionLikesDislikes][]'][value='4']").first().check();
        await expect(page.locator('#typeMinSupportersFemaleRow').first()).toBeVisible();
        await page.locator('#typeMinSupporters').first().fill('1');
        await page.locator('#typeMinSupportersFemale').first().fill('1');
        await page.locator('#typeAllowMoreSupporters').first().check();
        await page.locator('.adminTypeForm [name="save"]').first().click();

        await home.gotoMotionCreatePage();
        await page.locator("input[name='tags[]'][value='1']").first().check();
        await page.locator("[name='sections[1]']").first().fill('Testantrag 1');
        await test.step('create a motion', async () => {
            await page.locator('#initiatorGender').first().selectOption('Männlich');
            await page.locator('#motionEditForm [name="save"]').click();
            await page.locator('#motionConfirmForm [name="confirm"]').click();
            const url = await page.locator('#urlSharing').inputValue();

            await logout(page);
            await loginAsStdUser(page);
            await page.goto(url);
        });

        await test.step('support it as a second man', async () => {
            await expect(page.locator('body')).toContainText('1 Unterstützer*innen, davon 1 Frau');
            await expect(page.locator('body')).toContainText('aktueller Stand: 0 / 0');
        });

        await test.step('support it as woman', async () => {
            await expect(page.locator('.motionSupportForm').first()).toBeVisible();
            await page.locator("input[name='motionSupportOrga']").first().fill('TestOrga');
            await page.locator('#motionSupportGender').first().selectOption('Männlich');
            await page.locator('.motionSupportForm [name="motionSupport"]').click();
            await expect(page.locator('body')).toContainText('Du unterstützt diesen Antrag nun.');
            await expect(page.locator('body')).toContainText('aktueller Stand: 1 / 0');

            await page.locator('.motionSupportForm [name="motionSupportRevoke"]').click();
            await expect(page.locator('body')).toContainText('aktueller Stand: 0 / 0');
            await page.locator("input[name='motionSupportOrga']").first().fill('TestOrga');
            await page.locator('#motionSupportGender').first().selectOption('Weiblich');
            await page.locator('.motionSupportForm [name="motionSupport"]').click();
            await expect(page.locator('body')).toContainText('Du unterstützt diesen Antrag nun.');
            await expect(page.locator('body')).toContainText('Die Mindestzahl an Unterstützer*innen (1) wurde erreicht');
        });
    });
});