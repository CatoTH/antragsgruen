import { test, expect } from '../../fixtures';
import { MotionCreatePage } from '../../pages/MotionCreatePage';

test.describe('Motion creation requires login', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('the login page is shown instead of the create form', async ({ page }) => {
        const createPage = new MotionCreatePage(page);
        await createPage.open({
            subdomain: 'bdk',
            consultationPath: 'bdk',
            motionTypeId: 7,
        });

        await expect(page.locator('h1').getByText(/antrag stellen/i).filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('h1')).toContainText(/login/i);
    });
});
