import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { MotionCreatePage } from '../../pages/MotionCreatePage';
import { MotionEditPage } from '../../pages/MotionEditPage';

const SUBDOMAIN = '1laenderrat2015';
const CONSULTATION_PATH = '1laenderrat2015';
const MOTION_TYPE_ID = 8;

test.describe('Wurzelwerk motion creation permissions', () => {
    test.skip(true, 'No test available in the original Codeception suite');

    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('logging in is required to create a motion', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open({ subdomain: SUBDOMAIN, consultationPath: CONSULTATION_PATH });
        await test.step('check that I have to login in order to create a motion', async () => {
            await expect(page.locator('#sidebar .createMotion').first()).toBeVisible();
        });

        await test.step('check that I cannot create a motion as a standard user', async () => {
            await page.locator('#sidebar .createMotion').click();
            await expect(page.locator('h1')).toContainText(/login/i);
        });
    });

    test('a standard user may not create a motion', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open({ subdomain: SUBDOMAIN, consultationPath: CONSULTATION_PATH });
        await loginAsStdUser(page);
        await test.step('check that I can create a motion as a Wurzelwerk-user', async () => {
            await expect(page.locator('#sidebar .createMotion').filter({ visible: true })).toHaveCount(0);

            const editPage = new MotionEditPage(page);
            await editPage.open({
                subdomain: SUBDOMAIN,
                consultationPath: CONSULTATION_PATH,
                motionTypeId: MOTION_TYPE_ID,
            });
            await expect(page.locator('h1').getByText(/antrag stellen/i).filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('body')).toContainText(
                'Keine Berechtigung zum Anlegen von Anträgen',
            );
        });
    });

    test('an admin may create a motion via the direct URL', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open({ subdomain: SUBDOMAIN, consultationPath: CONSULTATION_PATH });
        await loginAsStdAdmin(page);
        await test.step('change that I can create a motion as admin', async () => {
            await expect(page.locator('#sidebar .createMotion').filter({ visible: true })).toHaveCount(0);

            const createPage = new MotionCreatePage(page);
            await createPage.open({
                subdomain: SUBDOMAIN,
                consultationPath: CONSULTATION_PATH,
                motionTypeId: MOTION_TYPE_ID,
            });
            await expect(page.locator('h1')).toContainText(/antrag stellen/i);
        });
    });
});
