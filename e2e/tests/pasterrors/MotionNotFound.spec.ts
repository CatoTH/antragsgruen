import { test, expect } from '../../fixtures';
import { MotionPage } from '../../pages/MotionPage';

test.describe('MotionNotFound', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('opening a non-existent motion shows the not-found message', async ({ page }) => {
        const motion = new MotionPage(page);
        await motion.open({ motionSlug: '112' });
        await expect(page.locator('body')).toContainText('Antrag nicht gefunden.');
    });
});