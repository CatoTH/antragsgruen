import { test, expect } from '../../fixtures';
import { loginAsStdAdmin, loginAsStdUser, logout } from '../../utils/auth';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { MotionPage } from '../../pages/MotionPage';
import { AmendmentPage } from '../../pages/AmendmentPage';
import { AdminAppearancePage } from '../../pages/AdminAppearancePage';

const MOTION_SLUG = '321-o-zapft-is';

test.describe('Personal notes', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('notes are hidden when logged out and shown when logged in', async ({ page }) => {
        const motion = new MotionPage(page);
        await motion.open({ motionSlug: MOTION_SLUG });

        await expect(page.locator('.privateNoteOpener')).toHaveCount(0);
        await expect(page.locator('.privateNotes form')).toHaveCount(0);
        await expect(page.locator('.privateParagraphNoteOpener')).toHaveCount(0);

        await loginAsStdUser(page);
        await expect(page.locator('.privateNoteOpener')).toBeVisible();
        await expect(page.locator('.privateParagraphNoteOpener')).toHaveCount(0);
        await expect(page.locator('.privateNotes form')).toHaveCount(0);
        await expect(page.locator('#privatenote1')).toHaveCount(0);
    });

    test('a motion note can be written and deleted', async ({ page }) => {
        const motion = new MotionPage(page);
        await motion.open({ motionSlug: MOTION_SLUG });
        await loginAsStdUser(page);

        await page.locator('.privateNoteOpener button').click();
        await expect(page.locator('.privateNoteOpener')).toHaveCount(0);
        await expect(page.locator('.privateNotes form')).toBeVisible();
        await expect(page.locator('.privateParagraphNoteOpener')).toBeVisible();

        await page.locator('.privateNotes form textarea').fill('Some comment');
        await page.locator('.privateNotes form [name="savePrivateNote"]').click();
        await expect(page.locator('#privatenote1')).toBeVisible();
        await expect(page.locator('#privatenote1')).toContainText('Some comment');

        await page.locator('#privatenote1 .btnEdit').click();
        await page.locator('.privateNotes form textarea').fill('');
        await page.locator('.privateNotes form [name="savePrivateNote"]').click();

        await expect(page.locator('.privateNoteOpener')).toBeVisible();
        await expect(page.locator('#privatenote1')).toHaveCount(0);
        await expect(page.locator('.privateParagraphNoteOpener')).toHaveCount(0);
    });

    test('a paragraph note can be written, edited and deleted', async ({ page }) => {
        const motion = new MotionPage(page);
        await motion.open({ motionSlug: MOTION_SLUG });
        await loginAsStdUser(page);

        await expect(page.locator('.privateParagraphNoteHolder form')).toHaveCount(0);
        await page.locator('.privateNoteOpener button').click();
        await page.locator('#section_2_1 .privateParagraphNoteOpener button').click();
        await expect(page.locator('#section_2_1 .privateParagraphNoteHolder form')).toBeVisible();

        await page
            .locator('#section_2_1 .privateParagraphNoteHolder textarea')
            .fill('More content');
        await page
            .locator('#section_2_1 .privateParagraphNoteHolder form [name="savePrivateNote"]')
            .click();
        await expect(page.locator('#section_2_1 #privatenote2')).toBeVisible();
        await expect(page.locator('#section_2_1 #privatenote2')).toContainText('More content');

        await expect(
            page.locator('#section_2_1 .privateParagraphNoteHolder textarea'),
        ).toHaveCount(0);
        await page.locator('#section_2_1 .privateParagraphNoteHolder .btnEdit').click();
        await page
            .locator('#section_2_1 .privateParagraphNoteHolder textarea')
            .fill('Changed content');
        await page
            .locator('#section_2_1 .privateParagraphNoteHolder form [name="savePrivateNote"]')
            .click();
        await expect(page.locator('#section_2_1 #privatenote2')).toContainText('Changed content');

        await page.locator('#section_2_1 .privateParagraphNoteHolder .btnEdit').click();
        await page.locator('#section_2_1 .privateParagraphNoteHolder textarea').fill('');
        await page
            .locator('#section_2_1 .privateParagraphNoteHolder form [name="savePrivateNote"]')
            .click();
        await expect(page.locator('#section_2_1 #privatenote2')).toHaveCount(0);
        await expect(page.locator('.privateNoteOpener')).toBeVisible();
        await expect(page.locator('.privateNotes form')).toHaveCount(0);
        await expect(page.locator('.privateParagraphNoteOpener')).toHaveCount(0);
    });

    test('private notes can be disabled and re-enabled by an admin', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        const motion = new MotionPage(page);
        const amendment = new AmendmentPage(page);

        await home.open();
        await loginAsStdAdmin(page);

        const appearance = new AdminAppearancePage(page);
        await appearance.open();
        await page.locator('#showPrivateNotes').uncheck();
        await appearance.saveForm();

        await motion.open({ motionSlug: MOTION_SLUG });
        await expect(page.locator('.privateNoteOpener')).toHaveCount(0);
        await amendment.open({ motionSlug: MOTION_SLUG, amendmentId: 3 });
        await expect(page.locator('.privateNoteOpener')).toHaveCount(0);

        await appearance.open();
        await page.locator('#showPrivateNotes').check();
        await appearance.saveForm();

        await motion.open({ motionSlug: MOTION_SLUG });
        await expect(page.locator('.privateNoteOpener')).toBeVisible();
        await amendment.open({ motionSlug: MOTION_SLUG, amendmentId: 3 });
        await expect(page.locator('.privateNoteOpener')).toBeVisible();
    });
});
