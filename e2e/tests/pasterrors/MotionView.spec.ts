import { test, expect } from '../../fixtures';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { MotionPage } from '../../pages/MotionPage';

test.describe('MotionView', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('FORCELINEBREAK placeholder is not visible to public', async ({ page }) => {
        await new ConsultationHomePage(page).open();
        const motion = new MotionPage(page);
        await motion.open({ motionSlug: '3' });
        await expect(page.locator('body')).toContainText('Zitat 223');
        await expect(page.locator('body')).not.toContainText('###FORCELINEBREAK###');
    });
});