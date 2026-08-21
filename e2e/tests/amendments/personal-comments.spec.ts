import { test, expect } from '../../fixtures';

import { AmendmentPage } from '../../pages/AmendmentPage';
import { loginAsStdUser } from '../../utils/auth';

test.describe('Amendments: PersonalComments', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('not see the comment section logged out', async ({ page }) => {
        await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is' });
        await expect(page.locator('.privateNoteOpener')).not.toBeVisible();
        await expect(page.locator('.privateNotes form')).not.toBeVisible();
    });

    test('see the comment section logged in and write a note', async ({ page }) => {
        await loginAsStdUser(page);
        await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is' });
        await expect(page.locator('.privateNoteOpener')).toBeVisible();
        await expect(page.locator('.privateNotes form')).not.toBeVisible();
        await expect(page.locator('#privatenote1')).not.toBeVisible();

        await page.locator('.privateNoteOpener button').click();
        await expect(page.locator('.privateNoteOpener')).not.toBeVisible();
        await expect(page.locator('.privateNotes form')).toBeVisible();
        await page.locator('.privateNotes form textarea').fill('Some comment');
        await page.locator('.privateNotes form [name="savePrivateNote"]').click();
        await expect(page.locator('#privatenote1')).toBeVisible();
        await expect(page.locator('#privatenote1')).toContainText('Some comment');
    });

    test('delete the note again', async ({ page }) => {
        await loginAsStdUser(page);
        await new AmendmentPage(page).open({ motionSlug: '321-o-zapft-is' });
        await page.locator('#privatenote1 .btnEdit').click();
        await page.locator('.privateNotes form textarea').fill('');
        await page.locator('.privateNotes form [name="savePrivateNote"]').click();

        await expect(page.locator('#privatenote1')).not.toBeVisible();
    });
});