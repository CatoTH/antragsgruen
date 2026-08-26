import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { FIRST_FREE_MOTION_ID } from '../../utils/constants';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { MotionPage } from '../../pages/MotionPage';
import { AdminConsultationPage } from '../../pages/AdminConsultationPage';

test.describe('Create motion for another initiator', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('the option is hidden for normal users', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await home.gotoMotionCreatePage();
        await test.step('check that this option is not available for normal users', async () => {
            await expect(page.locator('input[name=otherInitiator]').filter({ visible: true })).toHaveCount(0);
        });

    });

    test('an admin creates motions for others and for themselves', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        let createPage = await home.gotoMotionCreatePage();

        await loginAsStdAdmin(page);
        await test.step('check that it is available as an admin (and preselected)', async () => {
            await expect(page.locator('input[name=otherInitiator]')).toBeChecked();
            await expect(page.locator('#initiatorPrimaryName')).toHaveValue('');
            await expect(page.locator('#initiatorEmail')).toHaveValue('');

            await createPage.fillInValidSampleData('Testantrag 1');
            await createPage.saveForm();
        });

        await page.locator('#motionConfirmForm [name="confirm"]').click();
        await expect(page.locator('h1')).toContainText(/antrag veröffentlicht/i);
        await page.locator('#motionConfirmedForm [type="submit"]').click();
        await expect(page.locator('body')).toContainText('Testantrag 1');

        const motion = new MotionPage(page);
        await motion.open({ motionSlug: String(FIRST_FREE_MOTION_ID) });
        await expect(page.locator('body')).toContainText('Testantrag 1');
        await expect(page.locator('.sidebarActions .withdraw').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.sidebarActions .edit').filter({ visible: true })).toHaveCount(0);

        await home.open();
        createPage = await home.gotoMotionCreatePage();
        await test.step('create a motion as myself', async () => {
            await page.locator('input[name=otherInitiator]').first().uncheck();
            await createPage.fillInValidSampleData('Testantrag 2');
            await createPage.saveForm();
            await page.locator('#motionConfirmForm [name="confirm"]').click();
            await expect(page.locator('h1')).toContainText(/antrag veröffentlicht/i);
            await page.locator('#motionConfirmedForm [type="submit"]').click();
            await expect(page.locator('body')).toContainText('Testantrag 2');

            await motion.open({ motionSlug: String(FIRST_FREE_MOTION_ID + 1) });
            await expect(page.locator('body')).toContainText('Testantrag 2');
            await expect(page.locator('.sidebarActions .withdraw').first()).toBeVisible();
            await expect(page.locator('.sidebarActions .edit').filter({ visible: true })).toHaveCount(0);
        });

    });

    test('initiators may edit only their own motions', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        let createPage = await home.gotoMotionCreatePage();

        await loginAsStdAdmin(page);
        await createPage.fillInValidSampleData('Testantrag 1');
        await createPage.saveForm();
        await page.locator('#motionConfirmForm [name="confirm"]').click();
        await page.locator('#motionConfirmedForm [type="submit"]').click();

        await home.open();
        createPage = await home.gotoMotionCreatePage();
        await page.locator('input[name=otherInitiator]').first().uncheck();
        await createPage.fillInValidSampleData('Testantrag 2');
        await createPage.saveForm();
        await page.locator('#motionConfirmForm [name="confirm"]').click();
        await page.locator('#motionConfirmedForm [type="submit"]').click();

        const consultation = new AdminConsultationPage(page);
        await consultation.open();
        await test.step('enable motion editing for initiators', async () => {
            await page.locator('#iniatorsMayEdit').first().check();
            await consultation.saveForm();

            const motion = new MotionPage(page);
            await motion.open({ motionSlug: String(FIRST_FREE_MOTION_ID) });
            await expect(page.locator('.sidebarActions .withdraw').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.sidebarActions .edit').filter({ visible: true })).toHaveCount(0);

            await motion.open({ motionSlug: String(FIRST_FREE_MOTION_ID + 1) });
            await expect(page.locator('.sidebarActions .withdraw').first()).toBeVisible();
            await expect(page.locator('.sidebarActions .edit').first()).toBeVisible();

            await page.locator('.sidebarActions .edit a').click();
            await expect(page.locator('input[name=otherInitiator]')).not.toBeChecked();
        });

    });
});
