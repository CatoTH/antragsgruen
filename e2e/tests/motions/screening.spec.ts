import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, logout } from '../../utils/auth';
import { FIRST_FREE_MOTION_ID } from '../../utils/constants';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { AdminMotionTypePage } from '../../pages/AdminMotionTypePage';

const MOTION_TITLE = 'My new, screened motion';

test.describe('Motion screening', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('a screened motion is only published after being screened', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);
        await test.step('switch to motion screening mode', async () => {
            await expect(page.locator('#adminTodo').filter({ visible: true })).toHaveCount(0);

            const motionType = new AdminMotionTypePage(page);
            await motionType.open({ motionTypeId: 1 });
            await expect(page.locator('#screeningMotions')).not.toBeChecked();
            await page.locator('#screeningMotions').first().check();
            await page.locator('.adminTypeForm [name="save"]').first().click();
            await expect(page.locator('#screeningMotions')).toBeChecked();

            await home.open();
            await logout(page);

            const createPage = await home.gotoMotionCreatePage();
            await createPage.createMotion(MOTION_TITLE, true);
        });

        await test.step('create a motion as a logged out user', async () => {
            await expect(page.locator('body')).toContainText(
                'Er wird nun auf formale Richtigkeit geprüft und dann freigeschaltet.',
            );

            await home.open();
            await expect(page.locator('body')).not.toContainText(MOTION_TITLE, { useInnerText: true });

            await home.open();
            await loginAsStdAdmin(page);
        });

        await test.step('check that the motion is not visible yet', async () => {
            await page.locator('#adminTodo').click();
        });

        await test.step('go to the admin page', async () => {
            await expect(page.locator('.adminTodo')).toContainText(MOTION_TITLE);
        });

        await test.step('Screen the motion with an invalid title String (race condition)', async () => {
            await page.locator(`.adminTodo .motionScreen${FIRST_FREE_MOTION_ID} a`).click();
        });

        await test.step('screen the motion normally', async () => {
            await expect(page.locator('#motionScreenForm').first()).toBeVisible();
            await page.evaluate(() => {
                const w = window as any;
                w.$('#motionScreenForm input[name=titlePrefix]').attr('value', 'A3');
            });
            await page.locator('#motionScreenForm [name="screen"]').click();
            await expect(page.locator('body')).toContainText(
                'Das angegebene Antragskürzel wird bereits von einem anderen Antrag verwendet.',
            );

            await expect(page.locator('#motionScreenForm').first()).toBeVisible();
            await page.locator('#motionScreenForm [name="screen"]').click();
            await expect(page.locator('body')).toContainText('Der Antrag wurde freigeschaltet.');

            await home.open();
        });

        await test.step('check if the motion is visible now', async () => {
            await expect(page.locator('.motionListStd')).toContainText(MOTION_TITLE);
            await expect(page.locator('#sidebar ul.motions').last()).toContainText(MOTION_TITLE);
        });
    });
});
