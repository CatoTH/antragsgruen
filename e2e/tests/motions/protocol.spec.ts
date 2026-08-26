import { test, expect } from '../../fixtures';
import { loginAsStdAdmin } from '../../utils/auth';
import { dispatchClick, setCkEditorContent } from '../../utils/dom';
import { ConsultationHomePage } from '../../pages/ConsultationHomePage';
import { MotionPage } from '../../pages/MotionPage';
import { AdminMotionListPage } from '../../pages/AdminMotionListPage';

const MOTION_SLUG = '321-o-zapft-is';

test.describe('Motion protocol', () => {
    test.beforeEach(async ({ db }) => {
        await db.populate('dbdata1');
    });

    test('a non-public protocol is hidden and becomes visible when published', async ({
        page,
    }) => {
        const home = new ConsultationHomePage(page);
        await home.open();
        await loginAsStdAdmin(page);

        const motionList = new AdminMotionListPage(page);
        await motionList.open();
        await page.locator('.adminMotionTable .motion2 .titleCol a').click();

        await expect(page.locator('.protocolHolder').filter({ visible: true })).toHaveCount(0);
        await dispatchClick(page, '.contentProtocolCaller button');
        await expect(page.locator('.protocolHolder').first()).toBeVisible();
        await setCkEditorContent(
            page,
            'protocol_text_wysiwyg',
            '<p>Famous quote</p><blockquote>So Long, and Thanks for All the Fish</blockquote>',
        );
        await page.locator('#motionUpdateForm [name="save"]').click();

        const motion = new MotionPage(page);
        await motion.open({ motionSlug: MOTION_SLUG });
        await expect(page.locator('.motionProtocol .protocolOpener').filter({ visible: true })).toHaveCount(0);

        await motionList.open();
        await page.locator('.adminMotionTable .motion2 .titleCol a').click();
        await page.locator("input[name='protocol_public'][value='1']").first().check();
        await page.locator('#motionUpdateForm [name="save"]').click();

        await motion.open({ motionSlug: MOTION_SLUG });
        await dispatchClick(page, '.motionProtocol .protocolOpener');
        await expect(page.locator('.protocolHolder')).toContainText(
            'So Long, and Thanks for All the Fish',
        );
    });
});
