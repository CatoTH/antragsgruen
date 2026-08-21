import { test, expect } from '../../fixtures';
import { AmendmentPage } from '../../pages/AmendmentPage';

test.describe('LostInsertedParagraph', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('dummy placeholder for the Zeilenumbruch feature', async ({ page }) => {
        await expect(page.locator('body')).toContainText('dummy');

        const amendment = new AmendmentPage(page);
        await amendment.open({ motionSlug: '2', amendmentId: 276 });
        await expect(page.locator('body')).toContainText('Zeilenumbruch');
    });
});