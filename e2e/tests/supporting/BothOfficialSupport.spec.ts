import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminIndexPage } from '../../pages/AdminIndexPage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';
import { MotionPage } from '../../pages/MotionPage';
import { AmendmentPage } from '../../pages/AmendmentPage';

test.describe('Supporting: BothOfficialSupport', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('officially supporting motions and amendments', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);
        await new AdminIndexPage(page).open();
        const motionTypePage = new AdminMotionTypePage(page);
        await motionTypePage.open({ motionTypeId: 1 });
        await page.locator('#typePolicySupportMotions').selectOption('2');
        await page.locator('#typePolicySupportAmendments').selectOption('2');
        await page.locator('.adminTypeForm [name="save"]').first().click();

        const motion = new MotionPage(page);
        await motion.open({ motionSlug: '321-o-zapft-is' });
        await expect(page.locator('.motionSupportForm')).toHaveCount(0);

        await page.goto(`/stdparteitag/std-parteitag/amendment/2`);
        await expect(page.locator('.motionSupportForm')).toHaveCount(0);

        await new AdminIndexPage(page).open();
        await motionTypePage.open({ motionTypeId: 1 });
        await page.locator("input[name='type[motionLikesDislikes][]'][value='4']").check();
        await page.locator("input[name='type[amendmentLikesDislikes][]'][value='4']").check();
        await page.locator('.adminTypeForm [name="save"]').first().click();

        await motion.open({ motionSlug: '321-o-zapft-is' });
        await expect(page.locator('.motionSupportForm')).toBeVisible();

        await page.locator('input[name=motionSupportName]').fill('My name');
        await page.locator('input[name=motionSupportOrga]').fill('Orga');
        await page.locator('.motionSupportForm [name="motionSupport"]').click();

        await expect(page.locator('#supporters')).toContainText('My name (Orga)');
        await page.locator('.motionSupportForm [name="motionSupportRevoke"]').click();
        await expect(page.locator('#supporters')).not.toContainText('My name (Orga)');

        await page.goto(`/stdparteitag/std-parteitag/amendment/2`);
        await expect(page.locator('.motionSupportForm')).toBeVisible();

        await page.locator('input[name=motionSupportName]').fill('My name');
        await page.locator('input[name=motionSupportOrga]').fill('Orga');
        await page.locator('.motionSupportForm [name="motionSupport"]').click();

        await expect(page.locator('#supporters')).toContainText('My name (Orga)');
        await page.locator('.motionSupportForm [name="motionSupportRevoke"]').click();
        await expect(page.locator('#supporters')).not.toContainText('My name (Orga)');

        await new AdminIndexPage(page).open();
        await motionTypePage.open({ motionTypeId: 1 });
        await page.locator('#typeSupportType').selectOption('2');
        await page.locator('.adminTypeForm [name="save"]').first().click();

        await motion.open({ motionSlug: '321-o-zapft-is' });
        await expect(page.locator('.motionSupportForm')).toHaveCount(0);

        await page.goto(`/stdparteitag/std-parteitag/amendment/2`);
        await expect(page.locator('.motionSupportForm')).toHaveCount(0);

        await new AdminIndexPage(page).open();
        await motionTypePage.open({ motionTypeId: 1 });
        await expect(page.locator('#typeAllowSupportingAfterPublication')).toHaveCount(0);
        await page.locator('#typeAllowMoreSupporters').check();
        await expect(page.locator('#typeAllowSupportingAfterPublication')).toBeVisible();
        await page.locator('#typeAllowSupportingAfterPublication').check();
        await page.locator('.adminTypeForm [name="save"]').first().click();

        await motion.open({ motionSlug: '321-o-zapft-is' });
        await expect(page.locator('.motionSupportForm')).toBeVisible();

        await page.locator('input[name=motionSupportName]').fill('My name');
        await page.locator('input[name=motionSupportOrga]').fill('Orga');
        await page.locator('.motionSupportForm [name="motionSupport"]').click();

        await expect(page.locator('#supporters')).toContainText('My name (Orga)');
        await page.locator('.motionSupportForm [name="motionSupportRevoke"]').click();
        await expect(page.locator('#supporters')).not.toContainText('My name (Orga)');

        await page.goto(`/stdparteitag/std-parteitag/amendment/2`);
        await expect(page.locator('.motionSupportForm')).toBeVisible();

        await page.locator('input[name=motionSupportName]').fill('My name');
        await page.locator('input[name=motionSupportOrga]').fill('Orga');
        await page.locator('.motionSupportForm [name="motionSupport"]').click();

        await expect(page.locator('#supporters')).toContainText('My name (Orga)');
        await page.locator('.motionSupportForm [name="motionSupportRevoke"]').click();
        await expect(page.locator('#supporters')).not.toContainText('My name (Orga)');
    });
});