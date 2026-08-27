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
        await page.locator('#typePolicySupportMotions').first().selectOption('2');
        await page.locator('#typePolicySupportAmendments').first().selectOption('2');
        await page.locator('.adminTypeForm [name="save"]').first().click();

        const motion = new MotionPage(page);
        await motion.open({ motionSlug: '321-o-zapft-is' });
        await test.step('activate officially supporting it', async () => {
            await expect(page.locator('.motionSupportForm').filter({ visible: true })).toHaveCount(0);

            await page.goto(`/stdparteitag/std-parteitag/amendment/2`);
            await expect(page.locator('.motionSupportForm').filter({ visible: true })).toHaveCount(0);

            await new AdminIndexPage(page).open();
            await motionTypePage.open({ motionTypeId: 1 });
            await page.locator("input[name='type[motionLikesDislikes][]'][value='4']").first().check();
            await page.locator("input[name='type[amendmentLikesDislikes][]'][value='4']").first().check();
            await page.locator('.adminTypeForm [name="save"]').first().click();

            await motion.open({ motionSlug: '321-o-zapft-is' });
            await expect(page.locator('.motionSupportForm').first()).toBeVisible();

            await page.locator('input[name=motionSupportName]').first().fill('My name');
            await page.locator('input[name=motionSupportOrga]').first().fill('Orga');
            await page.locator('.motionSupportForm [name="motionSupport"]').click();

            await expect(page.locator('#supporters')).toContainText('My name (Orga)');
            await page.locator('.motionSupportForm [name="motionSupportRevoke"]').click();
            await expect(page.locator('#supporters').getByText('My name (Orga)').filter({ visible: true })).toHaveCount(0);

            await page.goto(`/stdparteitag/std-parteitag/amendment/2`);
            await expect(page.locator('.motionSupportForm').first()).toBeVisible();

            await page.locator('input[name=motionSupportName]').first().fill('My name');
            await page.locator('input[name=motionSupportOrga]').first().fill('Orga');
            await page.locator('.motionSupportForm [name="motionSupport"]').click();

            await expect(page.locator('#supporters')).toContainText('My name (Orga)');
            await page.locator('.motionSupportForm [name="motionSupportRevoke"]').click();
            await expect(page.locator('#supporters').getByText('My name (Orga)').filter({ visible: true })).toHaveCount(0);

            await new AdminIndexPage(page).open();
            await motionTypePage.open({ motionTypeId: 1 });
        });

        await test.step('ensure it is not enabled for published motions by default if there is a collection phase', async () => {
            await page.locator('#typeSupportType').first().selectOption('2');
            await page.locator('.adminTypeForm [name="save"]').first().click();

            await motion.open({ motionSlug: '321-o-zapft-is' });
            await expect(page.locator('.motionSupportForm').filter({ visible: true })).toHaveCount(0);

            await page.goto(`/stdparteitag/std-parteitag/amendment/2`);
            await expect(page.locator('.motionSupportForm').filter({ visible: true })).toHaveCount(0);

            await new AdminIndexPage(page).open();
            await motionTypePage.open({ motionTypeId: 1 });
        });

        await test.step('enable it for collection phase', async () => {
            await expect(page.locator('#typeAllowSupportingAfterPublication').filter({ visible: true })).toHaveCount(0);
            await page.locator('#typeAllowMoreSupporters').first().check();
            await expect(page.locator('#typeAllowSupportingAfterPublication').first()).toBeVisible();
            await page.locator('#typeAllowSupportingAfterPublication').first().check();
            await page.locator('.adminTypeForm [name="save"]').first().click();

            await motion.open({ motionSlug: '321-o-zapft-is' });
            await expect(page.locator('.motionSupportForm').first()).toBeVisible();

            await page.locator('input[name=motionSupportName]').first().fill('My name');
            await page.locator('input[name=motionSupportOrga]').first().fill('Orga');
            await page.locator('.motionSupportForm [name="motionSupport"]').click();

            await expect(page.locator('#supporters')).toContainText('My name (Orga)');
            await page.locator('.motionSupportForm [name="motionSupportRevoke"]').click();
            await expect(page.locator('#supporters').getByText('My name (Orga)').filter({ visible: true })).toHaveCount(0);

            await page.goto(`/stdparteitag/std-parteitag/amendment/2`);
            await expect(page.locator('.motionSupportForm').first()).toBeVisible();

            await page.locator('input[name=motionSupportName]').first().fill('My name');
            await page.locator('input[name=motionSupportOrga]').first().fill('Orga');
            await page.locator('.motionSupportForm [name="motionSupport"]').click();

            await expect(page.locator('#supporters')).toContainText('My name (Orga)');
            await page.locator('.motionSupportForm [name="motionSupportRevoke"]').click();
            await expect(page.locator('#supporters').getByText('My name (Orga)').filter({ visible: true })).toHaveCount(0);
        });

    });
});