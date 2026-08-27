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

        await test.step('not see the comment section', async () => {
            await expect(page.locator('.privateNoteOpener').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.privateNotes form').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.privateParagraphNoteOpener').filter({ visible: true })).toHaveCount(0);

            await loginAsStdUser(page);
        });

        await test.step('see the comment section logged in', async () => {
            await expect(page.locator('.privateNoteOpener').first()).toBeVisible();
            await expect(page.locator('.privateParagraphNoteOpener').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.privateNotes form').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('#privatenote1').filter({ visible: true })).toHaveCount(0);
        });

    });

    test('a motion note can be written and deleted', async ({ page }) => {
        const motion = new MotionPage(page);
        await motion.open({ motionSlug: MOTION_SLUG });
        await loginAsStdUser(page);

        await test.step('write a note', async () => {
            await page.locator('.privateNoteOpener button').click();
            await expect(page.locator('.privateNoteOpener').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.privateNotes form').first()).toBeVisible();
            await expect(page.locator('.privateParagraphNoteOpener').first()).toBeVisible();

            await page.locator('.privateNotes form textarea').first().fill('Some comment');
            await page.locator('.privateNotes form [name="savePrivateNote"]').click();
            await expect(page.locator('#privatenote1').first()).toBeVisible();
            await expect(page.locator('#privatenote1')).toContainText('Some comment');
        });

        await test.step('delete it again', async () => {
            await page.locator('#privatenote1 .btnEdit').click();
            await page.locator('.privateNotes form textarea').first().fill('');
            await page.locator('.privateNotes form [name="savePrivateNote"]').click();

            await expect(page.locator('.privateNoteOpener').first()).toBeVisible();
            await expect(page.locator('#privatenote1').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.privateParagraphNoteOpener').filter({ visible: true })).toHaveCount(0);
        });

    });

    test('a paragraph note can be written, edited and deleted', async ({ page }) => {
        const motion = new MotionPage(page);
        await motion.open({ motionSlug: MOTION_SLUG });
        await loginAsStdUser(page);

        await test.step('write a paragraph-note', async () => {
            await expect(page.locator('.privateParagraphNoteHolder form').filter({ visible: true })).toHaveCount(0);
            await page.locator('.privateNoteOpener button').click();
            await page.locator('#section_2_1 .privateParagraphNoteOpener button').click();
            await expect(page.locator('#section_2_1 .privateParagraphNoteHolder form').first()).toBeVisible();
        });

        await test.step('edit the note', async () => {
            await page
                .locator('#section_2_1 .privateParagraphNoteHolder textarea')
                .fill('More content');
            await page
                .locator('#section_2_1 .privateParagraphNoteHolder form [name="savePrivateNote"]')
                .click();
            await expect(page.locator('#section_2_1 #privatenote2').first()).toBeVisible();
            await expect(page.locator('#section_2_1 #privatenote2')).toContainText('More content');

            await expect(
                page.locator('#section_2_1 .privateParagraphNoteHolder textarea'),
            ).not.toBeVisible();
            await page.locator('#section_2_1 .privateParagraphNoteHolder .btnEdit').click();
            await page
                .locator('#section_2_1 .privateParagraphNoteHolder textarea')
                .fill('Changed content');
            await page
                .locator('#section_2_1 .privateParagraphNoteHolder form [name="savePrivateNote"]')
                .click();
            await expect(page.locator('#section_2_1 #privatenote2')).toContainText('Changed content');

            await page.locator('#section_2_1 .privateParagraphNoteHolder .btnEdit').click();
            await page.locator('#section_2_1 .privateParagraphNoteHolder textarea').first().fill('');
            await page
                .locator('#section_2_1 .privateParagraphNoteHolder form [name="savePrivateNote"]')
                .click();
            await expect(page.locator('#section_2_1 #privatenote2').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.privateNoteOpener').first()).toBeVisible();
            await expect(page.locator('.privateNotes form').filter({ visible: true })).toHaveCount(0);
            await expect(page.locator('.privateParagraphNoteOpener').filter({ visible: true })).toHaveCount(0);
        });

    });

    test('private notes can be disabled and re-enabled by an admin', async ({ page }) => {
        const home = new ConsultationHomePage(page);
        const motion = new MotionPage(page);
        const amendment = new AmendmentPage(page);

        await home.open();
        await loginAsStdAdmin(page);

        const appearance = new AdminAppearancePage(page);
        await appearance.open();
        await test.step('Disabled private notes', async () => {
            await page.locator('#showPrivateNotes').first().uncheck();
            await appearance.saveForm();

            await motion.open({ motionSlug: MOTION_SLUG });
            await expect(page.locator('.privateNoteOpener').filter({ visible: true })).toHaveCount(0);
            await amendment.open({ motionSlug: MOTION_SLUG, amendmentId: 3 });
            await expect(page.locator('.privateNoteOpener').filter({ visible: true })).toHaveCount(0);

            await appearance.open();
        });

        await test.step('Enable private notes again', async () => {
            await page.locator('#showPrivateNotes').first().check();
            await appearance.saveForm();

            await motion.open({ motionSlug: MOTION_SLUG });
            await expect(page.locator('.privateNoteOpener').first()).toBeVisible();
            await amendment.open({ motionSlug: MOTION_SLUG, amendmentId: 3 });
            await expect(page.locator('.privateNoteOpener').first()).toBeVisible();
        });

    });
});
