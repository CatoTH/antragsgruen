import { test, expect } from '../../fixtures';

import { AmendmentPage } from '../../pages/AmendmentPage';
import { loginAsStdUser } from '../../utils/auth';

test.describe('Amendments: PersonalComments', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('not see the comment section logged out', async ({ page }) => {
        await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
        await expect(page.locator('.privateNoteOpener').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.privateNotes form').filter({ visible: true })).toHaveCount(0);
    });

    test('see the comment section logged in and write a note', async ({ page }) => {
        await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
        await loginAsStdUser(page);
        await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
        await expect(page.locator('.privateNoteOpener').first()).toBeVisible();
        await expect(page.locator('.privateNotes form').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('#privatenote1').filter({ visible: true })).toHaveCount(0);

        await page.locator('.privateNoteOpener button').click();
        await expect(page.locator('.privateNoteOpener').filter({ visible: true })).toHaveCount(0);
        await expect(page.locator('.privateNotes form').first()).toBeVisible();
        await page.locator('.privateNotes form textarea').first().fill('Some comment');
        await page.locator('.privateNotes form [name="savePrivateNote"]').click();
        await expect(page.locator('#privatenote1').first()).toBeVisible();
        await expect(page.locator('#privatenote1')).toContainText('Some comment');
    });

    test('delete the note again', async ({ page }) => {
        await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
        await loginAsStdUser(page);
        await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is', amendmentId: 1 });
        await test.step('delete it again', async () => {
            await page.locator('#privatenote1 .btnEdit').click();
            await page.locator('.privateNotes form textarea').first().fill('');
            await page.locator('.privateNotes form [name="savePrivateNote"]').click();

            await expect(page.locator('#privatenote1').filter({ visible: true })).toHaveCount(0);
        });
    });
});