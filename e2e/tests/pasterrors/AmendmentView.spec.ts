import { test, expect } from '../../fixtures';
import { AmendmentPage } from '../../pages/AmendmentPage';

test.describe('AmendmentView', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('check amendment views', async ({ page }) => {
        const amendment = new AmendmentPage(page);
        await amendment.open({ motionSlug: '2', amendmentId: 1 });
        await expect(page.locator('body')).toContainText('Oamoi a Maß');

        await amendment.open({ motionSlug: '3', amendmentId: 2 });
        await expect(page.locator('body')).toContainText('Um das ganze mal zu testen');
        await expect(page.locator('body')).not.toContainText('###FORCELINEBREAK###', { useInnerText: true });
    });
});